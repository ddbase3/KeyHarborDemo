<?php declare(strict_types=1);

namespace KeyHarborDemo\Test\Provider;

use CredentialFoundation\Api\ICredentialServiceProvider;
use KeyHarborDemo\Provider\KeyHarborDemoServiceProvider;
use PHPUnit\Framework\TestCase;

final class KeyHarborDemoServiceProviderTest extends TestCase {

	public function testProvidesThreeIndependentServices(): void {
		$provider = new KeyHarborDemoServiceProvider();

		self::assertInstanceOf(ICredentialServiceProvider::class, $provider);
		self::assertSame('keyharbordemoserviceprovider', $provider::getName());
		self::assertSame(
			[
				KeyHarborDemoServiceProvider::SERVICE_PING,
				KeyHarborDemoServiceProvider::SERVICE_ECHO,
				KeyHarborDemoServiceProvider::SERVICE_REPORT
			],
			array_map(
				static fn($service): string => $service->getServiceId(),
				$provider->getServices()
			)
		);
	}
}
