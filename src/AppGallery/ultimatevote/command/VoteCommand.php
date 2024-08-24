<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\command;

use AppGallery\ultimatevote\VotePlugin;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionMapper;
use AppGallery\ultimatevote\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;

final class VoteCommand extends Command implements PluginOwned {
	use PluginOwnedTrait;

	public function __construct(VotePlugin $plugin) {
		$this->owningPlugin = $plugin;

		$cmdConfig = $plugin->getConfig()->get(VotePlugin::CONFIG_CMD_VOTE);

		parent::__construct($cmdConfig['name'], $cmdConfig['description'], $cmdConfig['usage'], $cmdConfig['aliases']);

		if (is_string($cmdConfig['permission'])){
			Utils::registerPermission($cmdConfig['permission']);
			$this->setPermission($cmdConfig['permission']);
            return;
		}

        $this->setPermission(DefaultPermissionNames::GROUP_USER);
    }

	public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if(!($sender instanceof Player)) {
			$sender->sendMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('only-player'));
			return;
		}

		$session = SessionMapper::getInstance()->find($sender);

		if ($session->isProcessing()) {
			$sender->sendMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('already-checking'));
			return;
		}

		$session->process();
	}

}