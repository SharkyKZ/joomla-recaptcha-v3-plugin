Array.from(document.querySelectorAll('input.plg-captcha-recaptcha-v3-hidden')).map(
	function (element) {
			element.form.addEventListener(
			'submit',
			function (event) {
				event.preventDefault();
				grecaptcha.ready(
					function () {
						grecaptcha.execute(
							Joomla.getOptions('plg_captcha_recaptcha_v3.siteKey'),
							{
								action: (function (form) {
									if (form.name) {
										return form.name;
									}
									form.classList.forEach(element => {
										if (element.startsWith('com-') || element.startsWith('mod-') || element.startsWith('plg-')) {
											return element;
										}
									});
									if (form.id) {
										return form.id;
									}
									return 'submit';
								})(event.target)
							}
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
