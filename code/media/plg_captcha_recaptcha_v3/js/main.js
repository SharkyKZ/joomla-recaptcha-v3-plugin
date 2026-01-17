/**
 * @copyright  (C) 2023 SharkyKZ
 * @license    GPL-3.0-or-later
 */
const captchaKey = Joomla.getOptions('plg_captcha_recaptcha_v3.siteKey', '');
const triggerMethod = Joomla.getOptions('plg_captcha_recaptcha_v3.triggerMethod', 'focusin');
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

const handleFocus = function(focusInEvent) {
	grecaptcha.ready(function () {
		const form = focusInEvent.target.form ?? focusInEvent.target.closest('input, textarea, select, button, fieldset').form;
		const actionElement = form.querySelector(actionSelector);
		actionElement.value = getAction(form);
		const answerElement = form.querySelector(answerSelector);
		grecaptcha.execute(captchaKey, {action: actionElement.value}).then(function (token) {
			answerElement.value = token;
			setInterval(handleLoad, 110_000, answerElement);
		});
	});
}

const handleIframeFocus = function(focusInEvent, addedNode) {
	const form = addedNode.closest('input, textarea, select, button, fieldset').form;
	grecaptcha.ready(function () {
		const actionElement = form.querySelector(actionSelector);
		actionElement.value = getAction(form);
		const answerElement = form.querySelector(answerSelector);
		grecaptcha.execute(captchaKey, {action: actionElement.value}).then(function (token) {
			answerElement.value = token;
			setInterval(handleLoad, 110_000, answerElement);
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

const observerConfig = {childList: true, subtree: true};

const observerCallback = (mutations, observer) => {
	for (const mutation of mutations) {
		for (const addedNode of mutation.addedNodes) {
			if (addedNode.nodeType !== Node.ELEMENT_NODE || addedNode.tagName !== 'IFRAME') {
				continue;
			}
			addedNode.contentDocument.addEventListener('focusin', (event) => handleIframeFocus(event, addedNode), {once: true});
		}
	}
};

Array.from(document.querySelectorAll(answerSelector)).map(function (element) {
	if (triggerMethod === 'submit') {
		element.form.addEventListener('submit', handleSubmit);
		return;
	}

	if (triggerMethod === 'focusin') {
		element.form.addEventListener('focusin', handleFocus, {once: true});

		// Special case for editors using dynamically addeds iframes
		const observer = new MutationObserver(observerCallback);
		observer.observe(element.form, observerConfig);

		return;
	}

	handleLoad(element);
	setInterval(handleLoad, 110_000, element);
});
