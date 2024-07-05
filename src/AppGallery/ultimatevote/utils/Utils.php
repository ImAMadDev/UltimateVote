<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\utils;

use InvalidArgumentException;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class Utils{

	const TOP_URL = "https://minecraftpocket-servers.com/api/?object=servers&element=voters&key={key}&format=json";
	const FETCH_URL = "https://minecraftpocket-servers.com/api/?object=votes&element=claim&key={key}&username={username}";
	const POST_URL = "https://minecraftpocket-servers.com/api/?action=post&object=votes&element=claim&key={key}&username={username}";
	const INFO_URL = 'https://minecraftpocket-servers.com/api/?object=servers&element=detail&key={key}';

	const STATUS_NOT_FOUND = '0';
	const STATUS_NOT_CLAIMED = '1';
	const STATUS_ALREADY_CLAIMED = '2';
	const STATUS_JUST_CLAIMED = '3';

	private function __construct(){}

	public static function registerPermission(string $permission, string $description = '', bool $default = false): Permission{
		$permManager = PermissionManager::getInstance();

		$opRoot = $permManager->getPermission(DefaultPermissions::ROOT_OPERATOR);
		$everyoneRoot = $permManager->getPermission(DefaultPermissions::ROOT_USER);

		$perm = new Permission($permission, $description);

		$opRoot->addChild($perm->getName(), true);
		$everyoneRoot->addChild($perm->getName(), $default);

		$permManager->addPermission($perm);

		return $permManager->getPermission($permission);
	}

	public static function parseRewards(array $items): array{
		$rewards = [];
		foreach($items as $type => $reward){
            if ($type === 'commands') {
				foreach($reward as $command){
					$rewards[$type][] = $command;
				}
			} elseif($type === 'items'){
				foreach($reward as $item){
					$rewards[$type][] = self::parseItem($item);
				}
			}
		}
		return $rewards;
	}

	private static function parseItem(array $item): Item{
        $itemKey = key($item);
        $values = $item[$itemKey];
        $parsedItem = StringToItemParser::getInstance()->parse($itemKey) ?? throw new InvalidArgumentException('Item ' . $itemKey . ' not found');

        $parsedItem->setCustomName($values['customName'] ?? '');
        $parsedItem->setLore(array_map([TextFormat::class, 'colorize'], $values['lore'] ?? []));
        $parsedItem->setCount($values['amount'] ?? 1);
		foreach($values['enchantments'] ?? [] as $enchantment){
			[$name, $level] = explode(':', $enchantment);
            $parsedEnchantment = StringToEnchantmentParser::getInstance()->parse($name);
            if ($parsedEnchantment instanceof Enchantment) {
                $parsedItem->addEnchantment(new EnchantmentInstance($parsedEnchantment, intval($level)));
			}
		}
        return $parsedItem;
	}

	public static function playSound(Player $player, string $sound, float $pitch = 1.0, float $volume = 1.0): void{
		$packet = new PlaySoundPacket();
        $position = $player->getPosition();
        $packet->x = $position->getFloorX();
        $packet->y = $position->getFloorY();
        $packet->z = $position->getFloorZ();
		$packet->soundName = $sound;
		$packet->pitch = $pitch;
		$packet->volume = $volume;
		$player->getNetworkSession()->sendDataPacket($packet);

	}

}