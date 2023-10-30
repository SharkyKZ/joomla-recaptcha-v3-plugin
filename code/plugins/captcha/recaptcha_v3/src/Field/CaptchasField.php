<?php
/**
 * @copyright   (C) 2023 SharkyKZ
 * @license     GPL-2.0-or-later
 */
namespace Sharky\Plugin\Captcha\RecaptchaV3\Field;

\defined('_JEXEC') || exit;

use Joomla\CMS\Form\Field\PluginsField;

/**
 * Google reCAPTCHA v3 plugin
 *
 * @since  1.0.0
 */
final class CaptchasField extends PluginsField
{
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if ($result = parent::setup($element, $value, $group))
		{
			$this->folder = 'captcha';
		}

		return $result;
	}

	public function getOptions()
	{
		$options = parent::getOptions();

		return array_filter(
			$options,
			static function ($v)
			{
				return $v->value !== 'recaptcha_v3';
			}
		);
	}
}
