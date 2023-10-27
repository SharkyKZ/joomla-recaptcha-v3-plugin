Array.from(document.querySelectorAll('.plg-captcha-recaptcha-v3-hidden'))
	.map((element) => element.form.addEventListener('submit', (event) => {
    event.preventDefault();
    grecaptcha.ready(function() {
      grecaptcha.execute(Joomla.getOptions('plg_captcha_recaptcha_v3.siteKey'), {action: 'submit'}).then(function(token) {
        element.value = token;
        event.target.submit();
      });
    });
	}));
