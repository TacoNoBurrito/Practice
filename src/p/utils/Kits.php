<?php
namespace p\utils;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\Player;
use pocketmine\item\Item;
class Kits {

	/**
	 * @param Player $player
	 */
	public function giveNoDebuffKit(Player $player) : void {
		$player->setAllowFlight(false);
		$player->setAllowMovementCheats(false);
		$player->setGamemode(0);
		$player->setFood(20);
		$player->setHealth(20);
		$player->getInventory()->setSize(36);
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$helmet = Item::get(310, 0, 1);
		$helmet->setCustomName("§r§l§cNoDebuff");
		$helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getArmorInventory()->setHelmet($helmet);
		$chestplate = Item::get(311, 0, 1);
		$chestplate->setCustomName("§r§l§cNoDebuff");
		$chestplate->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getArmorInventory()->setChestplate($chestplate);
		$leggings = Item::get(312, 0, 1);
		$leggings->setCustomName("§r§l§cNoDebuff");
		$leggings->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getArmorInventory()->setLeggings($leggings);
		$boots = Item::get(313, 0, 1);
		$boots->setCustomName("§r§l§cNoDebuff");
		$boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getArmorInventory()->setBoots($boots);
		$sword = Item::get(276, 0, 1);
		$sword->setCustomName("§r§l§cNoDebuff");
		$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getInventory()->setItem(0, $sword);
		$player->getInventory()->setItem(1, Item::get(368, 0, 16));
		$player->getInventory()->addItem(Item::get(438, 22, 34));
	}
}