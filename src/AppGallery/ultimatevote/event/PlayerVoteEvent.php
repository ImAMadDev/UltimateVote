<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerVoteEvent extends PlayerEvent{

	public function __construct(protected Player $player){}

}