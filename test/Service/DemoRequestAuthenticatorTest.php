<?php declare(strict_types=1);

namespace KeyHarborDemo\Test\Service;

use Base3\Api\IRequest;
use CredentialFoundation\Api\IApiCredentialService;
use CredentialFoundation\Dto\CredentialAuthenticationResult;
use CredentialFoundation\Dto\HmacAuthenticationRequest;
use KeyHarborDemo\Service\DemoRequestAuthenticator;
use PHPUnit\Framework\TestCase;

final class DemoRequestAuthenticatorTest extends TestCase {

	public function testDelegatesBearerRequest(): void {
		$request = new DemoRequest([
			'HTTP_AUTHORIZATION' => 'Bearer demo-token'
		]);
		$service = new RecordingCredentialService();
		$authenticator = new DemoRequestAuthenticator($request, $service);

		$result = $authenticator->authenticate('keyharbordemo:ping');

		self::assertTrue($result->isAuthenticated());
		self::assertSame('demo-token', $service->bearerToken);
		self::assertSame('keyharbordemo:ping', $service->serviceId);
		self::assertNull($service->hmacRequest);
	}

	public function testBuildsHmacRequestFromHttpHeaders(): void {
		$request = new DemoRequest([
			'HTTP_AUTHORIZATION' => 'Bearer demo-token',
			'HTTP_X_BASE3_TIMESTAMP' => '1785140000',
			'HTTP_X_BASE3_NONCE' => 'demo-nonce',
			'HTTP_X_BASE3_SIGNATURE' => str_repeat('a', 64),
			'REQUEST_METHOD' => 'get',
			'REQUEST_URI' => '/index.php?name=demo&service_id=keyharbordemo%3Aping',
			'QUERY_STRING' => 'name=demo&service_id=keyharbordemo%3Aping'
		]);
		$service = new RecordingCredentialService();
		$authenticator = new DemoRequestAuthenticator($request, $service);

		$result = $authenticator->authenticate('keyharbordemo:ping');

		self::assertTrue($result->isAuthenticated());
		self::assertInstanceOf(HmacAuthenticationRequest::class, $service->hmacRequest);
		self::assertSame('GET', $service->hmacRequest->getMethod());
		self::assertSame('/index.php', $service->hmacRequest->getPath());
		self::assertSame('name=demo&service_id=keyharbordemo%3Aping', $service->hmacRequest->getQueryString());
		self::assertSame(1785140000, $service->hmacRequest->getTimestamp());
		self::assertSame('', $service->hmacRequest->getBody());
	}

	public function testRejectsMalformedAuthorizationBeforeServiceCall(): void {
		$request = new DemoRequest([
			'HTTP_AUTHORIZATION' => 'Basic demo-token'
		]);
		$service = new RecordingCredentialService();
		$authenticator = new DemoRequestAuthenticator($request, $service);

		$result = $authenticator->authenticate('keyharbordemo:ping');

		self::assertFalse($result->isAuthenticated());
		self::assertSame(
			CredentialAuthenticationResult::FAILURE_MALFORMED_CREDENTIAL,
			$result->getFailureCode()
		);
		self::assertNull($service->bearerToken);
		self::assertNull($service->hmacRequest);
	}

	public function testRejectsIncompleteHmacHeaders(): void {
		$request = new DemoRequest([
			'HTTP_AUTHORIZATION' => 'Bearer demo-token',
			'HTTP_X_BASE3_TIMESTAMP' => 'invalid',
			'HTTP_X_BASE3_NONCE' => 'demo-nonce'
		]);
		$service = new RecordingCredentialService();
		$authenticator = new DemoRequestAuthenticator($request, $service);

		$result = $authenticator->authenticate('keyharbordemo:ping');

		self::assertFalse($result->isAuthenticated());
		self::assertSame(
			CredentialAuthenticationResult::FAILURE_INVALID_TIMESTAMP,
			$result->getFailureCode()
		);
		self::assertNull($service->hmacRequest);
	}
}

final class RecordingCredentialService implements IApiCredentialService {

	public ?string $bearerToken = null;
	public ?HmacAuthenticationRequest $hmacRequest = null;
	public string $serviceId = '';

	public function authenticateBearer(
		string $token,
		string $serviceId
	): CredentialAuthenticationResult {
		$this->bearerToken = $token;
		$this->serviceId = $serviceId;

		return CredentialAuthenticationResult::success('credential-id', 7, $serviceId);
	}

	public function authenticateHmac(
		HmacAuthenticationRequest $request,
		string $serviceId
	): CredentialAuthenticationResult {
		$this->hmacRequest = $request;
		$this->serviceId = $serviceId;

		return CredentialAuthenticationResult::success('credential-id', 7, $serviceId);
	}
}

final class DemoRequest implements IRequest {

	/**
	 * @param array<string,mixed> $server
	 */
	public function __construct(
		private readonly array $server = []
	) {}

	public function get(string $key, $default = null) {
		return $default;
	}

	public function post(string $key, $default = null) {
		return $default;
	}

	public function request(string $key, $default = null) {
		return $default;
	}

	public function allRequest(): array {
		return [];
	}

	public function cookie(string $key, $default = null) {
		return $default;
	}

	public function session(string $key, $default = null) {
		return $default;
	}

	public function server(string $key, $default = null) {
		return $this->server[$key] ?? $default;
	}

	public function files(string $key, $default = null) {
		return $default;
	}

	public function allGet(): array {
		return [];
	}

	public function allPost(): array {
		return [];
	}

	public function allCookie(): array {
		return [];
	}

	public function allSession(): array {
		return [];
	}

	public function allServer(): array {
		return $this->server;
	}

	public function allFiles(): array {
		return [];
	}

	public function getJsonBody(): array {
		return [];
	}

	public function isCli(): bool {
		return false;
	}

	public function getContext(): string {
		return self::CONTEXT_WEB_API;
	}
}
