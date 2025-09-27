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

abstract class VoteTask extends AsyncTask
{
    protected string $username;
    protected string $url;
    private string $apiKey;

    public function __construct(string $username, string $url, string $apiKey)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $username = str_replace(" ", "%20", $username);
        $this->url = str_replace(
            ["{username}", "{key}"],
            [$username, $this->apiKey],
            $url,
        );
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function onRun(): void
    {
        try {
            $request = Utils::buildCurl($this->url);
        } catch (RuntimeException $exception) {
            \GlobalLogger::get()->error("[VoteTask] Failed to build cURL request for {$this->username}: " . $exception->getMessage());
            $this->setResult("ERROR: " . $exception->getMessage());
            return;
        }

        $result = $this->execute($request);

        curl_close($request);
        
        // Check for cURL errors
        if ($result === false) {
            \GlobalLogger::get()->error("[VoteTask] cURL execution failed for {$this->username}");
            $this->setResult("ERROR: cURL execution failed");
            return;
        }
        
        // Set initial result only once
        $this->setResult($result);

        if ($result === Utils::STATUS_ALREADY_CLAIMED) {
            return;
        }

        if (!$this->shouldClaim() || $result !== Utils::STATUS_NOT_CLAIMED) {
            return;
        }

        try {
            // Build proper POST URL for claiming
            $postUrl = str_replace(['{username}', '{key}'], [str_replace(' ', '%20', $this->username), $this->apiKey], Utils::POST_URL);
            
            $claimRequest = Utils::buildCurl($postUrl);
            $updateVote = new UpdateVote(Utils::POST_URL, $this->getUsername(), $this->apiKey);
            $claimResult = $updateVote->execute($claimRequest);
            
            if ($claimResult !== false && $claimResult !== '0') {
                $this->setResult(Utils::STATUS_JUST_CLAIMED);
            } else {
                \GlobalLogger::get()->warning("[VoteTask] Vote claim failed for {$this->username} - result was: '{$claimResult}'");
            }
        } catch (RuntimeException $exception) {
            \GlobalLogger::get()->error("[VoteTask] Exception during vote claim for {$this->username}: " . $exception->getMessage());
            $this->setResult("ERROR: " . $exception->getMessage());
            return;
        }
    }

    abstract public function execute(CurlHandle $request): bool|string;

    /**
     * Override this method in child classes to determine if vote should be claimed
     */
    public function shouldClaim(): bool
    {
        return false;
    }

    public function onCompletion(): void
    {
        if ($this->username === "") {
            $result = $this->getResult();
            
            if (!str_contains($result, "voters")) {
                \GlobalLogger::get()->warning("[VoteTask] Top voters result doesn't contain 'voters' key: {$result}");
                return;
            }

            VoteCache::setTopCache(json_decode($result, true)["voters"]);
            return;
        }

        $player = Server::getInstance()->getPlayerExact($this->getUsername());
        if ($player === null) {
            return;
        }

        $session = UltimateVote::getInstance()
            ->getSessionFactory()
            ->get($player);
        if ($session == null) {
            UltimateVote::getInstance()->getSessionFactory()->add($player);
            $session = UltimateVote::getInstance()->getSessionFactory()->get($player);
        }

        // Always set processing to false when completing the task
        $session->setProcessing(false);

        $this->parseResult($session, $this->getResult());
    }

    private function parseResult(Session $session, string $result): void
    {
        $translator = UltimateVote::getInstance()->getTranslator();
        $player = $session->getPlayer();
        $prefix = $translator->translate("prefix");

        // Handle errors first
        if (str_starts_with($result, "ERROR:") || str_contains($result, "Error:") || str_contains($result, "error") || str_contains($result, "Invalid") || str_contains($result, "no server key")) {
            \GlobalLogger::get()->error("[VoteTask] API error for player {$player->getName()}: {$result}");
            $player->sendMessage($prefix . "§cError del servidor de votos. Por favor intenta más tarde.");
            return;
        }

        if ($result === Utils::STATUS_NOT_FOUND) {
            $player->sendMessage(
                $prefix .
                    $translator->translate("not-found", [
                        "link" => UltimateVote::getInstance()
                            ->getConfig()
                            ->get("link"),
                    ]),
            );
            return;
        }

        if ($result === Utils::STATUS_JUST_CLAIMED) {
            $session->claim();
            return;
        }

        if ($result === Utils::STATUS_ALREADY_CLAIMED) {
            $player->sendMessage(
                $prefix . $translator->translate("already-claimed"),
            );
            return;
        }

        if ($result === Utils::STATUS_NOT_CLAIMED) {
            $player->sendMessage(
                $prefix .
                    $translator->translate("available-rewards", [
                        "command" =>
                            UltimateVote::getInstance()
                                ->getConfig()
                                ->get(UltimateVote::CONFIG_CMD_VOTE)["name"] ??
                            "vote",
                    ]),
            );
            return;
        }

        \GlobalLogger::get()->warning("[VoteTask] Unknown result '{$result}' for player: " . $player->getName());
        $player->sendMessage($prefix . $translator->translate("error"));
    }


}
