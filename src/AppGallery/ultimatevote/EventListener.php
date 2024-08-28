<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote;

use AppGallery\ultimatevote\event\PlayerVoteEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

final class EventListener implements Listener{

	public function __construct(
		private readonly UltimateVote $plugin
	){}

	/**
	 * @priority MONITOR
	 **/
	public function handleJoin(PlayerJoinEvent $event): void{
		$this->plugin->getSessionFactory()->add($event->getPlayer());
	}

	/**
	 * @priority MONITOR
	 **/
	public function handleQuit(PlayerQuitEvent $event): void{
		$this->plugin->getSessionFactory()->remove($event->getPlayer());
	}

	/**
	 * @priority MONITOR
	 */
	public function handleVote(PlayerVoteEvent $event): void{
		if($this->plugin->getVoteParty()->isEnabled()){
			$this->plugin->getVoteParty()->addVote();
		}

		if(UltimateVote::getInstance()->getConfig()->get('broadcast-on-claim')){
			Server::getInstance()->broadcastMessage($this->plugin->getTranslator()->translate('prefix') . $this->plugin->getTranslator()->translate('claim-broadcast', ['username' => $event->getPlayer()->getName(), 'link' => UltimateVote::getInstance()->getConfig()->get('link')]));
		}
	}
}