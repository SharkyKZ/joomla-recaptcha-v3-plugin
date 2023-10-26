<?php
/**
 * @copyright   (C) 2023 SharkyKZ
 * @license	 GPL-2.0-or-later
 */
namespace Sharky\Plugin\Captcha\RecaptchaV3;

\defined('_JEXEC') || exit;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Extension\PluginInterface;
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
	 * Application instance.
	 *
	 * @var	CMSApplicationInterface
	 * @since  1.0.0
	 */
	private $app;

	/**
	 * Plugin parameters.
	 *
	 * @var	Registry
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
	 * @throws  RuntimeException
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

		$assetManager = $document->getWebAssetManager();

		if (!$assetManager->assetExists('script', 'plg_captcha_recaptcha_v3.js'))
		{
			$assetManager->registerAndUseScript('plg_captcha_recaptcha_v3.js', 'https://www.google.com/recaptcha/api.js');
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
		ob_start();
		include PluginHelper::getLayoutPath('captcha', 'recaptcha_v3', 'noscript');
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Makes HTTP request to remote service to verify user's answer.
	 *
	 * @param   string|null  $code  Answer provided by user.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		return true;
	}
}
