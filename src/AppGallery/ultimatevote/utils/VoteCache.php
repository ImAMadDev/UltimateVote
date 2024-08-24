<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\utils;

use AppGallery\ultimatevote\VotePlugin;

final class VoteCache {

	private static array $topCache = [];
	private static string $information = '';

	private function __construct() {}

	public static function getTopCache(): array {
		return self::$topCache;
	}

	public static function setTopCache(array $topCache): void {
		self::$topCache = $topCache;

		VotePlugin::getInstance()->getHologram()->parse($topCache);
	}

	public static function getInformation(): string {
		return self::$information;
	}

}