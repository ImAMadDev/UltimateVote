<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote;

use AppGallery\ultimatevote\command\VoteCommand;
use AppGallery\ultimatevote\hologram\Hologram;
use AppGallery\ultimatevote\libs\JackMD\UpdateNotifier\UpdateNotifier;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionFactory;
use AppGallery\ultimatevote\task\async\VoteTask;
use AppGallery\ultimatevote\task\TopUpdateTask;
use AppGallery\ultimatevote\utils\Rewards;
use AppGallery\ultimatevote\voteparty\VoteParty;
use JsonException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

final class UltimateVote extends PluginBase{
	use SingletonTrait;

	private const CONFIG_HOLOGRAM = 'hologram.yml';
	public const CONFIG_MESSAGES = 'messages.yml';
	private const CONFIG_MAIN = 'config.yml';
	private const CONFIG_KEY = 'api-key';
	private const CONFIG_TOP_UPDATE = 'top-update';
	public const CONFIG_CMD_VOTE = 'command-vote';

	private ?Hologram $hologram = null;
	private SessionFactory $sessionFactory;
	private VoteParty $voteParty;
	private Translator $translator;
	private Rewards $voteRewards;

	protected function onLoad(): void{
		self::$instance = $this;
		$this->saveDefaultConfigs();
		VoteTask::setVoteKey($this->getConfig()->get(self::CONFIG_KEY));
		UpdateNotifier::checkUpdate($this, $this->getName(), $this->getDescription()->getVersion());
	}

	private function saveDefaultConfigs(): void {
		$this->saveResource(self::CONFIG_MAIN);
		$this->saveResource(self::CONFIG_HOLOGRAM);
		$this->saveResource(self::CONFIG_MESSAGES);
	}

	protected function onEnable(): void{
		$this->registerListeners();
		$this->registerCommands();
		$this->registerEntity();
		try{
			$this->loadHologram();
		} catch(JsonException $exception){
			$this->getLogger()->error($exception->getMessage());
		}

		$this->voteParty = new VoteParty($this);
		$this->sessionFactory = new SessionFactory();
		$this->translator = new Translator($this);
		$this->voteRewards = new Rewards($this->getConfig()->get('rewards'));
		$this->getScheduler()->scheduleRepeatingTask(new TopUpdateTask(), (int) $this->getConfig()->get(self::CONFIG_TOP_UPDATE, 300) * 20);
	}

	private function registerListeners(): void{
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	private function registerCommands(): void{
		$this->getServer()->getCommandMap()->register($this->getName(), new VoteCommand($this));
	}

	private function registerEntity(): void{
		EntityFactory::getInstance()->register(Hologram::class, function (World $world, CompoundTag $nbt): Hologram {
			return new Hologram(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['hologram']);
	}

	/**
	 * @throws JsonException
	 */
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

	public function getHologram(): ?Hologram{
		return $this->hologram;
	}

	public function getVoteParty(): VoteParty{
		return $this->voteParty;
	}

	public function getSessionFactory(): SessionFactory{
		return $this->sessionFactory;
	}

	public function getTranslator(): Translator{
		return $this->translator;
	}

	public function getVoteRewards(): Rewards{
		return $this->voteRewards;
	}

	protected function onDisable(): void{
		$this->voteParty->save();
	}
}