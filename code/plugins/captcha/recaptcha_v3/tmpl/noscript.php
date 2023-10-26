<?php
/**
 * @copyright   (C) 2023 SharkyKZ
 * @license	 GPL-2.0-or-later
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') || exit;

/** @var Sharky\Plugin\Captcha\RecaptchaV3\Plugin $this */

?>
<noscript>
	<div class="alert alert-warning">
		<?= Text::_('PLG_CAPTCHA_RECAPTCHA_V3_NOSCRIPT') ?>
	</div>
</noscript>
