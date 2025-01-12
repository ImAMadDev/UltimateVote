<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task\async;

use AppGallery\ultimatevote\UltimateVote;
use AppGallery\ultimatevote\session\Session;
use AppGallery\ultimatevote\utils\Utils;
use AppGallery\ultimatevote\utils\VoteCache;
use CurlHandle;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use RuntimeException;

abstract class VoteTask extends AsyncTask{

	protected string $username;
	protected string $url;
    private string $apiKey;

    public function __construct(string $username, string $url, string $apiKey){
		$this->username = $username;
        $this->apiKey = $apiKey;
        $username = str_replace(' ', '%20', $username);
        $this->url = str_replace(['{username}', '{key}'], [$username, $this->apiKey], $url);
    }

	public function getUsername(): string{
		return $this->username;
	}

	public function onRun(): void{
		try{
			$request = Utils::buildCurl($this->url);
		} catch(RuntimeException $exception){
			$this->setResult($exception->getMessage());
			return;
		}

		$result = $this->execute($request);

		curl_close($request);
		if(!$this instanceof ProcessVote){
			$this->setResult($result);
		}

		if(!$this->shouldClaim() || $result !== Utils::STATUS_NOT_CLAIMED){
			$this->setResult($result);
			return;
		}

		try{
			$result = (new UpdateVote(Utils::POST_URL, $this->getUsername(), $this->apiKey))->execute(Utils::buildCurl($this->url));
		} catch(RuntimeException $exception){
            error_log($exception->getMessage());
			$this->setResult($exception->getMessage());
			return;
		}

		if($result === false) {
			return;
		}

		$this->setResult(Utils::STATUS_JUST_CLAIMED);
	}

	public abstract function execute(CurlHandle $request): bool|string;

	public function onCompletion(): void{
		if($this->username === ''){
			$result = $this->getResult();
			if(!str_contains($result, 'voters')){
				return;
			}

			VoteCache::setTopCache(json_decode($result, true)['voters']);
		}

		$player = Server::getInstance()->getPlayerExact($this->getUsername());
		if($player === null){
			return;
		}

		$session = UltimateVote::getInstance()->getSessionFactory()->get($player);
		if($session == null){
			UltimateVote::getInstance()->getSessionFactory()->add($player);
		}

		$session->setProcessing(false);

		$this->parseResult($session, $this->getResult());
	}

	private function parseResult(Session $session, string $result): void{
		$translator = UltimateVote::getInstance()->getTranslator();
		$player = $session->getPlayer();
		$prefix = $translator->translate('prefix');

		if($result === Utils::STATUS_NOT_FOUND){
			$player->sendMessage($prefix . $translator->translate('not-found', ['link' => UltimateVote::getInstance()->getConfig()->get('link')]));
			return;
		}

		if($result === Utils::STATUS_JUST_CLAIMED){
			$session->claim();
			return;
		}

		if($result === Utils::STATUS_ALREADY_CLAIMED){
			$player->sendMessage($prefix . $translator->translate('already-claimed'));
			return;
		}

		if($result === Utils::STATUS_NOT_CLAIMED){
			$player->sendMessage($prefix . $translator->translate('available-rewards', ['command' => UltimateVote::getInstance()->getConfig()->get(UltimateVote::CONFIG_CMD_VOTE)['name'] ?? 'vote']));
			return;
		}

		$player->sendMessage($prefix . $translator->translate('error'));
	}
}