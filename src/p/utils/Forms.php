<?php
namespace p\utils;

use p\Core;
use p\tasks\DuelInviteTask;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Forms {

	/**
	 * @param Player $player
	 */
	public function openFFAForm(Player $player) : void {
		$api = Core::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI");
		if($api === null || $api->isDisabled()){
			return;
		}
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return;
			}
			switch($result){
				case 0:
					Core::getKits()->giveNoDebuffKit($player);
					$player->teleport(Core::getInstance()->getServer()->getLevelByName("NoDebuff")->getSpawnLocation());
					$x = rand(-130, 0);
					$y = 102;
					$z = rand(-102, 0);
					$player->teleport(new Vector3($x,$y,$z));
					$player->sendMessage("§aSuccessfully Warped To NoDebuff.");
					return;
			}
		});
		$form->setTitle("§bFree For All");
		$form->setContent("§bChoose An Arena To Play In!");
		$form->addButton("§bNoDebuff\n§fPlaying: ".count(Core::getInstance()->getServer()->getLevelByName("NoDebuff")->getPlayers()));
		$form->sendToPlayer($player);
	}

	/**
	 * @param $player
	 * @param $statsBoi
	 */
	public function openStatsForm($player, $statsBoi) {
		$form = Core::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createModalForm(function (Player $sender, bool $data) {
			if (!$data) {

			}
			return;
		});
		$cnf = Core::getInstance()->database->get(strtolower($statsBoi));
		$form->setTitle($statsBoi."'s Stats");
		$form->setContent("\nKills: ".$cnf["kills"]."\nDeaths: ".$cnf["deaths"]."\nCurrent Killstreak: ".$cnf["killstreak"]."\nBest Killstreak: ".$cnf["best-killstreak"]."\nRank: ".$cnf["rank"]."\nUnraked-Wins: ".$cnf["unranked-wins"]."\n");
		$form->setButton1("Ok");
		$form->setButton2("Exit");
		$form->sendToPlayer($player);
	}

	/**
	 * @param Player $player
	 */
	public function openPreDuelForm(Player $player) : void {
		$api = Core::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI");
		if($api === null || $api->isDisabled()){
			return;
		}
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return;
			}
			switch($result){
				case 0:
					$this->openUnrankedDuelsForm($player);
					return;
			}
		});
		$form->setTitle("§bDuels");
		$form->setContent("§bChoose a Mode!");
		$form->addButton("§bUnranked\n§fQueued: §b".Core::getInstance()->getQueuedUnranked()." §fFighting: §b".Core::getInstance()->getFightingUnranked()."");
		$form->sendToPlayer($player);
	}

	/**
	 * @param Player $player
	 */
	public function openUnrankedDuelsForm(Player $player) : void {
		$api = Core::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI");
		if($api === null || $api->isDisabled()){
			return;
		}
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return;
			}
			switch($result){
				case 0:
					Core::getInstance()->queue($player, "nodebuff-unranked");
					return;
			}
		});
		$form->setTitle("§bUnranked Duels");
		$form->setContent("§bChoose a Mode!");
		$n = Core::getInstance()->getQueuedUnranked();
		$form->addButton("§bNoDebuff\n§fQueued: §b".$n." §fFighting: §b".Core::getInstance()->fightingCount["nodebuff-unranked"]);
		$form->sendToPlayer($player);
	}

	/**
	 * @param Player $player
	 */
	public function openDuelPlayerForm(Player $player, Player $invited) : void {
		$api = Core::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI");
		if($api === null || $api->isDisabled()){
			return;
		}
		$form = $api->createSimpleForm(function (Player $player, int $data = null) use($invited) {
			$result = $data;
			if($result === null){
				return;
			}
			switch($result){
				case 0:
					Core::getInstance()->getScheduler()->scheduleRepeatingTask(new DuelInviteTask($player, $invited, "nodebuff-unranked"), 20);
					return;
			}
		});
		$form->setTitle("§bDuel ".$invited->getName());
		$form->setContent("§bChoose a Mode!");
		$form->addButton("§bNoDebuff");
		$form->sendToPlayer($player);
	}

}