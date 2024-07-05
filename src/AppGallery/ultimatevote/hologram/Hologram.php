<?php

namespace AppGallery\ultimatevote\hologram;

use AppGallery\ultimatevote\message\Translator;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

final class Hologram extends Human{

	public function __construct(Location $location, ?CompoundTag $nbt = null){
		parent::__construct($location, new Skin("Standard_Custom", str_repeat("\x00", 8192)), $nbt);
		$this->setNameTagAlwaysVisible();
		$this->setNameTagVisible();

		$this->setCanSaveWithChunk(false);
		$this->setHasGravity(false);
	}

	public function parse(array $top): void{
		$nametag = Translator::getInstance()->translate('top-title') . TextFormat::EOL;
		foreach($top as $index => $data){
			$nametag .= Translator::getInstance()->translate('top-line', ['index' => $index + 1, 'username' => $data['nickname'], 'votes' => $data['votes']]) . TextFormat::EOL;
		}

		$this->setNameTag($nametag);
	}

	public function move(float $dx, float $dy, float $dz): void{}

	public function canBeCollidedWith(): bool{ return false; }

	public function setOnFire(int $seconds): void{}

	public function isOnFire(): bool{ return false; }

	public function isFireProof(): bool{ return true; }

	public function applyDamageModifiers(EntityDamageEvent $source): void{}

	public function getDrops(): array{ return []; }

	public function attack(EntityDamageEvent $source): void{
		$source->cancel();
	}

	protected function entityBaseTick(int $tickDiff = 1): bool{
		if($this->justCreated){
			$this->justCreated = false;
			if(!$this->isAlive()){
				$this->kill();
			}
		}
		return false;
	}

	protected function applyPostDamageEffects(EntityDamageEvent $source): void{}

}