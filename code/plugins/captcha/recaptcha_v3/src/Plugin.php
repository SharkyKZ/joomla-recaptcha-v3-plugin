<?php
/**
 * @copyright   (C) 2023 SharkyKZ
 * @license     GPL-2.0-or-later
 */
namespace Sharky\Plugin\Captcha\RecaptchaV3;

\defined('_JEXEC') || exit;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Form\Field\CaptchaField;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

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
	private const SCRIPT_HASH = 'aa574c49';

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
	 * Class constructor.
	 *
	 * @param   Registry  $params  Plugin parameters.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function __construct(CMSApplicationInterface $app, Registry $params)
	{
		$this->app = $app;
		$this->params = $params;
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
	 * @param   string|null  $id  The id of the field.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function onInit($id = null)
	{
		if (!$this->app instanceof CMSWebApplicationInterface)
		{
			return true;
		}

		$document = $this->app->getDocument();

		if (!$document instanceof HtmlDocument)
		{
			return true;
		}

		$document->addScriptOptions('plg_captcha_recaptcha_v3.siteKey', $this->params->get('siteKey'));

		$assetManager = $document->getWebAssetManager();
		$assetManager->useScript('core');

		if (!$assetManager->assetExists('script', 'plg_captcha_recaptcha_v3.api.js'))
		{
			$assetManager->registerAndUseScript(
				'plg_captcha_recaptcha_v3.api.js',
				'https://www.google.com/recaptcha/api.js?render=' . $this->params->get('siteKey'),
				[],
				['async' => true]
			);
		}

		if (!$assetManager->assetExists('script', 'plg_captcha_recaptcha_v3.main.js'))
		{
			$assetManager->registerAndUseScript(
				'plg_captcha_recaptcha_v3.main.js',
				'plg_captcha_recaptcha_v3/main.js',
				['version' => self::SCRIPT_HASH],
				['async' => true, 'defer' => true]
			);
		}

		return true;
	}

	/**
	 * Generates HTML field markup.
	 *
	 * @param   string|null  $name   The name of the field.
	 * @param   string|null  $id	 The id of the field.
	 * @param   string|null  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since  1.0.0
	 */
	public function onDisplay($name = null, $id = null, $class = '')
	{
		$html = '<input type="hidden" name="' . $name . '" class="plg-captcha-recaptcha-v3-hidden">';

		ob_start();
		(function ()
		{
			include PluginHelper::getLayoutPath('captcha', 'recaptcha_v3', 'noscript');
		})();

		$html .= ob_get_clean();

		return $html;
	}

	/**
	 * Alters form field.
	 *
	 * @param   CaptchaField	   $field	Captcha field instance
	 * @param   \SimpleXMLElement  $element  XML form definition
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function onSetupField(CaptchaField $field, \SimpleXMLElement $element)
	{
		$element['hiddenLabel'] = 'true';
	}

	/**
	 * Makes HTTP request to remote service to verify user's answer.
	 *
	 * @param   string|null  $code  Answer provided by user.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		$language = $this->app->getLanguage();
		$language->load('plg_captcha_recaptcha_v3', \JPATH_ADMINISTRATOR);

		if ($code === null || $code === '')
		{
			// No answer provided, form was manipulated.
			throw new \RuntimeException($language->_('PLG_CAPTCHA_RECAPTCHA_V3_ERROR_EMPTY_ANSWER'));
		}

		try
		{
			$http = (new HttpFactory)->getHttp();
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

		$data = array(
			'response' => $code,
			'secret' => $this->params->get('secret'),
		);

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

		return true;
	}
}
