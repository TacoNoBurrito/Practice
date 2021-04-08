<?php
namespace p\tasks;
use pocketmine\scheduler\Task;
use p\Core;
class LeaderboardTask extends Task {
	/**
	 * @param int $tick
	 */
	public function onRun(int $tick) : void {
		Core::getInstance()->killsLeaderboard->setTitle(Core::getInstance()->getKillsLeaderboard());
		Core::getInstance()->getServer()->getLevelByName("Hub")->addParticle(Core::getInstance()->killsLeaderboard);
		Core::getInstance()->deathsLeaderboard->setTitle(Core::getInstance()->getDeathsLeaderboard());
		Core::getInstance()->getServer()->getLevelByName("Hub")->addParticle(Core::getInstance()->deathsLeaderboard);
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $player) {
			Core::getInstance()->updateFT($player);
		}
	}
}