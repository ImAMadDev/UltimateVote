<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\listener;

use AppGallery\ultimatevote\event\PlayerVoteEvent;
use AppGallery\ultimatevote\VotePlugin;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\voteparty\VoteParty;
use pocketmine\event\Listener;
use pocketmine\Server;

final class PlayerVoteListener implements Listener {

    /**
     * @priority MONITOR
     */
    public function onPlayerVote(PlayerVoteEvent $event): void {
        VoteParty::getInstance()->addVote();

        if(!VotePlugin::getInstance()->getConfig()->get('broadcast-on-claim')) return;

        Server::getInstance()->broadcastMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('claim-broadcast', ['username' => $event->getPlayer()->getName(), 'link' => VotePlugin::getInstance()->getConfig()->get('link')]));
    }

}