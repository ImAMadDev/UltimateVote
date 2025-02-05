<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task;

use AppGallery\ultimatevote\task\async\ProcessVote;
use AppGallery\ultimatevote\utils\Utils;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class TopUpdateTask extends Task{

    public function __construct(
        private readonly string $apiKey
    ){}

    public function onRun(): void{
		Server::getInstance()->getAsyncPool()->submitTask(new ProcessVote(Utils::TOP_URL, $this->apiKey));
	}
}