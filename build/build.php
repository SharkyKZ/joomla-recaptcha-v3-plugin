<?php

require __DIR__ . '/vendor/autoload.php';

use Sharky\Joomla\PluginBuildScript\Script;

(
	new class(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'recaptcha_v3',
		'captcha',
		'joomla-recaptcha-v3-plugin',
		'SharkyKZ',
		'Captcha - reCAPTCHA v3',
		'Google reCAPTCHA v3 plugin for Joomla!.',
		'(5\.|4\.)',
		'7.2.5',
	) extends Script {
		public function build(): void
		{
			$plugin = $this->pluginDirectory . '/src/Plugin.php';
			$script = $this->mediaDirectory . '/js/main.js';

			$hash = substr(hash_file('sha1', $script, false), 0, 8);
			$code = file_get_contents($plugin);

			$pattern = '/(private\s+const\s+SCRIPT_HASH\s+=\s+\')(.*)(\';)/';
			$code = preg_replace($pattern, '${1}' . $hash . '$3', $code);

			file_put_contents($plugin, $code);

			parent::build();
		}
	}
)->build();
