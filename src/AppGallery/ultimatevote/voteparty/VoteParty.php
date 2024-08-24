<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\voteparty;

use AppGallery\ultimatevote\event\VotePartyReachEvent;
use AppGallery\ultimatevote\VotePlugin;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\utils\CustomCommandSender;
use AppGallery\ultimatevote\utils\Utils;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class VoteParty {
	use SingletonTrait;

	private bool $enabled = false;

	private int $amount = 250;

    /** @var string[] */
	private array $commands = [];
	/** @var Item[] */
	private array $items = [];

	private int $votes = 0;

	public function onEnable(VotePlugin $plugin): void {
		if (file_exists($plugin->getDataFolder() . 'vote-party.yml')) {
			$this->votes = yaml_parse(file_get_contents($plugin->getDataFolder() . 'vote-party.yml'))['votes'] ?? 0;
		}

		$data = $plugin->getConfig()->get('vote-party');

		$this->enabled = $data['enabled'];
		$this->amount = $data['amount'];

		$rewards = Utils::parseRewards($data);

		$this->commands = $rewards['commands'];
		$this->items = $rewards['items'];
	}

	public function addVote(int $amount = 1): void{
		$votes = $this->votes + $amount;

		if ($votes < $this->amount) {
            Server::getInstance()->broadcastMessage(Translator::getInstance()->translate('remaining-votes', ['amount' => $this->amount - $votes]));
            $this->votes = $votes;
            return;
        }

        $event = new VotePartyReachEvent($votes, $this->commands, $this->items);
        $event->call();

        if ($event->isCancelled()) {
            $this->votes = $event->getVotes();
            return;
        }

        $votes = 0;

        foreach ($event->getCommands() as $command) {
            if (!str_contains($command, '{username}')) {
                Server::getInstance()->getCommandMap()->dispatch(new CustomCommandSender(), $command);
                continue;
            }

            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                Server::getInstance()->getCommandMap()->dispatch(new CustomCommandSender(), str_replace('{username}', $player->getName(), $command));
            }
        }

        foreach ($event->getItems() as $item) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                    continue;
                }

                $player->getWorld()->dropItem($player->getLocation(), $item, Vector3::zero());
            }
        }

        Server::getInstance()->broadcastMessage(Translator::getInstance()->translate('vote-party'));

        $this->votes = $votes;
	}

	public function getVotes(): int {
		return $this->votes;
	}

	public function save(): void {
		file_put_contents(VotePlugin::getInstance()->getDataFolder() . 'vote_party.yml', yaml_emit(['votes' => $this->votes]));
	}

}