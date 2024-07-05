<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\thread;

use AppGallery\ultimatevote\Loader;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionFactory;
use AppGallery\ultimatevote\thread\task\ProcessVote;
use AppGallery\ultimatevote\thread\task\UpdateVote;
use AppGallery\ultimatevote\thread\task\VoteTask;
use AppGallery\ultimatevote\utils\Utils;
use AppGallery\ultimatevote\utils\VoteCache;
use pmmp\thread\Thread as NativeThread;
use pmmp\thread\ThreadSafeArray;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;

class VoteThread extends Thread{
	private ThreadSafeArray $buffer;
	private ThreadSafeArray $result;
	private bool $running;
	private string $key;

	public function __construct(private readonly SleeperHandlerEntry $sleeperHandlerEntry){
		$this->buffer = new ThreadSafeArray();
		$this->result = new ThreadSafeArray();
	}

	public function setKey(string $key): void{
		$this->key = $key;
	}

	public function start(int $options = NativeThread::INHERIT_ALL): bool{
		$this->running = true;
		return parent::start($options);
	}

	public function stop(): void{
		$this->running = false;
		$this->synchronized(function(): void{
			$this->notify();
		});
	}

	public function triggerGarbageCollector(): void{
		$this->synchronized(function(): void{
			$this->buffer[] = igbinary_serialize("garbage_collector");
			$this->notifyOne();
		});
	}

	public function onRun(): void{
		while($this->running){
			$notifier = $this->sleeperHandlerEntry->createNotifier();
			while(($data = $this->buffer->shift()) !== null){
				/** @var VoteTask|string $task */
				$task = igbinary_unserialize($data);
				if($task === "garbage_collector"){
					gc_collect_cycles();
					gc_mem_caches();
					gc_enable();
				} else{
					$this->processTask($task);
					$this->result[] = igbinary_serialize($task);
					$notifier->wakeupSleeper();
				}
			}
			$this->sleep();
		}
	}

	private function processTask(VoteTask $task): void{
		$result = $task->execute($this->key);
		if($task instanceof ProcessVote){
			if($task->shouldClaim() && $result === Utils::STATUS_NOT_CLAIMED){
				$result = (new UpdateVote(Utils::POST_URL, $task->getUsername()))->execute($this->key);
				if($result !== false){
					$task->setResult(Utils::STATUS_JUST_CLAIMED);
				}
			} else{
				$task->setResult($result);
			}
		} else{
			$task->setResult($result);
		}

	}

	public function execute(VoteTask $task): void{
		$this->synchronized(function() use ($task): void{
			$this->buffer[] = igbinary_serialize($task);
			$this->notifyOne();
		});
	}

	public function sleep(): void{
		$this->synchronized(function(): void{
			if($this->running){
				$this->wait();
			}
		});
	}

	public function collect(): void{
		while(($data = $this->result->shift()) !== null){
			/** @var VoteTask $task */
			$task = igbinary_unserialize($data);
			$this->handleTaskResult($task);
		}
	}

	private function handleTaskResult(VoteTask $task): void{
		if($task->getUsername() === ''){
			$result = $task->getResult();
			if(str_contains($result, 'voters')){
				VoteCache::setTopCache(json_decode($result, true)['voters']);
			}
			return;
		}

		$player = Server::getInstance()->getPlayerExact($task->getUsername());
		if($player === null){
			return;
		}

		$session = SessionFactory::getInstance()->get($player);
		$session->setProcessing(false);
		$this->sendPlayerMessage($session, $task->getResult());
	}

	private function sendPlayerMessage($session, string $result): void{
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