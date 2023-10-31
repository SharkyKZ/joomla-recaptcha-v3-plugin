Array.from(document.querySelectorAll('input.plg-captcha-recaptcha-v3-hidden')).map(
	function (element) {
			element.form.addEventListener(
			'submit',
			function (event) {
				event.preventDefault();
				grecaptcha.ready(
					function () {
						action = (function (form) {
							form.classList.forEach(element => {
								if (element.startsWith('com-') || element.startsWith('mod-') || element.startsWith('plg-')) {
									return element;
								}
							});
							if (form.id) {
								return form.id;
							}
							if (form.name) {
								return form.name;
							}
							return 'submit';
						})(event.target);
						actionElement = event.target.querySelector('.plg-captcha-recaptcha-v3-action');
						actionElement.value = action.replace(/[^a-z0-9_]/, '_');
						grecaptcha.execute(
							Joomla.getOptions('plg_captcha_recaptcha_v3.siteKey'),
							{action: actionElement.value}
						).then(
							function (token) {
								element.value = token;
								event.target.submit();
							}
						);
					}
				);
			}
		)
	}
);
