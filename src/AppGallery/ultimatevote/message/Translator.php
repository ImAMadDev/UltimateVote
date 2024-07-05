<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\message;

use AppGallery\ultimatevote\Loader;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

final class Translator {
	use SingletonTrait;

	private const ERROR_MESSAGE_NOT_FOUND = 'Error: message not found - ';

	private array $messages = [];

	public function onEnable(Loader $plugin): void{
		$configPath = $plugin->getDataFolder() . Loader::CONFIG_MESSAGES;
		if (!file_exists($configPath)) {
			$plugin->getLogger()->error("The messages file does not exist: $configPath");
			return;
		}
		$config = new Config($configPath);
		$this->messages = array_map(
			fn(string $line): string => str_replace('\n', TextFormat::EOL, TextFormat::colorize($line)),
			$config->getAll()
		);
	}

	public function translate(string $message, array $params = []): string{
		$translated = $this->messages[$message] ?? self::ERROR_MESSAGE_NOT_FOUND . $message;
		foreach ($params as $key => $param) {
			$translated = str_replace('{' . $key . '}', strval($param), $translated);
		}
		return $translated;
	}
}

