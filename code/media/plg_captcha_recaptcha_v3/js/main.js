/**
 * @copyright   (C) 2023 SharkyKZ
 * @license     GPL-2.0-or-later
 */
Array.from(document.querySelectorAll("input.plg-captcha-recaptcha-v3-hidden")).map(function (element) {
	element.form.addEventListener("submit", function (event) {
		event.preventDefault();
		grecaptcha.ready(function () {
			const action = (function (form) {
				let matchClass;
				form.classList.forEach((element) => {
					if (element.match(/^(com|mod|plg)\-/)) {
						matchClass = element;
					}
				});
				if (matchClass) {
					return matchClass;
				}
				if (form.id) {
					return form.id;
				}
				if (form.name) {
					return form.name;
				}
				return "submit";
			})(event.target);
			let actionElement = event.target.querySelector(".plg-captcha-recaptcha-v3-action");
			actionElement.value = action.replace(/[^a-z0-9]+/gi, "_");
			grecaptcha
				.execute(Joomla.getOptions("plg_captcha_recaptcha_v3.siteKey"), {
					action: actionElement.value,
				})
				.then(function (token) {
					element.value = token;
					event.target.submit();
				});
		});
	});
});
