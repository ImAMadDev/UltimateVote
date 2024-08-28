<?php

namespace AppGallery\ultimatevote\utils;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class Rewards{

	/** @var string[] */
	private array $commands;
	/** @var Item[] */
	private array $items;

	public function __construct(array $rewardsData){
		$rewards = Utils::parseRewards($rewardsData);
		$this->commands = $rewards['commands'];
		$this->items = $rewards['items'];
	}

	public function apply(Player $player): void{
		foreach($this->commands as $command){
			$command = str_replace('{username}', $player->getName(), $command);
			if(str_contains($command, '{console}')){
				Server::getInstance()->getCommandMap()->dispatch(new CustomCommandSender(), str_replace('{console}', '', $command));
				continue;
			}

			Server::getInstance()->getCommandMap()->dispatch($player, $command);
		}

		foreach($this->items as $item){
			if(!$player->getInventory()->canAddItem($item)){
				$player->getWorld()->dropItem($player->getLocation(), $item, Vector3::zero());
				continue;
			}

			$player->getInventory()->addItem($item);
		}
	}

	/**
	 * @return string[]
	 */
	public function getCommands(): array{
		return $this->commands;
	}

	/**
	 * @param string[] $commands
	 * @return void
	 */
	public function setCommands(array $commands): void{
		$this->commands = $commands;
	}

	/**
	 * @return Item[]
	 */
	public function getItems(): array{
		return $this->items;
	}

	/**
	 * @param Item[] $items
	 */
	public function setItems(array $items): void{
		$this->items = $items;
	}

}