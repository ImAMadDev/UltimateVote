<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\utils;

use pocketmine\command\CommandSender;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissibleDelegateTrait;
use pocketmine\Server;

final class CustomCommandSender implements CommandSender {
	use PermissibleDelegateTrait;

	public function __construct() {
		$this->perm = new PermissibleBase([DefaultPermissions::ROOT_CONSOLE => true]);

        $this->recalculatePermissions();
	}

	public function sendMessage(Translatable|string $message): void {
		if($message instanceof Translatable) {
			$message = $this->getLanguage()->translate($message);
		}

		$this->getServer()->getLogger()->info($message);
	}

	public function getLanguage(): Language {
		return $this->getServer()->getLanguage();
	}

	public function getServer(): Server {
		return Server::getInstance();
	}

	public function getName(): string {
		return "CONSOLE";
	}

	public function getScreenLineHeight(): int {
		return PHP_INT_MAX;
	}

	public function setScreenLineHeight(?int $height): void {}

}