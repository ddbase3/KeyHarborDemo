<?php declare(strict_types=1);

namespace KeyHarborDemo\Provider;

use CredentialFoundation\Api\ICredentialServiceProvider;
use CredentialFoundation\Dto\CredentialServiceDefinition;

/**
 * Exposes several credential-protected services from one provider class.
 */
final class KeyHarborDemoServiceProvider implements ICredentialServiceProvider {

	public const SERVICE_PING = 'keyharbordemo:ping';
	public const SERVICE_ECHO = 'keyharbordemo:echo';
	public const SERVICE_REPORT = 'keyharbordemo:report';

	public static function getName(): string {
		return 'keyharbordemoserviceprovider';
	}

	public function getServices(): array {
		return [
			new CredentialServiceDefinition(
				self::SERVICE_PING,
				'KeyHarbor Demo Ping',
				'Confirms that a credential is valid for the basic demo service.'
			),
			new CredentialServiceDefinition(
				self::SERVICE_ECHO,
				'KeyHarbor Demo Echo',
				'Returns one caller-supplied query value after credential authentication.'
			),
			new CredentialServiceDefinition(
				self::SERVICE_REPORT,
				'KeyHarbor Demo Report',
				'Returns a protected example report and demonstrates a separate service grant.'
			)
		];
	}
}
