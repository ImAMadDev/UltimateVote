<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\command;

use AppGallery\ultimatevote\UltimateVote;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;

final class VoteCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;

	public function __construct(UltimateVote $plugin){
		$this->owningPlugin = $plugin;
		$cmdConfig = $plugin->getConfig()->get(UltimateVote::CONFIG_CMD_VOTE);
		parent::__construct(
			$cmdConfig['name'],
			$cmdConfig['description'],
			$cmdConfig['usage'],
			$cmdConfig['aliases']
		);
		$this->setPermission('ultimateVote.command.vote');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void{
		$translator = UltimateVote::getInstance()->getTranslator();
		if(!($sender instanceof Player)){
			$sender->sendMessage($translator->translate('prefix') . $translator->translate('only-player'));
			return;
		}

		$session = UltimateVote::getInstance()->getSessionFactory()->get($sender);
		if($session === null){
			UltimateVote::getInstance()->getSessionFactory()->add($sender);
		}

		if($session->isProcessing()){
			$sender->sendMessage($translator->translate('prefix') . $translator->translate('already-checking'));
			return;
		}

		$session->process();
	}
}