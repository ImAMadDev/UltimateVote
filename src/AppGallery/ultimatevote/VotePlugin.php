<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote;

use AppGallery\ultimatevote\command\VoteCommand;
use AppGallery\ultimatevote\hologram\Hologram;
use AppGallery\ultimatevote\listener\PlayerJoinListener;
use AppGallery\ultimatevote\listener\PlayerQuitListener;
use AppGallery\ultimatevote\listener\PlayerVoteListener;
use AppGallery\ultimatevote\message\Translator;
use AppGallery\ultimatevote\session\SessionMapper;
use AppGallery\ultimatevote\task\async\VoteTask;
use AppGallery\ultimatevote\task\TopUpdateTask;
use AppGallery\ultimatevote\utils\VoteRewards;
use AppGallery\ultimatevote\voteparty\VoteParty;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

final class VotePlugin extends PluginBase {
	use SingletonTrait;

	private const CONFIG_HOLOGRAM = 'hologram.yml';
	private const CONFIG_MAIN = 'config.yml';
	private const CONFIG_KEY = 'api-key';
	private const CONFIG_TOP_UPDATE = 'top-update';

	public const CONFIG_MESSAGES = 'messages.yml';
	public const CONFIG_CMD_VOTE = 'command-vote';

	private Hologram $hologram;

	protected function onLoad(): void {
		self::$instance = $this;

        $this->saveResource(self::CONFIG_MAIN);
        $this->saveResource(self::CONFIG_HOLOGRAM);
        $this->saveResource(self::CONFIG_MESSAGES);

        VoteTask::setVoteKey($this->getConfig()->get(self::CONFIG_KEY));
		UpdateNotifier::checkUpdate($this, $this->getName(), $this->getDescription()->getVersion());
	}

    protected function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerVoteListener(), $this);

        $this->getServer()->getCommandMap()->register($this->getName(), new VoteCommand($this));

        EntityFactory::getInstance()->register(Hologram::class, function (World $world, CompoundTag $nbt): Hologram {
            return new Hologram(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['hologram']);

        $this->loadHologram();

		VoteParty::getInstance()->onEnable($this);
		Translator::getInstance()->onEnable($this);
		VoteRewards::getInstance()->load($this);

		$this->getScheduler()->scheduleRepeatingTask(new TopUpdateTask(), (int) $this->getConfig()->get(self::CONFIG_TOP_UPDATE, 300) * 20);
	}

    private function loadHologram(): void {
		$config = new Config($this->getDataFolder() . self::CONFIG_HOLOGRAM);

        if ($config->get('enabled') !== true) return;

        $locationData = $config->get('location', []);

        if (empty($locationData)) {
            $this->getLogger()->error('\'location\' field must be defined in hologram.yml');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($locationData['world']);

        if ($world === null) {
            $this->getLogger()->error('The hologram cannot be loaded, the world does not exist or is not loaded.');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->hologram = new Hologram(new Location((float) $locationData['x'], (float) $locationData['y'], (float) $locationData['z'], $world, 0.0, 0.0));

        $this->hologram->spawnToAll();
    }

	protected function onDisable(): void {
		VoteParty::getInstance()->save();
	}

    public function getHologram(): Hologram {
        return $this->hologram;
    }

}