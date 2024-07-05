<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote;

use AppGallery\ultimatevote\event\PlayerVoteEvent;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionFactory;
use AppGallery\ultimatevote\voteparty\VoteParty;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

final class EventListener implements Listener{

	/**
	 * @priority MONITOR
	 **/
	public function handleJoin(PlayerJoinEvent $event): void{
		SessionFactory::getInstance()->add($event->getPlayer());
	}

	/**
	 * @priority MONITOR
	 **/
	public function handleQuit(PlayerQuitEvent $event): void{
		SessionFactory::getInstance()->remove($event->getPlayer());
	}

	/**
	 * @priority MONITOR
	 */
	public function handleVote(PlayerVoteEvent $event): void{
		VoteParty::getInstance()->addVote();
		if(Loader::getInstance()->getConfig()->get('broadcast-on-claim')){
			Server::getInstance()->broadcastMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('claim-broadcast', ['username' => $event->getPlayer()->getName(), 'link' => Loader::getInstance()->getConfig()->get('link')]));
		}
	}
}