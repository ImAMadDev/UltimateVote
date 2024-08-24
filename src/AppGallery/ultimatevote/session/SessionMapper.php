<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\session;

use AppGallery\ultimatevote\VotePlugin;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class SessionMapper {
	use SingletonTrait;

	/** @var Session[] * */
	private array $sessions = [];

	public function add(Player $player): Session {
		return $this->sessions[$player->getName()] = new Session($player);
	}

	public function find(Player $player): ?Session {
		return $this->sessions[$player->getName()] ?? null;
	}

	public function remove(Player $player): void {
		unset($this->sessions[$player->getName()]);
	}

}