<?php
namespace p\tasks;
use pocketmine\scheduler\Task;
use p\Core;
class AnnouncementTask extends Task {

	/**
	 * @param int $tick
	 */
	public function onRun(int $tick) : void {
		$announcements = ["§l§bZylphix §r§f>>\n§fJoin our discord at §ohttps://discord.gg/w6jfVCM7As", "§l§bZylphix §r§f>>\n§fW Tapping can improve your ability to get combos in pvp!", "§l§bZylphix §r§f>>\n§fGoing over 25CPS is bannable!"];
		$r = array_rand($announcements);
		$an = $announcements[$r];
		Core::getInstance()->getServer()->broadcastMessage($an);
	}
}