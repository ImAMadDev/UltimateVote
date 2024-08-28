<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\event;

use AppGallery\ultimatevote\utils\Rewards;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class VotePartyReachEvent extends Event implements Cancellable{
	use CancellableTrait;

	public function __construct(private int $votes, private Rewards $rewards){}

	public function getVotes(): int{
		return $this->votes;
	}

	public function setVotes(int $votes): void{
		$this->votes = $votes;
	}

	public function getRewards(): Rewards{
		return $this->rewards;
	}

	public function setRewards(Rewards $rewards): void{
		$this->rewards = $rewards;
	}
}