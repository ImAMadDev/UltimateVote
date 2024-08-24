<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\session;

use AppGallery\ultimatevote\event\PlayerVoteEvent;
use AppGallery\ultimatevote\VotePlugin;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\task\async\ProcessVote;
use AppGallery\ultimatevote\utils\Utils;
use AppGallery\ultimatevote\utils\VoteRewards;
use pocketmine\player\Player;
use pocketmine\Server;

final class Session {

	private bool $processing = false;

	public function __construct(private readonly Player $player) {
		$this->process(VotePlugin::getInstance()->getConfig()->get('claim-on-join'));
	}

	public function process(bool $claim = true): void {
		$this->processing = true;

		$this->player->sendMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('checking'));

        Server::getInstance()->getAsyncPool()->submitTask(new ProcessVote(Utils::FETCH_URL, $this->getPlayer()->getName(), $claim));
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function isProcessing(): bool {
		return $this->processing;
	}

	public function setProcessing(bool $processing): void {
		$this->processing = $processing;
	}

	public function claim(): void {
		(new PlayerVoteEvent($this->getPlayer()))->call();

		VoteRewards::getInstance()->apply($this->getPlayer());
	}

}