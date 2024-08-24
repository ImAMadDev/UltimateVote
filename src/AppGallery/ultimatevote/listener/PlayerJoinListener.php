<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\listener;

use AppGallery\ultimatevote\session\SessionMapper;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

final class PlayerJoinListener implements Listener {

    /**
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        SessionMapper::getInstance()->add($event->getPlayer());
    }

}