<?php declare(strict_types=1);

namespace KeyHarborDemo;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;

final class KeyHarborDemoPlugin implements IPlugin {

	public function __construct(
		private readonly IContainer $container
	) {}

	public static function getName(): string {
		return 'keyharbordemoplugin';
	}

	public function init() {
		$this->container->set(
			self::getName(),
			$this,
			IContainer::SHARED
		);
	}
}
