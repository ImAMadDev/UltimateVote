<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote;

use AppGallery\ultimatevote\command\VoteCommand;
use AppGallery\ultimatevote\hologram\Hologram;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionFactory;
use AppGallery\ultimatevote\task\TopUpdateTask;
use AppGallery\ultimatevote\thread\task\ProcessVote;
use AppGallery\ultimatevote\thread\VoteThread;
use AppGallery\ultimatevote\utils\Utils;
use AppGallery\ultimatevote\utils\VoteRewards;
use AppGallery\ultimatevote\voteparty\VoteParty;
use JackMD\UpdateNotifier\UpdateNotifier;
use pmmp\thread\Thread;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

final class Loader extends PluginBase{
	use SingletonTrait;

	private const CONFIG_HOLOGRAM = 'hologram.yml';
	public const CONFIG_MESSAGES = 'messages.yml';
	private const CONFIG_MAIN = 'config.yml';
	private const CONFIG_KEY = 'api-key';
	private const CONFIG_TOP_UPDATE = 'top-update';
	public const CONFIG_CMD_VOTE = 'command-vote';

	private VoteThread $voteThread;
	private ?Hologram $hologram = null;

	public function getHologram(): ?Hologram{
		return $this->hologram;
	}

	public function getVoteThread(): VoteThread{
		return $this->voteThread;
	}

	protected function onLoad(): void{
		self::$instance = $this;
		$this->saveDefaultConfigs();
		$this->registerThread();
		UpdateNotifier::checkUpdate($this, $this->getName(), $this->getDescription()->getVersion());
	}

	private function saveDefaultConfigs(): void {
		$this->saveResource(self::CONFIG_MAIN, true);
		$this->saveResource(self::CONFIG_HOLOGRAM, true);
		$this->saveResource(self::CONFIG_MESSAGES, true);
	}

	private function registerThread(): void {
		$notifier = $this->getServer()->getTickSleeper()->addNotifier(function (): void {
			$this->voteThread->collect();
		});
		$this->voteThread = new VoteThread($notifier);
		$this->voteThread->start(Thread::INHERIT_INI | Thread::INHERIT_CONSTANTS);
		$this->voteThread->setKey($this->getConfig()->get(self::CONFIG_KEY));
	}

	protected function onEnable(): void {
		$this->voteThread->execute(new ProcessVote(Utils::TOP_URL, ''));
		$this->registerListeners();
		$this->registerCommands();
		$this->registerEntity();
		$this->loadHologram();
		VoteParty::getInstance()->onEnable($this);
		SessionFactory::getInstance()->onEnable($this);
		Translator::getInstance()->onEnable($this);
		VoteRewards::getInstance()->load($this);
		$this->getScheduler()->scheduleRepeatingTask(new TopUpdateTask(), (int) $this->getConfig()->get(self::CONFIG_TOP_UPDATE, 300) * 20);
	}

	private function registerListeners(): void {
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}

	private function registerCommands(): void {
		$cmdConfig = $this->getConfig()->get(self::CONFIG_CMD_VOTE);
		$command = new VoteCommand(
			$cmdConfig['name'],
			$cmdConfig['description'],
			$cmdConfig['usage'],
			$cmdConfig['aliases']
		);

		if (is_string($cmdConfig['permission'])) {
			Utils::registerPermission($cmdConfig['permission']);
			$command->setPermission($cmdConfig['permission']);
		} else {
			$command->setPermission(DefaultPermissionNames::GROUP_USER);
		}

		$this->getServer()->getCommandMap()->register($this->getName(), $command);
	}

	private function registerEntity(): void{
		EntityFactory::getInstance()->register(Hologram::class, function (World $world, CompoundTag $nbt): Hologram {
			return new Hologram(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['hologram']);
	}

	private function loadHologram(): void{
		$config = new Config($this->getDataFolder() . self::CONFIG_HOLOGRAM);
		if ($config->get('enabled') === true) {
			$loc = $config->get('location', []);
			$world = Server::getInstance()->getWorldManager()->getWorldByName($loc['world']);
			if ($world === null) {
				$this->getLogger()->error('The hologram cannot be loaded, the world does not exist or is not loaded.');
				return;
			}

			$this->hologram = new Hologram(new Location(
				(float) $loc['x'],
				(float) $loc['y'],
				(float) $loc['z'],
				$world,
				0.0,
				0.0
			));
			$this->hologram->spawnToAll();
		}
	}

	protected function onDisable(): void{
		VoteParty::getInstance()->save();
		$this->voteThread->stop();
		$this->voteThread->join();
	}
}