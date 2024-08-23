<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\command;

use AppGallery\ultimatevote\Loader;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionFactory;
use AppGallery\ultimatevote\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;

final class VoteCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;

	public function __construct(Loader $plugin){
		$this->owningPlugin = $plugin;
		$cmdConfig = $plugin->getConfig()->get(Loader::CONFIG_CMD_VOTE);
		parent::__construct(
			$cmdConfig['name'],
			$cmdConfig['description'],
			$cmdConfig['usage'],
			$cmdConfig['aliases']
		);
		if (is_string($cmdConfig['permission'])){
			Utils::registerPermission($cmdConfig['permission']);
			$this->setPermission($cmdConfig['permission']);
		} else {
			$this->setPermission(DefaultPermissionNames::GROUP_USER);
		}
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void{
		if(!($sender instanceof Player)){
			$sender->sendMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('only-player'));
			return;
		}

		$session = SessionFactory::getInstance()->get($sender);
		if($session->isProcessing()){
			$sender->sendMessage(Translator::getInstance()->translate('prefix') . Translator::getInstance()->translate('already-checking'));
			return;
		}

		$session->process();
	}
}