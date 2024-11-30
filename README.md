# Google reCAPTCHA v3 plugin for Joomla! 5.0 and 4.0.
Register on [reCAPTCHA admin console](https://www.google.com/recaptcha/admin/create) to get your site and secret keys.
## Plugin features
- Configurable rejection score threshold
- Option to show alternative captcha on failure
- Based on Joomla's Captcha API, supports compliant 3rd party extensions

## System Requirements
- Joomla! 4.0 or higher
- PHP 7.2.5 or higher

## Troubleshooting
By default CAPTCHA challenge is triggered on form submit (i.e. when user clicks submit button). This is recommended but may be incompatible with some extensions. Version `1.2.0` added `Trigger Method` option to allow automatically triggering CAPTCHA on page load. If you're receiving `Captcha answer is missing` errors, enabling this option may solve the issue. CAPTCHA challenges triggered on page load are automatically refreshed every 2 minutes. 
