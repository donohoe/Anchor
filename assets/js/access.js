/*
	Anchor Access Control & Metering
	Client-side access evaluation with all decisions made in browser
*/
(function() {
	'use strict';

	var STORAGE_KEY = '_a_m';

	var ACCESS_HIERARCHY = {
		anonymous: 0,
		public: 0,
		registered: 1,
		subscriber: 2
	};

	function init() {
		var accessData = getAccessData();
		var config = window.AnchorConfig;
		var member = window.AnchorMember || null;

		if (!accessData || !config) {
			return;
		}

		// log basic init info for testing
		console.log('[Anchor] Init', {
			postId: accessData.postId,
			accessRequired: accessData.accessRequired,
			countsTowardMeter: accessData.countsTowardMeter,
			meteringEnabled: config.metering.enabled
		});

		debug('Full access data:', accessData);
		debug('Full config:', config);
		debug('Member:', member);

		// Determine user access level
		var userLevel = getUserAccessLevel(member);
		debug('User access level:', userLevel);

		// Evaluate access
		var decision = canAccessContent(accessData, userLevel, config);

		// Always log decision summary
		console.log('[Anchor] Decision:', decision.reason, decision.access ? '✓' : '✗',
			decision.remaining !== undefined ? '(' + decision.remaining + ' remaining)' : '');

		// Apply decision
		applyAccessDecision(decision, config);

		// Expose debug interface
		if (config.debug) {
			window.AnchorDebug = {
				accessData: accessData,
				config: config,
				member: member,
				userLevel: userLevel,
				decision: decision,
				meter: loadMeter(),
				resetMeter: function() {
					localStorage.removeItem(STORAGE_KEY);
					location.reload();
				}
			};
		}
	}

	/* Get per-page access data from inline JSON */
	function getAccessData() {
		var el = document.getElementById('anchor-access-data');
		if (!el) return null;

		try {
			return JSON.parse(el.textContent);
		} catch (e) {
			debug('Failed to parse access data:', e);
			return null;
		}
	}

	/* Get user's access level from member info */
	function getUserAccessLevel(member) {
		if (!member || !member.memberId) {
			return 'anonymous';
		}
		return member.accessLevel || 'registered';
	}

	/*
		Main access evaluation function.
		Order: subscriber bypass > time decay > metering > access level > deny */
	function canAccessContent(page, userLevel, config) {
		debug('Evaluating access for user:', userLevel);

		// 1. Subscribers always bypass everything
		if (userLevel === 'subscriber') {
			debug('Access granted: subscriber');
			return { access: true, reason: 'authorized' };
		}

		// 2. Time decay override - old content becomes public
		if (config.timeDecay.enabled && isTimeDecayed(page, config)) {
			console.log('[Anchor] Time-decayed: article older than', config.timeDecay.days, 'days');
			return { access: true, reason: 'time-decayed' };
		}

		// 3. Metering for anonymous/registered users (soft paywall)
		// Apply metering BEFORE access level check for soft paywall behavior
		if (shouldApplyMetering(userLevel, config) && page.countsTowardMeter) {
			debug('Applying metering...');
			return checkMeter(page, userLevel, config);
		}

		// 4. Access level authorization
		if (meetsRequirement(userLevel, page.accessRequired)) {
			return { access: true, reason: 'authorized' };
		}

		// 5. Deny with reason
		return {
			access: false,
			reason: 'insufficient-access',
			required: page.accessRequired,
			action: getActionForUser(userLevel, config)
		};
	}

	/* Check if content is time-decayed (old enough to be public) */
	function isTimeDecayed(page, config) {
		if (!page.publishedDate || !config.timeDecay.days) {
			return false;
		}

		var published = new Date(page.publishedDate);
		var now = new Date();
		var diffDays = Math.floor((now - published) / (1000 * 60 * 60 * 24));

		return diffDays >= config.timeDecay.days;
	}

	/* Check if user meets access requirement */
	function meetsRequirement(userLevel, required) {
		var userRank = ACCESS_HIERARCHY[userLevel] || 0;
		var requiredRank = ACCESS_HIERARCHY[required] || 0;
		return userRank >= requiredRank;
	}

	/*
		Check if metering should apply to this user.
		Metering acts as a soft paywall - limits views before requiring action */
	function shouldApplyMetering(userLevel, config) {
		if (!config.metering.enabled) {
			return false;
		}

		// Subscribers bypass metering entirely (already checked in main flow, but defensive)
		if (userLevel === 'subscriber') {
			return false;
		}

		// Anonymous users - always metered if metering is enabled
		if (userLevel === 'anonymous') {
			return true;
		}

		// Registered users - only if registered metering is enabled
		if (userLevel === 'registered') {
			return config.metering.registeredEnabled;
		}

		return false;
	}

	/* Check meter and determine access */
	function checkMeter(page, userLevel, config) {
		debug('checkMeter for post:', page.postId);

		// Post doesn't count toward meter (exempt)
		if (!page.countsTowardMeter) {
			debug('Post is exempt from metering');
			return { access: true, reason: 'exempt' };
		}

		// Load or initialize meter
		var meter = loadMeter();
		debug('Loaded meter:', meter);
		if (!meter) {
			meter = initializeMeter(userLevel, config);
			debug('Initialized new meter:', meter);
		}

		// Check if period expired
		if (isPeriodExpired(meter)) {
			debug('Period expired, resetting meter');
			meter = resetMeter(userLevel, config);
			debug('Meter reset to:', meter);
		}

		// Already viewed this post
		if (meter.viewedPosts.indexOf(page.postId) !== -1) {
			debug('Post already counted, not incrementing');
			return { access: true, reason: 'already-counted' };
		}

		// Get limit for user type
		var limit = getLimitForUser(userLevel, config);

		// Always log meter state
		console.log('[Anchor] Meter:', meter.count + '/' + limit, 'period:', meter.periodStart, 'to', meter.periodEnd);
		debug('Meter viewedPosts:', meter.viewedPosts);

		// Under limit
		if (meter.count < limit) {
			incrementMeter(page.postId, meter);
			debug('Meter incremented to:', meter.count);
			return {
				access: true,
				reason: 'metered',
				remaining: limit - meter.count,
				limit: limit,
				count: meter.count
			};
		}

		// Limit reached
		debug('Meter limit reached - blocking content');
		return {
			access: false,
			reason: 'meter-limit',
			action: getActionForUser(userLevel, config),
			limit: limit,
			count: meter.count
		};
	}

	/* Load meter from localStorage */
	function loadMeter() {
		try {
			var raw = localStorage.getItem(STORAGE_KEY);
			if (!raw) return null;

			// Decode if not debug mode
			var config = window.AnchorConfig || {};
			var data;
			if (config.debug) {
				data = JSON.parse(raw);
			} else {
				data = JSON.parse(atob(raw));
			}

			return data;
		} catch (e) {
			debug('Failed to load meter:', e);
			return null;
		}
	}

	/* Save meter to localStorage */
	function saveMeter(meter) {
		try {
			meter.lastUpdated = new Date().toISOString();

			var config = window.AnchorConfig || {};
			var data;
			if (config.debug) {
				data = JSON.stringify(meter);
			} else {
				data = btoa(JSON.stringify(meter));
			}

			localStorage.setItem(STORAGE_KEY, data);
		} catch (e) {
			debug('Failed to save meter:', e);
		}
	}

	/* Initialize a new meter */
	function initializeMeter(userLevel, config) {
		var period = getPeriodForUser(userLevel, config);
		var bounds = getPeriodBounds(period);

		var meter = {
			count: 0,
			periodStart: bounds.start,
			periodEnd: bounds.end,
			viewedPosts: [],
			lastUpdated: new Date().toISOString()
		};

		saveMeter(meter);
		return meter;
	}

	/* Reset meter for new period */
	function resetMeter(userLevel, config) {
		debug('Resetting meter for new period');
		return initializeMeter(userLevel, config);
	}

	/* Check if meter period has expired */
	function isPeriodExpired(meter) {
		if (!meter.periodEnd) return true;

		// Parse periodEnd as local date (YYYY-MM-DD format)
		var parts = meter.periodEnd.split('-');
		var end = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));

		// Compare with today's local date at midnight
		var now = new Date();
		var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

		var expired = today >= end;

		if (expired) {
			console.log('[Anchor] Period expired - resetting meter');
		}
		debug('Period check - today:', today.toDateString(), 'end:', end.toDateString(), 'expired:', expired);

		return expired;
	}

	/* Increment meter for a post */
	function incrementMeter(postId, meter) {
		meter.count++;
		meter.viewedPosts.push(postId);
		saveMeter(meter);
		debug('Meter incremented:', meter.count);
	}

	/* Get limit for user type */
	function getLimitForUser(userLevel, config) {
		if (userLevel === 'registered') {
			return config.metering.registeredLimit;
		}
		return config.metering.anonymousLimit;
	}

	/* Get period for user type */
	function getPeriodForUser(userLevel, config) {
		if (userLevel === 'registered') {
			return config.metering.registeredPeriod;
		}
		return config.metering.anonymousPeriod;
	}

	/* Get action for user when access is denied */
	function getActionForUser(userLevel, config) {
		if (userLevel === 'anonymous') {
			return config.metering.anonymousAction;
		}
		// Registered user who hit meter limit
		return 'require_subscription';
	}

	/* Calculate period boundaries */
	function getPeriodBounds(period) {
		var now = new Date();
		var start, end;

		if (period === 'daily') {
			start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
			end = new Date(start);
			end.setDate(end.getDate() + 1);
		} else if (period === 'weekly') {
			// Start of week (Sunday)
			var day = now.getDay();
			start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - day);
			end = new Date(start);
			end.setDate(end.getDate() + 7);
		} else {
			// Monthly (default)
			start = new Date(now.getFullYear(), now.getMonth(), 1);
			end = new Date(now.getFullYear(), now.getMonth() + 1, 1);
		}

		// Format as YYYY-MM-DD in local time (no UTC conversion)
		function formatLocalDate(d) {
			var year = d.getFullYear();
			var month = d.getMonth() + 1;
			var day = d.getDate();
			// Pad with leading zeros
			month = month < 10 ? '0' + month : '' + month;
			day = day < 10 ? '0' + day : '' + day;
			return year + '-' + month + '-' + day;
		}

		return {
			start: formatLocalDate(start),
			end: formatLocalDate(end)
		};
	}

	/* Apply access decision to the page */
	function applyAccessDecision(decision, config) {
		var content = document.querySelector('.anchor-content');
		var paywall = document.querySelector('.anchor-paywall');

		if (!content) {
			debug('No content wrapper found');
			return;
		}

		if (decision.access) {
			// Access granted - show remaining if metered
			if (decision.reason === 'metered' && decision.remaining !== undefined) {
				showMeterNotice(decision, config);
			}
			return;
		}

		// Access denied - block content and show paywall
		content.classList.add('anchor-blocked');

		if (paywall) {
			paywall.style.display = '';
			renderPaywall(paywall, decision, config);
		}
	}

	/* Show meter notice for metered access */
	function showMeterNotice(decision, config) {
		var content = document.querySelector('.anchor-content');
		if (!content) return;

		var notice = document.createElement('div');
		notice.className = 'anchor-meter-notice';

		var remaining = decision.remaining;
		var message;

		if (remaining === 0) {
			message = 'This is your last free article this period.';
		} else if (remaining === 1) {
			message = 'You have 1 free article remaining.';
		} else {
			message = 'You have ' + remaining + ' free articles remaining.';
		}

		notice.innerHTML = '<p>' + message + '</p>';
		content.parentNode.insertBefore(notice, content);
	}

	/* Render paywall content */
	function renderPaywall(container, decision, config) {
		var action = decision.action;
		var html = '';

		html += '<div class="anchor-paywall-inner">';

		if (decision.reason === 'meter-limit') {
			html += '<h3>You\'ve reached your article limit</h3>';
			html += '<p>You\'ve read all your free articles for this period.</p>';
		} else {
			html += '<h3>This content requires access</h3>';
		}

		if (action === 'require_registration') {
			html += '<p>Create a free account to continue reading.</p>';
			html += '<div class="anchor-paywall-actions">';
			html += '<a href="' + escapeHtml(config.urls.register) + '" class="anchor-button anchor-button-primary">Create Free Account</a>';
			html += '<a href="' + escapeHtml(config.urls.login) + '" class="anchor-button anchor-button-secondary">Sign In</a>';
			html += '</div>';
		} else if (action === 'require_subscription') {
			html += '<p>Subscribe to get unlimited access.</p>';
			html += '<div class="anchor-paywall-actions">';
			html += '<a href="' + escapeHtml(config.urls.register) + '" class="anchor-button anchor-button-primary">Subscribe</a>';
			html += '<a href="' + escapeHtml(config.urls.login) + '" class="anchor-button anchor-button-secondary">Sign In</a>';
			html += '</div>';
		}

		html += '</div>';

		container.innerHTML = html;
	}

	/* Escape HTML entities */
	function escapeHtml(str) {
		var div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	/* Debug logging */
	function debug() {
		var config = window.AnchorConfig;
		if (config && config.debug) {
			console.log.apply(console, ['[Anchor]'].concat(Array.prototype.slice.call(arguments)));
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
