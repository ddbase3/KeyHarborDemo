<?php declare(strict_types=1);

namespace KeyHarborDemo\Test\Service;

use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Logger\Api\ILogger;
use Base3\Usermanager\Api\IUsermanager;
use CredentialFoundation\Api\ICredentialAccess;
use CredentialFoundation\Dto\CredentialAuthenticationResult;
use KeyHarborDemo\Display\KeyHarborDemoAdminDisplay;
use KeyHarborDemo\Provider\KeyHarborDemoServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the current access-control flow used by the demo endpoint.
 */
final class KeyHarborDemoCredentialAccessTest extends TestCase {

	public function testUsesRequestIdentityAndAuthorizesOnlyTheSelectedService(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturnCallback(
			static fn(string $key, mixed $default = null): mixed => $key === 'service_id'
				? KeyHarborDemoServiceProvider::SERVICE_PING
				: $default
		);

		$credentialAccess = $this->createMock(ICredentialAccess::class);
		$credentialAccess->expects(self::once())
			->method('authorizeService')
			->with(KeyHarborDemoServiceProvider::SERVICE_PING)
			->willReturn(CredentialAuthenticationResult::success(
				'credential-id',
				42,
				KeyHarborDemoServiceProvider::SERVICE_PING
			));

		$accesscontrol = $this->createMock(IAccesscontrol::class);
		$accesscontrol->method('getUserId')->willReturn(42);

		$display = new KeyHarborDemoAdminDisplay(
			$request,
			$this->createStub(IMvcView::class),
			$this->createStub(IAssetResolver::class),
			$this->createStub(ILinkTargetService::class),
			$this->createStub(ILogger::class),
			$this->createStub(IUsermanager::class),
			$accesscontrol,
			$credentialAccess
		);

		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);

		self::assertTrue($response['ok']);
		self::assertSame(42, $response['authentication']['user_id']);
		self::assertSame('pong', $response['payload']['message']);
	}
}
