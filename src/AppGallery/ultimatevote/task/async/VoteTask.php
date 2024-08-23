<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task\async;

use AppGallery\ultimatevote\Loader;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\Session;
use AppGallery\ultimatevote\session\SessionFactory;
use AppGallery\ultimatevote\utils\Utils;
use AppGallery\ultimatevote\utils\VoteCache;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

abstract class VoteTask extends AsyncTask{

	protected string $username;
	protected string $url;

	private static string $voteKey = "";

	public function __construct(
		string $username,
		string $url
	){
		if(self::$voteKey === ""){
			throw new \RuntimeException("Vote key not set");
		}

		$this->username = $username;
		$username = str_replace(' ', '%20', $username);
		$this->url = str_replace(['{username}', '{key}'], [$username, self::$voteKey], $url);
	}

	public function getUsername() : string{
		return $this->username;
	}

	public function getUrl() : string{
		return $this->url;
	}
	public function onRun() : void{
		$result = $this->execute();
		if($this instanceof ProcessVote){
			if($this->shouldClaim() && $result === Utils::STATUS_NOT_CLAIMED){
				$result = (new UpdateVote(Utils::POST_URL, $this->getUsername()))->execute();
				if($result !== false){
					$this->setResult(Utils::STATUS_JUST_CLAIMED);
				}
			} else{
				$this->setResult($result);
			}
		} else{
			$this->setResult($result);
		}
	}

	public abstract function execute() : bool|string;

	public static function setVoteKey(string $voteKey) : void{
		self::$voteKey = $voteKey;
	}

	public function onCompletion() : void{
		if($this->username === ''){
			$result = $this->getResult();
			if(str_contains($result, 'voters')){
				VoteCache::setTopCache(json_decode($result, true)['voters']);
			}
			return;
		}

		$player = Server::getInstance()->getPlayerExact($this->getUsername());
		if($player === null){
			return;
		}

		$session = SessionFactory::getInstance()->get($player);
		if($session == null){
			SessionFactory::getInstance()->add($player);
		}

		$session->setProcessing(false);
		$this->sendPlayerMessage($session, $this->getResult());
	}

	private function sendPlayerMessage(Session $session, string $result): void{
		$translator = Translator::getInstance();
		$player = $session->getPlayer();
		$prefix = $translator->translate('prefix');

		if($result === Utils::STATUS_NOT_FOUND){
			$player->sendMessage($prefix . $translator->translate('not-found', ['link' => Loader::getInstance()->getConfig()->get('link')]));
		} elseif($result === Utils::STATUS_JUST_CLAIMED){
			$session->claim();
		} elseif($result === Utils::STATUS_ALREADY_CLAIMED){
			$player->sendMessage($prefix . $translator->translate('already-claimed'));
		} elseif($result === Utils::STATUS_NOT_CLAIMED){
			$player->sendMessage($prefix . $translator->translate('available-rewards', ['command' => Loader::getInstance()->getConfig()->get(Loader::CONFIG_CMD_VOTE)['name'] ?? 'vote']));
		} else{
			$player->sendMessage($prefix . $translator->translate('error'));
		}
	}
}