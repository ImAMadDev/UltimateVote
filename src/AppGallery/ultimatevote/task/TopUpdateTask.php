<?php

namespace AppGallery\ultimatevote\task;

use AppGallery\ultimatevote\Loader;
use AppGallery\ultimatevote\thread\task\ProcessVote;
use AppGallery\ultimatevote\utils\Utils;
use pocketmine\scheduler\Task;

class TopUpdateTask extends Task{

	public function onRun(): void{
		Loader::getInstance()->getVoteThread()->execute(new ProcessVote(Utils::TOP_URL));
	}
}