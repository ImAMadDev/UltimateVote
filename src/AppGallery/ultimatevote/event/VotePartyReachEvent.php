<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class VotePartyReachEvent extends Event implements Cancellable {
	use CancellableTrait;

	public function __construct(private int $votes, private array $commands, private array $items) {}

	public function getVotes(): int {
		return $this->votes;
	}

	public function setVotes(int $votes): void {
		$this->votes = $votes;
	}

	public function getCommands(): array {
		return $this->commands;
	}

	public function setCommands(array $commands): void {
		$this->commands = $commands;
	}

	public function getItems(): array {
		return $this->items;
	}

	public function setItems(array $items): void {
		$this->items = $items;
	}

}