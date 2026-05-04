<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerVoteEvent extends PlayerEvent implements Cancellable{
    use CancellableTrait;

	public function __construct(protected Player $player){}

}