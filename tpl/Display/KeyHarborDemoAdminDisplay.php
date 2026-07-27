<?php
$error = (string)($this->_['error'] ?? '');
$serviceUrl = (string)($this->_['serviceUrl'] ?? '');
$services = is_array($this->_['services'] ?? null) ? $this->_['services'] : [];
$cssUrl = (string)($this->_['cssUrl'] ?? '');
$scriptUrl = (string)($this->_['scriptUrl'] ?? '');
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($cssUrl); ?>">

<div
	class="keyharbordemo-shell"
	data-keyharbordemo
	data-service-url="<?php echo htmlspecialchars($serviceUrl); ?>"
>
	<header class="keyharbordemo-header">
		<div>
			<p class="keyharbordemo-eyebrow">CredentialFoundation reference consumer</p>
			<h1>KeyHarbor Demo</h1>
			<p>
				Test real bearer and HMAC requests against three separately grantable services.
				Credential grants come from CredentialFoundation; the request user is resolved through BASE3 accesscontrol.
			</p>
		</div>
	</header>

	<?php if ($error !== ''): ?>
		<div class="keyharbordemo-alert keyharbordemo-alert-error">
			<?php echo htmlspecialchars($error); ?>
		</div>
	<?php else: ?>
		<section class="keyharbordemo-panel">
			<h2>Credential</h2>
			<p>
				Create a credential under <strong>My API Keys</strong>, grant one or more demo services,
				and paste the one-time token here. The token stays only in this browser field.
			</p>

			<div class="keyharbordemo-form-grid">
				<label for="keyharbordemo-token">Token</label>
				<div class="keyharbordemo-token-row">
					<input
						id="keyharbordemo-token"
						type="password"
						autocomplete="off"
						spellcheck="false"
						data-token
						placeholder="b3k_..."
					>
					<button class="keyharbordemo-button keyharbordemo-button-secondary" type="button" data-action="toggle-token">
						Show
					</button>
				</div>

				<label for="keyharbordemo-mode">Authentication</label>
				<select id="keyharbordemo-mode" data-auth-mode>
					<option value="bearer">Bearer</option>
					<option value="hmac">HMAC-SHA256</option>
				</select>

				<label for="keyharbordemo-message">Echo message</label>
				<input
					id="keyharbordemo-message"
					type="text"
					value="Hello from KeyHarborDemo"
					data-message
				>
			</div>
		</section>

		<section class="keyharbordemo-panel">
			<div class="keyharbordemo-section-heading">
				<div>
					<h2>Protected services</h2>
					<p>Each request is checked against its own service grant.</p>
				</div>
			</div>

			<div class="keyharbordemo-service-grid">
				<?php foreach ($services as $service): ?>
					<?php
					$serviceId = (string)($service['service_id'] ?? '');
					$label = (string)($service['label'] ?? $serviceId);
					$description = (string)($service['description'] ?? '');
					$action = str_ends_with($serviceId, ':ping') ? 'ping' : (str_ends_with($serviceId, ':echo') ? 'echo' : 'report');
					?>
					<article class="keyharbordemo-service-card">
						<div>
							<h3><?php echo htmlspecialchars($label); ?></h3>
							<code><?php echo htmlspecialchars($serviceId); ?></code>
							<p><?php echo htmlspecialchars($description); ?></p>
						</div>
						<button
							class="keyharbordemo-button keyharbordemo-button-primary"
							type="button"
							data-action="<?php echo htmlspecialchars($action); ?>"
							data-service-id="<?php echo htmlspecialchars($serviceId); ?>"
						>
							Call service
						</button>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="keyharbordemo-panel">
			<h2>HMAC failure tests</h2>
			<p>These actions deliberately reuse a nonce or send an old timestamp.</p>
			<div class="keyharbordemo-actions">
				<button
					class="keyharbordemo-button keyharbordemo-button-secondary"
					type="button"
					data-action="replay"
					data-service-id="keyharbordemo:ping"
				>
					Send replay pair
				</button>
				<button
					class="keyharbordemo-button keyharbordemo-button-secondary"
					type="button"
					data-action="stale"
					data-service-id="keyharbordemo:ping"
				>
					Send stale timestamp
				</button>
			</div>
			<p class="keyharbordemo-hint">Select HMAC-SHA256 and use an HMAC credential for these tests.</p>
		</section>

		<section class="keyharbordemo-result-panel" aria-live="polite">
			<div class="keyharbordemo-result-header">
				<h2>Result</h2>
				<span data-status>Ready</span>
			</div>
			<pre data-result>{
  "message": "Run a demo request."
}</pre>
		</section>
	<?php endif; ?>
</div>

<script src="<?php echo htmlspecialchars($scriptUrl); ?>"></script>
