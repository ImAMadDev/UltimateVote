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
	private ?int $lastVoteCommandTime = null;
	private ?int $processingStartTime = null;

	public function __construct(private readonly Player $player){
		$this->process(UltimateVote::getInstance()->getConfig()->get('claim-on-join'));
	}

	public function process(bool $claim = true): void{
		// Check 5-second cooldown for vote command
		$currentTime = time();
		if ($this->lastVoteCommandTime !== null && ($currentTime - $this->lastVoteCommandTime) < 5) {
			$remaining = 5 - ($currentTime - $this->lastVoteCommandTime);
			$this->player->sendMessage(UltimateVote::getInstance()->getTranslator()->translate('prefix') . "§cDebes esperar {$remaining} segundos antes de usar el comando de voto nuevamente.");
			return;
		}

		// Prevent multiple simultaneous vote checks
		if ($this->processing) {
			\GlobalLogger::get()->warning("[Session] Player " . $this->player->getName() . " already processing a vote request");
			$this->player->sendMessage(UltimateVote::getInstance()->getTranslator()->translate('prefix') . "§cYa estás procesando un voto. Espera un momento.");
			return;
		}

		$this->processing = true;
		$this->lastVoteCommandTime = $currentTime;
		$this->processingStartTime = $currentTime;
		Server::getInstance()->getAsyncPool()->submitTask(new ProcessVote(Utils::FETCH_URL, UltimateVote::getInstance()->getConfig()->get('api-key'), $this->getPlayer()->getName(), $claim));
		$this->player->sendMessage(UltimateVote::getInstance()->getTranslator()->translate('prefix') . UltimateVote::getInstance()->getTranslator()->translate('checking'));
	}

	public function getPlayer(): Player{
		return $this->player;
	}

	public function isProcessing(): bool{
		// Auto-clear processing state if it's been more than 30 seconds
		if ($this->processing && $this->processingStartTime !== null) {
			if ((time() - $this->processingStartTime) > 30) {
				\GlobalLogger::get()->warning("[Session] Processing timeout for player " . $this->player->getName() . ", auto-clearing state");
				$this->processing = false;
				$this->processingStartTime = null;
				return false;
			}
		}
		return $this->processing;
	}

	public function setProcessing(bool $processing): void{
		$this->processing = $processing;
		if (!$processing) {
			$this->processingStartTime = null;
		}
	}

	public function claim(): void{
		$event = new PlayerVoteEvent($this->getPlayer());
		$event->call();
		
		if ($event->isCancelled()) {
			\GlobalLogger::get()->warning("[Session] Vote event cancelled for player: " . $this->player->getName());
			return;
		}
		
		UltimateVote::getInstance()->getVoteRewards()->apply($this->getPlayer());
	}

	public function getLastVoteCommandTime(): ?int{
		return $this->lastVoteCommandTime;
	}
}
