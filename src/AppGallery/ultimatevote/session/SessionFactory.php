<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\session;

use pocketmine\player\Player;

final class SessionFactory{
	/** @var Session[] * */
	private array $sessions = [];

	public function add(Player $player): Session{
		return $this->sessions[$player->getName()] = new Session($player);
	}

	public function get(Player $player): ?Session{
		return $this->sessions[$player->getName()] ?? null;
	}

	public function remove(Player $player): void{
		unset($this->sessions[$player->getName()]);
	}
}