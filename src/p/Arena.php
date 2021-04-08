<?php
namespace p;
use p\tasks\ArenaStartTask;
use pocketmine\level\generator\Flat;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
class Arena {

	/**
	 * @var Player
	 */
	private Player $p1;

	/**
	 * @var Player
	 */
	private Player $p2;

	/**
	 * @var string
	 */
	private string $type;

	/**
	 * Arena constructor.
	 * @param Player $p1
	 * @param Player $p2
	 * @param string $type
	 */
	public function __construct(Player $p1, Player $p2, string $type) {
		$this->p1 = $p1;
		$this->p2 = $p2;
		$this->type = $type;
		if (in_array($p1->getName(), Core::getInstance()->duelInvites)) {
			unset(Core::getInstance()->duelInvites[$p1->getName()]);
		}
		if (in_array($p2->getName(), Core::getInstance()->duelInvites)) {
			unset(Core::getInstance()->duelInvites[$p2->getName()]);
		}
		switch($type) {
			case "nodebuff-unranked":
				Core::getInstance()->add("ndb");
				break;
		}
		Core::getInstance()->fightingType[$p1->getName()] = $type;
		Core::getInstance()->fightingType[$p2->getName()] = $type;
		$random = rand(1,1000000);
		Core::getInstance()->getServer()->generateLevel("unranked-duel-".$random, null, Flat::class);
		Core::getInstance()->getServer()->loadLevel("unranked-duel-".$random);
		$p1->setImmobile(true);
		$p2->setImmobile(true);
		$p1->teleport(Core::getInstance()->getServer()->getLevelByName("unranked-duel-".$random)->getSafeSpawn());
		$p2->teleport(Core::getInstance()->getServer()->getLevelByName("unranked-duel-".$random)->getSafeSpawn());
		$p1->teleport(new Vector3(256,4,257));
		$p2->teleport(new Vector3(256,4,249));
		$p1->getInventory()->clearAll();
		$p1->getArmorInventory()->clearAll();
		$p2->getInventory()->clearAll();
		$p2->getArmorInventory()->clearAll();
		$p2->sendMessage("§aAn Opponent Has Been Found! Starting Match In 3 Seconds...");
		Core::getInstance()->fighting[$this->p1->getName()] = $this->p2->getName();
		Core::getInstance()->fighting[$this->p2->getName()] = $this->p1->getName();
		Core::getInstance()->getScheduler()->scheduleRepeatingTask(new ArenaStartTask($p1, $p2, $type), 20);
	}

}