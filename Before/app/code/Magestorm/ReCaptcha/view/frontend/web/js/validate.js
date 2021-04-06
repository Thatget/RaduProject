/**
 * Created by Magestorm Team on 2/3/2017.
 */
define([
	"jquery",
	"domReady!",
	'ko'
], function($) {
	"use strict";

	$.widget("magestorm.recaptcha", {
		_init: function () {
			var self = this;
			self.formToProtectOnPage = [];
			var formsToProtect = self.options.data;
			var checkKo = false;
			formsToProtect.forEach(function (item) {
				if (item.ko_selector && $(item.ko_selector).length) {
					checkKo = item.ko_selector;
				}
			});
			if (checkKo) {
				var bindingContextElement = document.querySelector(checkKo);

				var renderThrottle = 300;
				var renderCycle = null;
				var onRenderComplete = function () {
					self.renderCaptcha(self);
				};

				// Observe mutations to element, call onRenderComplete after 300ms of no mutations
				var observer = new MutationObserver(function (mutations) {
					clearTimeout(renderCycle);
					renderCycle = setTimeout(onRenderComplete, renderThrottle);
				});

				var config = {
					childList: true
				};

				observer.observe(bindingContextElement, config);
			} else {
				this.renderCaptcha(self);
			}
		},

		renderCaptcha: function (_self) {
			_self.formToProtectOnPage = [];
			var formsToProtect = _self.options.data;
			formsToProtect.forEach(function (item) {
				var formToProtect = {"form": $(item.form_selector)[0], "button": item.button_selector};
				if (formToProtect && $(item.form_selector)[0]) {
					_self.formToProtectOnPage.push(formToProtect);
				}
			});
			if (_self.formToProtectOnPage.length) {
				var reCaptchaScript = document.createElement('script');
				reCaptchaScript.src = 'https://www.google.com/recaptcha/api.js?onload=reCaptchaOnloadCallback&render=explicit';
				reCaptchaScript.async = true;
				reCaptchaScript.defer = true;
				document.body.appendChild(reCaptchaScript);
			}

			window.reCaptchaOnloadCallback = function () {
				for (var i = 0; i < _self.formToProtectOnPage.length; i++) {
					if (_self.options.type == 'default') {
						if (_self.formToProtectOnPage[i].button) {
							_self.formToProtectOnPage[i].form.querySelector('button'+_self.formToProtectOnPage[i].button).closest("div").insertAdjacentHTML('afterbegin', '<div id="recaptcha-' + _self.formToProtectOnPage[i].form.id + '"></div>' +
								'<div class="control"><input type="checkbox" value="" class="required-captcha checkbox" name="recaptcha-validate-' + _self.formToProtectOnPage[i].form.id + '"' +
								'data-validate="{required:true,messages:{required:\'Make sure you are not robots.\'}}" /></div>');
						} else {
							if (_self.formToProtectOnPage[i].form.querySelector(".actions-toolbar")) {
								_self.formToProtectOnPage[i].form.querySelector(".actions-toolbar").insertAdjacentHTML('beforebegin', '<div id="recaptcha-' + _self.formToProtectOnPage[i].form.id + '"></div>' +
									'<div class="control"><input type="checkbox" value="" class="required-captcha checkbox" name="recaptcha-validate-' + _self.formToProtectOnPage[i].form.id + '"' +
									'data-validate="{required:true,messages:{required:\'Make sure you are not robots.\'}}" /></div>');
							} else {
								_self.formToProtectOnPage[i].form.querySelector("[type='submit']").closest("div").insertAdjacentHTML('afterbegin', '<div id="recaptcha-' + _self.formToProtectOnPage[i].form.id + '"></div>' +
									'<div class="control"><input type="checkbox" value="" class="required-captcha checkbox" name="recaptcha-validate-' + _self.formToProtectOnPage[i].form.id + '"' +
									'data-validate="{required:true,messages:{required:\'Make sure you are not robots.\'}}" /></div>');
							}
						}
					} else {
						_self.formToProtectOnPage[i].form.innerHTML +=
							'<input type="hidden" name="magestorm_invisible_token" value="" />';
					}
				}

				var id = '';

				for (var i = 0; i < _self.formToProtectOnPage.length; i++) {
					var form = _self.formToProtectOnPage[i].form;
					if (form.tagName.toLowerCase() != 'form') {
						continue;
					}

					id = form.querySelector("[type='submit']");
					if (_self.formToProtectOnPage[i].button) {
						id = form.querySelector('button' + _self.formToProtectOnPage[i].button);
					}

					(function (form, id) {
						if (_self.options.type == 'default') {
							grecaptcha.render('recaptcha-'+form.id, {
								'sitekey': _self.options.site_key,
								'callback': function () {
									form.querySelector('input[type=checkbox].required-captcha').setAttribute("checked", "checked");
								},
								'expired-callback': function () {
									form.querySelector('input[type=checkbox].required-captcha').removeAttribute("checked");
								}
							});
						} else {
							grecaptcha.render(id, {
								'sitekey': _self.options.site_key,
								'callback': function (token) {
									if ($(form).validation() && $(form).validation('isValid')) {
										form.querySelector("[name='magestorm_invisible_token']").setAttribute('value', token);
										form.submit();
									} else {
										grecaptcha.reset();
									}
								}
							});
						}
					})(form, id);
				}
			};
		}
	});

	return $.magestorm.recaptcha;
});