<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\session;

use AppGallery\ultimatevote\event\PlayerVoteEvent;
use AppGallery\ultimatevote\UltimateVote;
use AppGallery\ultimatevote\task\async\ProcessVote;
use AppGallery\ultimatevote\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;

final class Session{

	private bool $processing = false;

	public function __construct(private readonly Player $player){
		$this->process(UltimateVote::getInstance()->getConfig()->get('claim-on-join'));
	}

	public function process(bool $claim = true): void{
		$this->processing = true;
		Server::getInstance()->getAsyncPool()->submitTask(new ProcessVote(Utils::FETCH_URL, UltimateVote::getInstance()->getConfig()->get('api-key'), $this->getPlayer()->getName(), $claim));
		$this->player->sendMessage(UltimateVote::getInstance()->getTranslator()->translate('prefix') . UltimateVote::getInstance()->getTranslator()->translate('checking'));
	}

	public function getPlayer(): Player{
		return $this->player;
	}

	public function isProcessing(): bool{
		return $this->processing;
	}

	public function setProcessing(bool $processing): void{
		$this->processing = $processing;
	}

	public function claim(): void{
		(new PlayerVoteEvent($this->getPlayer()))->call();
		UltimateVote::getInstance()->getVoteRewards()->apply($this->getPlayer());
	}
}