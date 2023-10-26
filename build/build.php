<?php

require (dirname(__DIR__)) . '/build-script/script.php';

(
	new PluginBuildScript(
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
	)
)->build();
