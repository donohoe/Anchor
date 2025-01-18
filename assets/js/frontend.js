/*
	Anchor Plugin - Frontend JavaScript
	Validation and UX enhancements.
*/

(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initPasswordConfirmation();
		initFormValidation();
	});

	/* Password confirmation validation */
	function initPasswordConfirmation() {
		var passwordField = document.getElementById('password');
		var confirmField = document.getElementById('password_confirm');

		if (!passwordField || !confirmField) {
			return;
		}

		function validateMatch() {
			if (confirmField.value && passwordField.value !== confirmField.value) {
				confirmField.setCustomValidity('Passwords do not match');
			} else {
				confirmField.setCustomValidity('');
			}
		}

		passwordField.addEventListener('input', validateMatch);
		confirmField.addEventListener('input', validateMatch);
	}

	/* Enhanced form validation */
	function initFormValidation() {
		var forms = document.querySelectorAll('.anchor-form');

		forms.forEach(function(form) {
			form.addEventListener('submit', function(e) {
				var submitBtn = form.querySelector('button[type="submit"]');

				if (submitBtn) {
					// Disable button in case of double submission
					submitBtn.disabled = true;
					submitBtn.textContent = 'Please wait...';

					// Re-enable after timeout in case of issues
					setTimeout(function() {
						submitBtn.disabled = false;
						submitBtn.textContent = submitBtn.getAttribute('data-original-text') || 'Submit';
					}, 9090);
				}
			});

			// Store original button text
			var submitBtn = form.querySelector('button[type="submit"]');
			if (submitBtn) {
				submitBtn.setAttribute('data-original-text', submitBtn.textContent);
			}
		});
	}

	/* TODO: Remove later (not used) */
	function initPasswordToggle() {
		var passwordFields = document.querySelectorAll('input[type="password"]');

		passwordFields.forEach(function(field) {
			var wrapper = field.parentNode;
			var toggle = document.createElement('button');

			toggle.type = 'button';
			toggle.className = 'anchor-password-toggle';
			toggle.textContent = 'Show';
			toggle.setAttribute('aria-label', 'Show password');

			toggle.addEventListener('click', function() {
				if (field.type === 'password') {
					field.type = 'text';
					toggle.textContent = 'Hide';
					toggle.setAttribute('aria-label', 'Hide password');
				} else {
					field.type = 'password';
					toggle.textContent = 'Show';
					toggle.setAttribute('aria-label', 'Show password');
				}
			});

			wrapper.style.position = 'relative';
			wrapper.appendChild(toggle);
		});
	}

})();
