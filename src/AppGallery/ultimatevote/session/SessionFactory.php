<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\session;

use AppGallery\ultimatevote\Loader;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class SessionFactory{
	use SingletonTrait;

	/** @var Session[] * */
	private array $sessions = [];

	public function onEnable(Loader $plugin): void{}

	public function add(Player $player): void{
		$this->sessions[$player->getName()] = new Session($player);
	}

	public function get(Player $player): ?Session{
		return $this->sessions[$player->getName()] ?? null;
	}

	public function remove(Player $player): void{
		unset($this->sessions[$player->getName()]);
	}
}