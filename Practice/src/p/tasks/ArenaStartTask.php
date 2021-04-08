<?php
namespace p\tasks;
use pocketmine\scheduler\Task;
use p\Core;
use pocketmine\Player;
class ArenaStartTask extends Task {

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
	 * @var int
	 */
	private int $countdown = 0;

	/**
	 * ArenaStartTask constructor.
	 * @param Player $p1
	 * @param Player $p2
	 * @param string $type
	 */
	public function __construct(Player $p1, Player $p2, string $type) {
		$this->p1 = $p1;
		$this->p2 = $p2;
		$this->type = $type;
	}

	/**
	 * @param string $message
	 */
	public function announce(string $message) : void {
		foreach([$this->p1, $this->p2] as $player) {
			$player->sendMessage($message);
		}
	}

	/**
	 * @param int $tick
	 */
	public function onRun(int $tick) : void {
		if ($this->countdown > 4) {
			Core::getInstance()->getScheduler()->cancelTask($this->getTaskId());
		} else {
			switch($this->countdown) {
				case 1:
					$this->announce("§eMatch Is Starting In 3...");
					break;
				case 2:
					$this->announce("§eMatch Is Starting In 2...");
					break;
				case 3:
					$this->announce("§eMatch Is Starting In 1...");
					break;
				case 4:
					$this->announce("§eMatch Has Started!");
					$this->p1->setImmobile(false);
					$this->p2->setImmobile(false);
					switch($this->type) {
						case "nodebuff-unranked":
							Core::getKits()->giveNoDebuffKit($this->p1);
							Core::getKits()->giveNoDebuffKit($this->p2);
							break;
					}
					break;
			}
		}
		$this->countdown++;
	}


}