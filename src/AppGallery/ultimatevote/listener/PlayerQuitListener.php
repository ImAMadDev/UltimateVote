<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\listener;

use AppGallery\ultimatevote\session\SessionMapper;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

final class PlayerQuitListener implements Listener {

    /**
     * @priority MONITOR
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        SessionMapper::getInstance()->remove($event->getPlayer());
    }

}