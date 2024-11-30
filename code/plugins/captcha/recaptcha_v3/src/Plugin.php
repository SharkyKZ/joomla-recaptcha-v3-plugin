<?php
/**
 * @copyright   (C) 2023 SharkyKZ
 * @license     GPL-3.0-or-later
 */
namespace Sharky\Plugin\Captcha\RecaptchaV3;

\defined('_JEXEC') || exit;

use Joomla\Application\SessionAwareWebApplicationInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Captcha\CaptchaRegistry;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CaptchaField;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Google reCAPTCHA v3 plugin
 *
 * @since  1.0.0
 */
final class Plugin implements PluginInterface
{
	/**
	* Remote service error codes
	*
	* @var	string[]
	* @since  1.0.0
	*/
	private const ERROR_CODES = [
		'missing-input-secret',
		'invalid-input-secret',
		'missing-input-response',
		'invalid-input-response',
		'bad-request',
		'timeout-or-duplicate',
	];

	/**
	 * Hash of the script file.
	 *
	 * @var	 string
	 * @since  1.0.0
	 */
	private const SCRIPT_HASH = '69134694';

	/**
	 * Application instance.
	 *
	 * @var	 CMSApplicationInterface
	 * @since  1.0.0
	 */
	private $app;

	/**
	 * Plugin parameters.
	 *
	 * @var	 Registry
	 * @since  1.0.0
	 */
	private $params;

	/**
	 * HTTP factory instance.
	 *
	 * @var	 HttpFactory
	 * @since  1.0.0
	 */
	private $httpFactory;

	/**
	 * Alternative Captcha instance, if set.
	 *
	 * @var	 ?Captcha
	 * @since  1.0.0
	 */
	private $captcha;

	/**
	 * Class constructor.
	 *
	 * @param   CMSApplicationInterface  $app          Application instance.
	 * @param   Registry                 $params       Plugin parameters.
	 * @param   HttpFactory              $httpFactory  HTTP factory instance.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function __construct(CMSApplicationInterface $app, Registry $params, HttpFactory $httpFactory)
	{
		$this->app = $app;
		$this->params = $params;
		$this->httpFactory = $httpFactory;
	}

	/**
	 * Unused method required to comply with broken architecture.
	 *
	 * @param   DispatcherInterface
	 *
	 * @return  $this
	 *
	 * @since   1.0.0
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		return $this;
	}

	/**
	 *  Unused method required to comply with broken architecture.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function registerListeners()
	{
	}

	/**
	 * Initialises the captcha.
	 *
	 * @param   ?string  $id  The id of the field.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function onInit($id = null)
	{
		if ($this->shouldShowCaptcha())
		{
			return $this->getCaptcha()->initialise($id);
		}

		if (!$siteKey = $this->params->get('siteKey'))
		{
			return false;
		}

		if (!$this->app instanceof CMSWebApplicationInterface)
		{
			return false;
		}

		$document = $this->app->getDocument();

		if (!$document instanceof HtmlDocument)
		{
			return false;
		}

		$document->addScriptOptions('plg_captcha_recaptcha_v3.siteKey', $siteKey);
		$document->addScriptOptions('plg_captcha_recaptcha_v3.triggerMethod', $this->params->get('triggerMethod', 'submit'));
		$assetManager = $document->getWebAssetManager();

		if (!$assetManager->assetExists('script', 'plg_captcha_recaptcha_v3.api.js'))
		{
			$languageTag = $this->app->getLanguage()->getTag();
			$assetManager->registerAsset(
				'script',
				'plg_captcha_recaptcha_v3.api.js',
				'https://www.google.com/recaptcha/api.js?hl=' . $languageTag . '&render=' . $siteKey,
				[],
				['defer' => true, 'referrerpolicy' => 'no-referrer'],
				['core']
			);
		}

		if (!$assetManager->assetExists('script', 'plg_captcha_recaptcha_v3.main.js'))
		{
			$assetManager->registerAsset(
				'script',
				'plg_captcha_recaptcha_v3.main.js',
				'plg_captcha_recaptcha_v3/main.js',
				['version' => self::SCRIPT_HASH],
				['type' => 'module'],
				['plg_captcha_recaptcha_v3.api.js', 'core']
			);
		}

		$assetManager->useAsset('script', 'plg_captcha_recaptcha_v3.api.js');
		$assetManager->useAsset('script', 'plg_captcha_recaptcha_v3.main.js');

		return true;
	}

	/**
	 * Generates HTML field markup.
	 *
	 * @param   ?string  $name   The name of the field.
	 * @param   ?string  $id	 The id of the field.
	 * @param   ?string  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since  1.0.0
	 */
	public function onDisplay($name = null, $id = null, $class = '')
	{
		if ($this->shouldShowCaptcha())
		{
			return $this->getCaptcha()->display($name, $id, $class);
		}

		$this->loadLanguage();

		if (!$this->params->get('siteKey'))
		{
			return $this->render('nokey');
		}

		$attributes = [
			'type' => 'hidden',
			'class' => 'plg-captcha-recaptcha-v3-hidden',
		];

		if ($name !== null && $name !== '')
		{
			$attributes['name'] = $name;
		}

		if ($id !== null && $id !== '')
		{
			$attributes['id'] = $id;
		}

		$attributes = array_map([$this, 'escape'], $attributes);

		$html = '<input ' . ArrayHelper::toString($attributes) . '>';
		$html .= '<input type="hidden" name="plg_captcha_recaptcha_v3_action" class="plg-captcha-recaptcha-v3-action">';
		$html .= $this->render('noscript');


		return $html;
	}

	/**
	 * Alters form field.
	 *
	 * @param   CaptchaField       $field    Captcha field instance
	 * @param   \SimpleXMLElement  $element  XML form definition
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onSetupField(CaptchaField $field, \SimpleXMLElement $element)
	{
		if ($this->shouldShowCaptcha())
		{
			$this->getCaptcha()->setupField($field, $element);

			return;
		}

		$element['hiddenLabel'] = 'true';
	}

	/**
	 * Makes HTTP request to remote service to verify user's answer.
	 *
	 * @param   ?string  $code  Answer provided by user.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		if ($this->shouldShowCaptcha())
		{
			if ($answer = $this->getCaptcha()->checkAnswer($code))
			{
				$this->setShouldShowCaptcha(false);
			}

			return $answer;
		}

		$language = $this->app->getLanguage();
		$this->loadLanguage();

		if ($code === null || $code === '')
		{
			// No answer provided, form was manipulated.
			throw new \RuntimeException($language->_('PLG_CAPTCHA_RECAPTCHA_V3_ERROR_EMPTY_RESPONSE'));
		}

		if (!$this->params->get('secret'))
		{
			throw new \RuntimeException($language->_('PLG_CAPTCHA_RECAPTCHA_V3_NO_SECRET_KEY'));
		}

		try
		{
			$http = $this->httpFactory->getHttp();
		}
		catch (\RuntimeException $exception)
		{
			if (\JDEBUG)
			{
				throw $exception;
			}

			// No HTTP transports supported.
			return !$this->params->get('strictMode');
		}

		$data = [
			'response' => $code,
			'secret' => $this->params->get('secret'),
		];

		try
		{
			$response = $http->post('https://www.google.com/recaptcha/api/siteverify', $data);
			$body = json_decode($response->body);
		}
		catch (\RuntimeException $exception)
		{
			if (\JDEBUG)
			{
				throw $exception;
			}

			// Connection or transport error.
			return !$this->params->get('strictMode');
		}

		// Remote service error.
		if ($body === null)
		{
			if (\JDEBUG)
			{
				throw new \RuntimeException($language->_('PLG_CAPTCHA_RECAPTCHA_V3_ERROR_INVALID_RESPONSE'));
			}

			return !$this->params->get('strictMode');
		}

		if (!isset($body->success) || $body->success !== true)
		{
			// If error codes are pvovided, use them for language strings.
			if (!empty($body->{'error-codes'}) && \is_array($body->{'error-codes'}))
			{
				if ($errors = array_intersect($body->{'error-codes'}, self::ERROR_CODES))
				{
					$error = $errors[array_key_first($errors)];

					throw new \RuntimeException($language->_('PLG_CAPTCHA_RECAPTCHA_V3_ERROR_' . strtoupper(str_replace('-', '_', $error))));
				}
			}

			return false;
		}

		$score = $this->params->get('score', 0.5);

		if (!\is_float($score) || $score < 0 || $score > 1)
		{
			$score = 0.5;
		}

		if (!isset($body->action) || $body->action !== $this->app->getInput()->get('plg_captcha_recaptcha_v3_action', '', 'RAW'))
		{
			throw new \RuntimeException('PLG_CAPTCHA_RECAPTCHA_V3_ERROR_INVALID_ACTION');
		}

		if (!isset($body->score) || $body->score < $score)
		{
			if ($this->hasCaptcha())
			{
				$this->setShouldShowCaptcha(true);
			}

			throw new \RuntimeException($language->_('PLG_CAPTCHA_RECAPTCHA_V3_ERROR_CAPTCHA_VERIFICATION'));
		}

		if ($this->hasCaptcha())
		{
			$this->setShouldShowCaptcha(false);
		}

		return true;
	}

	private function escape(?string $string): string
	{
		return $string ? htmlspecialchars($string, \ENT_QUOTES|\ENT_SUBSTITUTE|\ENT_HTML5, 'UTF-8') : (string) $string;
	}

	private function render(string $layout): string
	{
		$html = '';
		$file = PluginHelper::getLayoutPath('captcha', 'recaptcha_v3', $layout);

		if (!is_file($file))
		{
			return '';
		}

		$data = [
			'language' => $this->app->getLanguage(),
		];

		ob_start();

		(static function ()
		{
			extract(func_get_arg(1));

			include func_get_arg(0);
		})($file, $data);

		$html .= ob_get_clean();

		return $html;
	}

	private function loadLanguage(): void
	{
		$this->app->getLanguage()->load('plg_captcha_recaptcha_v3', \JPATH_ADMINISTRATOR);
	}

	private function setShouldShowCaptcha(bool $value): void
	{
		if (!$this->app instanceof SessionAwareWebApplicationInterface)
		{
			return;
		}

		if ($value)
		{
			$this->app->getSession()->set('plg_captcha_recaptcha_v3.showCaptcha', true);

			return;
		}

		$this->app->getSession()->remove('plg_captcha_recaptcha_v3.showCaptcha');
	}

	private function shouldShowCaptcha(): bool
	{
		if (!$this->hasCaptcha())
		{
			return false;
		}

		if (!$this->app instanceof SessionAwareWebApplicationInterface)
		{
			return false;
		}

		return $this->app->getSession()->has('plg_captcha_recaptcha_v3.showCaptcha');
	}

	private function hasCaptcha(): bool
	{
		if (!$captcha = $this->params->get('captcha'))
		{
			return false;
		}

		if ($captcha === 'recaptcha_v3')
		{
			return false;
		}

		if (version_compare(\JVERSION, '5.0', '>='))
		{
			$container = Factory::getContainer();

			if ($container->has(CaptchaRegistry::class) && $container->get(CaptchaRegistry::class)->has($captcha))
			{
				return true;
			}
		}

		return PluginHelper::isEnabled('captcha', $captcha);
	}

	private function getCaptcha(): Captcha
	{
		if ($this->captcha === null)
		{
			$this->captcha = Captcha::getInstance($this->params->get('captcha'));
		}

		return $this->captcha;
	}
}
