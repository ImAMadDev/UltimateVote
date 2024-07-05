<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\command;

use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class VoteCommand extends Command{

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