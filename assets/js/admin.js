/*
	Anchor Admin
*/
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initDeleteConfirmation();
		initNoticesDismiss();
		initContentAccessForm();
	});

	function initDeleteConfirmation() {
		// Single member delete
		document.querySelectorAll('.anchor-delete-member').forEach(function(link) {
			link.addEventListener('click', function(e) {
				if (typeof anchorAdmin !== 'undefined' && anchorAdmin.i18n) {
					if (!confirm(anchorAdmin.i18n.confirmDelete)) {
						e.preventDefault();
					}
				}
			});
		});

		// Bulk actions - confirm delete
		var bulkForms = document.querySelectorAll('.anchor-admin form[method="post"]');
		bulkForms.forEach(function(form) {
			form.addEventListener('submit', function(e) {
				var actionSelect = form.querySelector('select[name="action"]');
				var actionSelect2 = form.querySelector('select[name="action2"]');

				var selectedAction = '';
				if (actionSelect && actionSelect.value !== '-1') {
					selectedAction = actionSelect.value;
				} else if (actionSelect2 && actionSelect2.value !== '-1') {
					selectedAction = actionSelect2.value;
				}

				if (selectedAction === 'delete') {
					var checkedBoxes = form.querySelectorAll('input[name="member[]"]:checked');
					if (checkedBoxes.length > 0) {
						if (typeof anchorAdmin !== 'undefined' && anchorAdmin.i18n) {
							if (!confirm(anchorAdmin.i18n.confirmBulkDelete)) {
								e.preventDefault();
							}
						}
					}
				}
			});
		});
	}

	function initNoticesDismiss() {
		document.querySelectorAll('.anchor-admin .notice.is-dismissible').forEach(function(notice) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'notice-dismiss';
			button.innerHTML = '<span class="screen-reader-text">Dismiss this notice.</span>';

			button.addEventListener('click', function() {
				notice.style.opacity = '0';
				notice.style.transition = 'opacity 0.2s ease';
				setTimeout(function() {
					notice.remove();
				}, 200);
			});

			notice.appendChild(button);
		});
	}

	function initContentAccessForm() {
		var form = document.getElementById('anchor-content-access-form');
		if (!form) return;

		// Elements
		var enableRegistered = document.getElementById('enable_registered');
		var enableSubscriber = document.getElementById('enable_subscriber');
		var enableMetering   = document.getElementById('enable_metering');
		var meterPeriodAnonymous = document.getElementById('meter_period_anonymous');
		var meterLimitRegistered = document.getElementById('meter_limit_registered');
		var enableTimeDecay = document.getElementById('enable_time_decay');

		// Sections
		var meteringSettings = document.getElementById('metering-settings');
		var weekEndDayField  = document.getElementById('week-end-day-field');
		var timezoneField    = document.getElementById('timezone-field');
		var registeredMeteringSection = document.getElementById('registered-metering-section');
		var registeredMeteringFields = document.getElementById('registered-metering-fields');
		var timeDecaySettings    = document.getElementById('time-decay-settings');
		var defaultAccessOptions = document.getElementById('default-access-options');
		var meterActionDropdown  = document.getElementById('meter_action_anonymous');

		// Access level toggle handlers
		if (enableRegistered) {
			enableRegistered.addEventListener('change', function() {
				updateAccessLevelDependencies();
			});
		}

		if (enableSubscriber) {
			enableSubscriber.addEventListener('change', function() {
				updateAccessLevelDependencies();
			});
		}

		// Metering toggle handler
		if (enableMetering) {
			enableMetering.addEventListener('change', function() {
				toggleElement(meteringSettings, this.checked);
			});
		}

		// Period change handler
		if (meterPeriodAnonymous) {
			meterPeriodAnonymous.addEventListener('change', function() {
				toggleElement(weekEndDayField, this.value === 'weekly');
				toggleElement(timezoneField, this.value === 'daily');
			});
		}

		// Registered metering toggle
		if (meterLimitRegistered) {
			meterLimitRegistered.addEventListener('change', function() {
				toggleElement(registeredMeteringFields, this.checked);
			});
		}

		// Time decay toggle
		if (enableTimeDecay) {
			enableTimeDecay.addEventListener('change', function() {
				toggleElement(timeDecaySettings, this.checked);
			});
		}

		// Exemption rules - add rule
		var addRuleBtn = document.getElementById('add-exemption-rule');
		var exemptionRulesContainer = document.getElementById('exemption-rules');
		var ruleTemplate = document.getElementById('exemption-rule-template');

		if (addRuleBtn && exemptionRulesContainer && ruleTemplate) {
			addRuleBtn.addEventListener('click', function() {
				var newRule = ruleTemplate.content.cloneNode(true);
				exemptionRulesContainer.appendChild(newRule);
				initRemoveRuleButtons();
			});
		}

		// Remove buttons
		initRemoveRuleButtons();

		/* Update access level dependencies */
		function updateAccessLevelDependencies() {
			var registeredEnabled = enableRegistered && enableRegistered.checked;
			var subscriberEnabled = enableSubscriber && enableSubscriber.checked;

			// Default access radio buttons
			if (defaultAccessOptions) {
				var registeredRadio = defaultAccessOptions.querySelector('input[value="registered"]');
				var subscriberRadio = defaultAccessOptions.querySelector('input[value="subscriber"]');

				if (registeredRadio) {
					registeredRadio.disabled = !registeredEnabled;
					if (!registeredEnabled && registeredRadio.checked) {
						var publicRadio = defaultAccessOptions.querySelector('input[value="public"]');
						if (publicRadio) publicRadio.checked = true;
					}
				}

				if (subscriberRadio) {
					subscriberRadio.disabled = !subscriberEnabled;
					if (!subscriberEnabled && subscriberRadio.checked) {
						var publicRadio = defaultAccessOptions.querySelector('input[value="public"]');
						if (publicRadio) publicRadio.checked = true;
					}
				}
			}

			// Meter action dropdown
			if (meterActionDropdown) {
				var subscriptionOption = meterActionDropdown.querySelector('option[value="require_subscription"]');
				if (subscriptionOption) {
					subscriptionOption.disabled = !subscriberEnabled;
					if (!subscriberEnabled && meterActionDropdown.value === 'require_subscription') {
						meterActionDropdown.value = 'require_registration';
					}
				}
			}

			// Show/hide registered metering section (when both are enabled)
			if (registeredMeteringSection) {
				toggleElement(registeredMeteringSection, registeredEnabled && subscriberEnabled);
			}
		}

		/* Toggle visibility */
		function toggleElement(el, show) {
			if (el) {
				el.style.display = show ? '' : 'none';
			}
		}

		/* Initialize remove rule buttons */
		function initRemoveRuleButtons() {
			document.querySelectorAll('.anchor-remove-rule').forEach(function(btn) {
				btn.removeEventListener('click', removeRule);
				btn.addEventListener('click', removeRule);
			});
		}

		/* Remove exemption rule */
		function removeRule(e) {
			var rule = e.target.closest('.anchor-exemption-rule');
			if (rule) {
				rule.remove();
			}
		}
	}
})();
