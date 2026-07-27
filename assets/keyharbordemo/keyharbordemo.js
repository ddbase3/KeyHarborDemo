(function() {
	'use strict';

	var root = document.querySelector('[data-keyharbordemo]');
	if (!root) return;

	var serviceUrl = root.getAttribute('data-service-url') || '';
	var tokenInput = root.querySelector('[data-token]');
	var authMode = root.querySelector('[data-auth-mode]');
	var messageInput = root.querySelector('[data-message]');
	var resultNode = root.querySelector('[data-result]');
	var statusNode = root.querySelector('[data-status]');
	var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-action]'));

	function setBusy(busy) {
		buttons.forEach(function(button) {
			button.disabled = busy;
		});
	}

	function showResult(status, value) {
		statusNode.textContent = status;
		resultNode.textContent = JSON.stringify(value, null, 2);
	}

	function getToken() {
		var token = tokenInput.value.trim();
		if (!token) throw new Error('Paste a KeyHarbor token first.');
		return token;
	}

	function getHmacSecret(token) {
		var match = /^b3k_[a-f0-9]{20}_([A-Za-z0-9_-]{43})$/.exec(token);
		if (!match) throw new Error('The token does not match the KeyHarbor token format.');
		return match[1];
	}

	function bytesToHex(buffer) {
		return Array.prototype.map.call(new Uint8Array(buffer), function(byte) {
			return byte.toString(16).padStart(2, '0');
		}).join('');
	}

	function requireWebCrypto() {
		if (!window.crypto || !crypto.subtle) {
			throw new Error('HMAC testing requires Web Crypto in a secure browser context.');
		}
	}

	async function sha256Hex(value) {
		requireWebCrypto();
		var bytes = new TextEncoder().encode(value);
		var digest = await crypto.subtle.digest('SHA-256', bytes);
		return bytesToHex(digest);
	}

	async function hmacHex(secret, value) {
		requireWebCrypto();
		var key = await crypto.subtle.importKey(
			'raw',
			new TextEncoder().encode(secret),
			{name: 'HMAC', hash: 'SHA-256'},
			false,
			['sign']
		);
		var signature = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(value));
		return bytesToHex(signature);
	}

	function createNonce() {
		requireWebCrypto();
		if (crypto.randomUUID) return crypto.randomUUID();
		var bytes = crypto.getRandomValues(new Uint8Array(18));
		return bytesToHex(bytes);
	}

	function buildUrl(serviceId, action) {
		var url = new URL(serviceUrl, window.location.href);
		url.searchParams.set('service_id', serviceId);
		if (action === 'echo') {
			url.searchParams.set('message', messageInput.value);
		} else {
			url.searchParams.delete('message');
		}
		return url;
	}

	async function buildHmacHeaders(url, token, timestamp, nonce) {
		var secret = getHmacSecret(token);
		var bodyHash = await sha256Hex('');
		var canonical = [
			'GET',
			url.pathname,
			url.search.substring(1),
			String(timestamp),
			nonce,
			bodyHash
		].join('\n');
		var signature = await hmacHex(secret, canonical);

		return {
			'Authorization': 'Bearer ' + token,
			'X-BASE3-Timestamp': String(timestamp),
			'X-BASE3-Nonce': nonce,
			'X-BASE3-Signature': signature
		};
	}

	async function send(url, headers) {
		var response = await fetch(url.toString(), {
			method: 'GET',
			headers: headers,
			cache: 'no-store',
			credentials: 'same-origin'
		});
		var text = await response.text();
		var payload;
		try {
			payload = JSON.parse(text);
		} catch (error) {
			payload = {ok: false, error: 'Invalid JSON response', response_text: text};
		}
		return {http_status: response.status, response: payload};
	}

	async function callService(action, serviceId, options) {
		var token = getToken();
		var url = buildUrl(serviceId, action);
		var mode = authMode.value;
		var timestamp = options && options.timestamp ? options.timestamp : Math.floor(Date.now() / 1000);
		var nonce = options && options.nonce ? options.nonce : createNonce();
		var headers = {'Authorization': 'Bearer ' + token};

		if (mode === 'hmac') {
			headers = await buildHmacHeaders(url, token, timestamp, nonce);
		}

		return send(url, headers);
	}

	async function runAction(button) {
		var action = button.getAttribute('data-action') || '';
		if (action === 'toggle-token') {
			var visible = tokenInput.type === 'text';
			tokenInput.type = visible ? 'password' : 'text';
			button.textContent = visible ? 'Show' : 'Hide';
			return;
		}

		var serviceId = button.getAttribute('data-service-id') || '';
		setBusy(true);
		statusNode.textContent = 'Running';

		try {
			if (action === 'replay') {
				if (authMode.value !== 'hmac') throw new Error('Replay testing requires HMAC-SHA256 mode.');
				var timestamp = Math.floor(Date.now() / 1000);
				var nonce = createNonce();
				var first = await callService('ping', serviceId, {timestamp: timestamp, nonce: nonce});
				var second = await callService('ping', serviceId, {timestamp: timestamp, nonce: nonce});
				showResult('Replay pair completed', {first_request: first, replay_request: second});
				return;
			}

			if (action === 'stale') {
				if (authMode.value !== 'hmac') throw new Error('Timestamp testing requires HMAC-SHA256 mode.');
				var stale = await callService('ping', serviceId, {
					timestamp: Math.floor(Date.now() / 1000) - 7200,
					nonce: createNonce()
				});
				showResult('Stale timestamp completed', stale);
				return;
			}

			var result = await callService(action, serviceId, null);
			showResult('Request completed', result);
		} catch (error) {
			showResult('Request failed', {ok: false, error: error instanceof Error ? error.message : String(error)});
		} finally {
			setBusy(false);
		}
	}

	buttons.forEach(function(button) {
		button.addEventListener('click', function() {
			runAction(button);
		});
	});
})();
