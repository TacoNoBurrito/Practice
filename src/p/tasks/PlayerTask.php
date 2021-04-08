<?php
namespace p\tasks;
use p\Core;
use p\EventListener;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\scheduler\Task;
class PlayerTask extends Task {
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		var_dump(Core::getInstance()->duelInvites);
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $player) {
			if (Core::getInstance()->isFighting($player)) {
				$c = Core::getInstance()::getScoreboardManager();
				$c->showScoreboard($player);
				$c->clearLines($player);
				$c->addLine("§7--------------------", $player);
				$c->addLine("§fFighting: §b".Core::getInstance()->getOpponent($player), $player);
				$c->addLine("§b§b§f§b    ", $player);
				$c->addLine("§aYour Ping: §f".$player->getPing(), $player);
				$c->addLine("§cTheir Ping: §f".Core::getInstance()->getServer()->getPlayer(Core::getInstance()->getOpponent($player))->getPing(), $player);
				$c->addLine("§7------§a§7--------------", $player);
				$c->addLine("§o§bna.zylphix.ml", $player);
			} else {
				$c = Core::getInstance()::getScoreboardManager();
				$c->showScoreboard($player);
				$c->clearLines($player);
				$c->addLine("§7--------------------", $player);
				$c->addLine("§fK: §b" . Core::getInstance()->getKills($player) . " §r§fD: §b" . Core::getInstance()->getDeaths($player), $player);
				$kdr = 0;
				if (Core::getInstance()->getKills($player) == 0 or Core::getInstance()->getDeaths($player) == 0) {
					$kdr = 0;
				} else {
					$kdr = round(Core::getInstance()->getKills($player) / Core::getInstance()->getDeaths($player), 2);
				}
				$c->addLine("§fKDR: §b" . $kdr, $player);
				$c->addLine("§fKillStreak: §b" . Core::getInstance()->getKillstreak($player), $player);
				$num = 0;
				$f = Core::getInstance();
				if (isset($f->pcooldown[$player->getName()]) and time() - $f->pcooldown[$player->getName()] < 12) $num++;
				if (time() - Core::getInstance()->combatTag[strtolower($player->getName())] < 15) $num++;
				if ($num != 0) {
					$c->addLine("§7------§a§7----§a§7----------", $player);
					if (isset($f->pcooldown[$player->getName()]) and time() - $f->pcooldown[$player->getName()] < 12) {
						$time = time() - $f->pcooldown[$player->getName()];
						$c->addLine("§fEnderpearl: §b" . (Core::intToString(12 - $time)), $player);
					}
					if (time() - Core::getInstance()->combatTag[strtolower($player->getName())] < 15) {
						$time = time() - Core::getInstance()->combatTag[strtolower($player->getName())];
						$cooldown = 15 - $time;
						$c->addLine("§fCombat: §b" . Core::intToString($cooldown), $player);
					}
				}
				$c->addLine("§7------§a§7--------------", $player);
				$c->addLine("§o§bna.zylphix.ml", $player);
			}
			Core::getInstance()->updateNametag($player);
			if ($player->getLevel()->getName() == "Hub") return;
			if (Core::getInstance()->isFighting($player)) {
				$effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 20 * 5, 0);
				$player->addEffect($effect);
			}
			switch($player->getLevel()->getName()) {
				case "NoDebuff":
					$effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 20 * 5, 0);
					$player->addEffect($effect);
					break;
			}
		}
	}
}