<?php
$error = (string)($this->_['error'] ?? '');
$serviceUrl = (string)($this->_['serviceUrl'] ?? '');
$services = is_array($this->_['services'] ?? null) ? $this->_['services'] : [];
$cssUrl = (string)($this->_['cssUrl'] ?? '');
$scriptUrl = (string)($this->_['scriptUrl'] ?? '');
$translations = is_array($this->_['translations'] ?? null) ? $this->_['translations'] : [];
$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$t = static fn(string $key, string $fallback): string => trim((string)($translations[$key] ?? '')) !== ''
	? (string)$translations[$key]
	: $fallback;
$credentialNavigationLabel = '<strong>' . $e($t('credential_navigation_label', 'My API Keys')) . '</strong>';
$credentialHelp = str_replace(
	'%s',
	$credentialNavigationLabel,
	$e($t(
		'credential_help',
		'Create a credential under %s, grant one or more demo services, and paste the one-time token here. The token stays only in this browser field.'
	))
);
$initialResult = [
	'message' => $t('initial_result_message', 'Run a demo request.')
];
?>
<link rel="stylesheet" href="<?php echo $e($cssUrl); ?>">

<div
	class="keyharbordemo-shell"
	data-keyharbordemo
	data-service-url="<?php echo $e($serviceUrl); ?>"
>
	<header class="keyharbordemo-header">
		<div>
			<p class="keyharbordemo-eyebrow"><?php echo $e($t('eyebrow', 'CredentialFoundation reference consumer')); ?></p>
			<h1>KeyHarbor Demo</h1>
			<p><?php echo $e($t('lead', 'Test real bearer and HMAC requests against three separately grantable services. Credential grants come from CredentialFoundation; the request user is resolved through BASE3 accesscontrol.')); ?></p>
		</div>
	</header>

	<?php if ($error !== ''): ?>
		<div class="keyharbordemo-alert keyharbordemo-alert-error">
			<?php echo $e($error); ?>
		</div>
	<?php else: ?>
		<section class="keyharbordemo-panel">
			<h2><?php echo $e($t('credential_title', 'Credential')); ?></h2>
			<p><?php echo $credentialHelp; ?></p>

			<div class="keyharbordemo-form-grid">
				<label for="keyharbordemo-token"><?php echo $e($t('token', 'Token')); ?></label>
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
						<?php echo $e($t('show', 'Show')); ?>
					</button>
				</div>

				<label for="keyharbordemo-mode"><?php echo $e($t('authentication', 'Authentication')); ?></label>
				<select id="keyharbordemo-mode" data-auth-mode>
					<option value="bearer"><?php echo $e($t('authentication_bearer', 'Bearer')); ?></option>
					<option value="hmac"><?php echo $e($t('authentication_hmac', 'HMAC-SHA256')); ?></option>
				</select>

				<label for="keyharbordemo-message"><?php echo $e($t('echo_message', 'Echo message')); ?></label>
				<input
					id="keyharbordemo-message"
					type="text"
					value="<?php echo $e($t('echo_default', 'Hello from KeyHarborDemo')); ?>"
					data-message
				>
			</div>
		</section>

		<section class="keyharbordemo-panel">
			<div class="keyharbordemo-section-heading">
				<div>
					<h2><?php echo $e($t('protected_services_title', 'Protected services')); ?></h2>
					<p><?php echo $e($t('protected_services_help', 'Each request is checked against its own service grant.')); ?></p>
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
							<h3><?php echo $e($label); ?></h3>
							<code><?php echo $e($serviceId); ?></code>
							<p><?php echo $e($description); ?></p>
						</div>
						<button
							class="keyharbordemo-button keyharbordemo-button-primary"
							type="button"
							data-action="<?php echo $e($action); ?>"
							data-service-id="<?php echo $e($serviceId); ?>"
						>
							<?php echo $e($t('call_service', 'Call service')); ?>
						</button>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="keyharbordemo-panel">
			<h2><?php echo $e($t('hmac_tests_title', 'HMAC failure tests')); ?></h2>
			<p><?php echo $e($t('hmac_tests_help', 'These actions deliberately reuse a nonce or send an old timestamp.')); ?></p>
			<div class="keyharbordemo-actions">
				<button
					class="keyharbordemo-button keyharbordemo-button-secondary"
					type="button"
					data-action="replay"
					data-service-id="keyharbordemo:ping"
				>
					<?php echo $e($t('send_replay', 'Send replay pair')); ?>
				</button>
				<button
					class="keyharbordemo-button keyharbordemo-button-secondary"
					type="button"
					data-action="stale"
					data-service-id="keyharbordemo:ping"
				>
					<?php echo $e($t('send_stale', 'Send stale timestamp')); ?>
				</button>
			</div>
			<p class="keyharbordemo-hint"><?php echo $e($t('hmac_hint', 'Select HMAC-SHA256 and use an HMAC credential for these tests.')); ?></p>
		</section>

		<section class="keyharbordemo-result-panel" aria-live="polite">
			<div class="keyharbordemo-result-header">
				<h2><?php echo $e($t('result', 'Result')); ?></h2>
				<span data-status><?php echo $e($t('ready', 'Ready')); ?></span>
			</div>
			<pre data-result><?php echo $e((string)json_encode($initialResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)); ?></pre>
		</section>
	<?php endif; ?>
</div>

<script src="<?php echo $e($scriptUrl); ?>"></script>
