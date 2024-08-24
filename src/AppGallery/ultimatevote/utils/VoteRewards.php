<?php

namespace AppGallery\ultimatevote\utils;

use AppGallery\ultimatevote\VotePlugin;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class VoteRewards {
	use SingletonTrait;

	/** @var string[] */
	private array $commands = [];
	/** @var Item[] */
	private array $items = [];

	public function load(VotePlugin $plugin): void {
        $rewards = Utils::parseRewards($plugin->getConfig()->get('rewards'));

		$this->commands = $rewards['commands'];
		$this->items = $rewards['items'];
	}

	public function apply(Player $player): void {
		foreach ($this->commands as $command) {
			if (str_contains($command, '{username}')) {
				Server::getInstance()->getCommandMap()->dispatch(new CustomCommandSender(), str_replace('{username}', $player->getName(), $command));
			    continue;
            }

            Server::getInstance()->getCommandMap()->dispatch(new CustomCommandSender(), $command);
        }

		foreach ($this->items as $item) {
			if ($player->getInventory()->canAddItem($item)) {
				$player->getInventory()->addItem($item);
                continue;
			}

            $player->getWorld()->dropItem($player->getLocation(), $item, Vector3::zero());
        }
	}

}