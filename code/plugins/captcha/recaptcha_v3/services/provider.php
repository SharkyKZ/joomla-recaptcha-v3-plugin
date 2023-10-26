<?php

defined('_JEXEC') || exit;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Sharky\Plugin\Captcha\RecaptchaV3\Plugin;

return new class implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->share(
			PluginInterface::class,
			static function ()
			{
				return new Plugin(
					Factory::getApplication(),
					new Registry(PluginHelper::getPlugin('captcha', 'recaptcha_v3')->params ?? null)
				);
			}
		);
	}
};
