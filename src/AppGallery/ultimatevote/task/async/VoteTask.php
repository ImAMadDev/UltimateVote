<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task\async;

use AppGallery\ultimatevote\VotePlugin;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\Session;
use AppGallery\ultimatevote\session\SessionMapper;
use AppGallery\ultimatevote\utils\Utils;
use AppGallery\ultimatevote\utils\VoteCache;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

abstract class VoteTask extends AsyncTask {

	protected string $username;
	protected string $url;

	private static string $voteKey = "";

	public function __construct(string $username, string $url) {
		if (self::$voteKey === '') {
			throw new \RuntimeException('Vote key must be defined first.');
		}

		$this->username = $username;
		$this->url = str_replace(['{username}', '{key}'], [str_replace(' ', '%20', $username), self::$voteKey], $url);
	}

    public static function setVoteKey(string $voteKey) : void {
        self::$voteKey = $voteKey;
    }

	public function getUsername() : string {
		return $this->username;
	}

	public function getUrl() : string {
		return $this->url;
	}

    public abstract function execute() : bool|string;

	public function onRun() : void {
		$result = $this->execute();

		if(!$this instanceof ProcessVote) {
            $this->setResult($result);
            return;
        }

        if (!$this->shouldClaim() || $result !== Utils::STATUS_NOT_CLAIMED) {
            $this->setResult($result);
            return;
        }

        $result = (new UpdateVote(Utils::POST_URL, $this->getUsername()))->execute();

        if ($result === false) return;

        $this->setResult(Utils::STATUS_JUST_CLAIMED);
    }

	public function onCompletion() : void {
		if ($this->username === '') {
			$result = $this->getResult();

			if (!str_contains($result, 'voters')) return;

            VoteCache::setTopCache(json_decode($result, true)['voters']);
            return;
		}

		$player = Server::getInstance()->getPlayerExact($this->getUsername());

		if ($player === null) return;

		$session = SessionMapper::getInstance()->find($player);

		if ($session === null) {
			SessionMapper::getInstance()->add($player); // TODO: Remove this.
		}

		$session->setProcessing(false);

		$this->sendPlayerMessage($session, $this->getResult());
	}

    private function sendPlayerMessage(Session $session, string $result): void {
        $translator = Translator::getInstance();
        $player = $session->getPlayer();
        $prefix = $translator->translate('prefix');

        if ($result === Utils::STATUS_NOT_FOUND) {
            $player->sendMessage(
                $prefix . $translator->translate(
                    'not-found',
                    ['link' => VotePlugin::getInstance()->getConfig()->get('link')]
                )
            );
            return;
        }

        if ($result === Utils::STATUS_JUST_CLAIMED) {
            $session->claim();
            return;
        }

        if ($result === Utils::STATUS_ALREADY_CLAIMED) {
            $player->sendMessage($prefix . $translator->translate('already-claimed'));
            return;
        }

        if ($result === Utils::STATUS_NOT_CLAIMED) {
            $player->sendMessage(
                $prefix . $translator->translate(
                    'available-rewards',
                    ['command' => VotePlugin::getInstance()->getConfig()->get(VotePlugin::CONFIG_CMD_VOTE)['name'] ?? 'vote']
                )
            );
            return;
        }

        $player->sendMessage($prefix . $translator->translate('error'));
    }

}