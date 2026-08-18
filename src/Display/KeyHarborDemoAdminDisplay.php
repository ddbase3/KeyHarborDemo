<?php declare(strict_types=1);

namespace KeyHarborDemo\Display;

use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Logger\Api\ILogger;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Permission;
use CredentialFoundation\Api\ICredentialAccess;
use CredentialFoundation\Dto\CredentialAuthenticationResult;
use KeyHarborDemo\Provider\KeyHarborDemoServiceProvider;
use Throwable;

/**
 * Provides an administrative test UI and credential-protected demo endpoint.
 */
final class KeyHarborDemoAdminDisplay implements IDisplay {

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver,
		private readonly ILinkTargetService $linkTargetService,
		private readonly ILogger $logger,
		private readonly IUsermanager $usermanager,
		private readonly IAccesscontrol $accesscontrol,
		private readonly ICredentialAccess $credentialAccess
	) {}

	public static function getName(): string {
		return 'keyharbordemoadmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if (strtolower($out) === 'json') {
			return $this->handleJson($final);
		}

		return $this->handleHtml();
	}

	public function getHelp(): string {
		$this->view->setPath(DIR_PLUGIN . 'KeyHarborDemo');
		$this->view->loadBricks('Display');
		$translations = $this->view->getBricks('keyharbor_demo_admin_display');
		$translations = is_array($translations) ? $translations : [];
		$help = trim((string)($translations['help'] ?? ''));
		return $help !== ''
			? $help
			: 'Tests CredentialFoundation bearer and HMAC authentication with several service grants.';
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'KeyHarborDemo');
		$this->view->loadBricks('Display');
		$translations = $this->view->getBricks('keyharbor_demo_admin_display');
		$translations = is_array($translations) ? $translations : [];

		$error = '';
		if (!$this->usermanager->can(Permission::for('system', 'admin'))) {
			$error = trim((string)($translations['permission_required'] ?? ''));
			if ($error === '') {
				$error = 'System administrator permission is required.';
			}
		}

		$provider = new KeyHarborDemoServiceProvider();

		$this->view->setTemplate('Display/KeyHarborDemoAdminDisplay.php');
		$this->view->assign('error', $error);
		$this->view->assign('translations', $translations);
		$this->view->assign(
			'serviceUrl',
			$this->linkTargetService->getLink([
				'name' => self::getName(),
				'out' => 'json'
			])
		);
		$this->view->assign(
			'services',
			array_map(
				static fn($service): array => $service->toArray(),
				$provider->getServices()
			)
		);
		$this->view->assign(
			'cssUrl',
			$this->assetResolver->resolve('plugin/KeyHarborDemo/assets/keyharbordemo/keyharbordemo.css')
		);
		$this->view->assign(
			'scriptUrl',
			$this->assetResolver->resolve('plugin/KeyHarborDemo/assets/keyharbordemo/keyharbordemo.js')
		);

		return $this->view->loadTemplate();
	}

	private function handleJson(bool $final): string {
		try {
			$serviceId = trim((string)$this->request->get('service_id', ''));
			$authorization = $this->credentialAccess->authorizeService($serviceId);

			if ($authorization->isAuthenticated()) {
				$userId = $this->accesscontrol->getUserId();
				$response = $this->buildSuccessResponse($serviceId, $userId, $authorization);
				$statusCode = 200;
			} else {
				$response = [
					'ok' => false,
					'authentication' => $authorization->toArray()
				];
				$statusCode = $this->getFailureStatusCode($authorization);
			}
		} catch (Throwable $throwable) {
			$this->logger->error('KeyHarborDemo request failed.', [
				'exception' => $throwable::class,
				'message' => $throwable->getMessage()
			]);
			$response = [
				'ok' => false,
				'error' => 'KeyHarborDemo request failed.'
			];
			$statusCode = 500;
		}

		if ($final && !headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code($statusCode);
		}

		return (string)json_encode(
			$response,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildSuccessResponse(
		string $serviceId,
		int|string|null $userId,
		CredentialAuthenticationResult $authorization
	): array {
		$payload = match ($serviceId) {
			KeyHarborDemoServiceProvider::SERVICE_PING => [
				'message' => 'pong',
				'server_time' => time()
			],
			KeyHarborDemoServiceProvider::SERVICE_ECHO => [
				'message' => (string)$this->request->get('message', '')
			],
			KeyHarborDemoServiceProvider::SERVICE_REPORT => [
				'report' => [
					'generated_at' => date('c'),
					'open_items' => 4,
					'completed_items' => 17,
					'completion_rate' => 80.95
				]
			],
			default => []
		};

		$data = $authorization->toArray();
		$data['user_id'] = $userId;

		return [
			'ok' => true,
			'authentication' => $data,
			'payload' => $payload
		];
	}

	private function getFailureStatusCode(CredentialAuthenticationResult $authorization): int {
		return in_array($authorization->getFailureCode(), [
			CredentialAuthenticationResult::FAILURE_SERVICE_NOT_FOUND,
			CredentialAuthenticationResult::FAILURE_SERVICE_NOT_GRANTED
		], true) ? 403 : 401;
	}
}
