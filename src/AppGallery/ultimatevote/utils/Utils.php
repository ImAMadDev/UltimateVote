<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\utils;

use InvalidArgumentException;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\TextFormat;

final class Utils {

	const TOP_URL = "https://minecraftpocket-servers.com/api/?object=servers&element=voters&key={key}&format=json";
	const FETCH_URL = "https://minecraftpocket-servers.com/api/?object=votes&element=claim&key={key}&username={username}";
	const POST_URL = "https://minecraftpocket-servers.com/api/?action=post&object=votes&element=claim&key={key}&username={username}";
	const INFO_URL = 'https://minecraftpocket-servers.com/api/?object=servers&element=detail&key={key}';

	const STATUS_NOT_FOUND = '0';
	const STATUS_NOT_CLAIMED = '1';
	const STATUS_ALREADY_CLAIMED = '2';
	const STATUS_JUST_CLAIMED = '3';

	private function __construct() {}

	public static function registerPermission(string $permissionName, string $description = '', bool $default = false): Permission {
		$permissionManager = PermissionManager::getInstance();

		$opRoot = $permissionManager->getPermission(DefaultPermissions::ROOT_OPERATOR);
		$everyoneRoot = $permissionManager->getPermission(DefaultPermissions::ROOT_USER);

		$permission = new Permission($permissionName, $description);

		$opRoot->addChild($permission->getName(), true);
		$everyoneRoot->addChild($permission->getName(), $default);

		$permissionManager->addPermission($permission);

		return $permissionManager->getPermission($permissionName);
	}

	public static function parseRewards(array $items): array {
		$rewards = [];

		foreach ($items as $type => $reward) {
            if ($type === 'commands') {
                $rewards[$type] = $reward;
                continue;
			}

            if ($type !== 'items') continue;

            $rewards[$type] = array_map(fn($item) => self::parseItem($item), $reward);
        }

		return $rewards;
	}

    private static function parseItem(array $item): Item {
        $itemKey = key($item);
        $values = $item[$itemKey];

        $parsedItem = StringToItemParser::getInstance()->parse($itemKey);

        if ($parsedItem === null) {
            throw new InvalidArgumentException('Item ' . $itemKey . ' not found');
        }

        if (isset($values['customName']) && $values['customName'] !== '') {
            $parsedItem->setCustomName($values['customName']);
        }

        if (!empty($values['lore'])) {
            $parsedItem->setLore(array_map([TextFormat::class, 'colorize'], $values['lore']));
        }

        $parsedItem->setCount(isset($values['amount']) ? (int)$values['amount'] : 1);

        if (!empty($values['enchantments'])) {
            self::addEnchantments($parsedItem, $values['enchantments']);
        }

        return $parsedItem;
    }

    private static function addEnchantments(Item $item, array $enchantments): void {
        foreach ($enchantments as $enchantment) {
            [$name, $level] = explode(':', $enchantment);

            $parsedEnchantment = StringToEnchantmentParser::getInstance()->parse($name);

            if (!$parsedEnchantment instanceof Enchantment) continue;

            $item->addEnchantment(new EnchantmentInstance($parsedEnchantment, (int)$level));
        }
    }

}