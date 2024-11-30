/**
 * @copyright   (C) 2023 SharkyKZ
 * @license     GPL-3.0-or-later
 */
const captchaKey = Joomla.getOptions('plg_captcha_recaptcha_v3.siteKey', '');
const triggerMethod = Joomla.getOptions('plg_captcha_recaptcha_v3.triggerMethod', 'submit');
const actionSelector = 'input.plg-captcha-recaptcha-v3-action';
const answerSelector = 'input.plg-captcha-recaptcha-v3-hidden';
const getAction = form => findAction(form).replace(/[^a-z0-9]+/gi, '_');
const findAction = function (form) {
	if (form.hasAttribute('class') && form.getAttribute('class') !== '') {
		let matchClass;
		form.getAttribute('class').split(/\s+/).forEach((className) => {
			if (className.match(/^(com|mod|plg)\-/)) {
				matchClass = className;
			}
		});
		if (matchClass) {
			return matchClass;
		}
	}
	if (form.hasAttribute('id') && form.getAttribute('id') !== '') {
		return form.getAttribute('id');
	}
	if (form.hasAttribute('name') && form.getAttribute('name') !== '') {
		return form.getAttribute('name');
	}
	return 'submit';
}

const handleSubmit = function (submitEvent) {
	submitEvent.preventDefault();
	grecaptcha.ready(function () {
		const actionElement = submitEvent.target.querySelector(actionSelector);
		actionElement.value = getAction(submitEvent.target);
		grecaptcha.execute(captchaKey, {action: actionElement.value}).then(function (token) {
			submitEvent.target.querySelector(answerSelector).value = token;
			submitEvent.target.submit();
		});
	});
}

const handleLoad = function (element) {
	grecaptcha.ready(function () {
		const actionElement = element.form.querySelector(actionSelector);
		actionElement.value = getAction(element.form);
		grecaptcha.execute(captchaKey, { action: actionElement.value }).then(function (token) {
			element.value = token;
		});
	});
}

Array.from(document.querySelectorAll(answerSelector)).map(function (element) {
	if (triggerMethod === 'submit') {
		element.form.addEventListener('submit', handleSubmit);

		return;
	}

	handleLoad(element);
	setInterval(handleLoad, 110_000, element);
});
