<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\voteparty;

use AppGallery\ultimatevote\event\VotePartyReachEvent;
use AppGallery\ultimatevote\UltimateVote;
use AppGallery\ultimatevote\utils\Rewards;
use pocketmine\Server;

final class VoteParty{

	private bool $enabled;
	private int $amount;
	private int $votes;
	private Rewards $rewards;

	public function __construct(UltimateVote $plugin){
		if(file_exists($plugin->getDataFolder() . 'vote-party.yml')){
			$this->votes = yaml_parse(file_get_contents($plugin->getDataFolder() . 'vote-party.yml'))['votes'] ?? 0;
		} else{
			$this->votes = 0;
		}
		$data = $plugin->getConfig()->get('vote-party');
		$this->enabled = $data['enabled'];
		$this->amount = $data['amount'];
		$this->rewards = new Rewards($data);
	}

	public function addVote(int $amount = 1): void{
		$votes = $this->votes + $amount;
		if($votes < $this->amount){
			Server::getInstance()->broadcastMessage(UltimateVote::getInstance()->getTranslator()->translate('remaining-votes', ['amount' => $this->amount - $votes]));

			return;
		}

		$ev = new VotePartyReachEvent($votes, $this->rewards);
		$ev->call();
		if($ev->isCancelled()){
			$this->votes = $ev->getVotes();
			return;
		}

		$this->votes = 0;
		Server::getInstance()->broadcastMessage(UltimateVote::getInstance()->getTranslator()->translate('vote-party'));

		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$this->rewards->apply($player);
		}

	}

	public function getVotes(): int{
		return $this->votes;
	}

	public function isEnabled(): bool{
		return $this->enabled;
	}

	public function save(): void{
		file_put_contents(UltimateVote::getInstance()->getDataFolder() . 'vote_party.yml', yaml_emit(['votes' => $this->votes]));
	}

}