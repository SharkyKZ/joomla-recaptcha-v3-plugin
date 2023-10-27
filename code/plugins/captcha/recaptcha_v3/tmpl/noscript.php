<?php
/**
 * @copyright   (C) 2023 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') || exit;

/** @var Sharky\Plugin\Captcha\RecaptchaV3\Plugin $this */

?>
<noscript>
	<div class="alert alert-warning">
		<?= $this->app->getLanguage()->_('PLG_CAPTCHA_RECAPTCHA_V3_NOSCRIPT') ?>
	</div>
</noscript>
