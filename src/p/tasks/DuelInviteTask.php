<?php
namespace p\tasks;
use p\Core;
use pocketmine\scheduler\Task;
use pocketmine\Player;
class DuelInviteTask extends Task {

	/**
	 * @var Player
	 */
	private Player $player;

	/**
	 * @var Player
	 */
	private Player $invited;

	/**
	 * @var string
	 */
	private string $type;

	/**
	 * @var string
	 */
	private string $invitedname;

	/**
	 * @var int
	 */
	private int $countdown = 0;

	/**
	 * DuelInviteTask constructor.
	 * @param Player $player
	 * @param Player $invited
	 * @param string $type
	 */
	public function __construct(Player $player, Player $invited, string $type) {
		$this->player = $player;
		$this->invited = $invited;
		$this->invitedname = $invited->getName();
		Core::getInstance()->duelInvites[$this->invitedname] = ["type" => $type, "player" => $player->getName()];
		$this->type = $type;
		$player->sendMessage("§aSuccesfully Sent a Invite To ".$invited->getName().".");
		$invited->sendMessage("§aYou have recieved a invite from ".$player->getName().". Use /duel accept accept the duel request! You have 30 seconds!");
	}

	/**
	 * @param int $tick
	 */
	public function onRun(int $tick) : void {
		$this->countdown++;
		if ($this->invited == null or $this->player == null) {
			if ($this->invited == null) {
				$this->player->sendMessage("§aThe player that you have invited is now offline, the invite has expired.");
				unset(Core::getInstance()->duelInvites[$this->invitedname]);
			}
			if ($this->player == null) {
				$this->invited->sendMessage("§aThe player that has invited you is now offline, the invite has expired.");
				unset(Core::getInstance()->duelInvites[$this->invitedname]);
			}
			Core::getInstance()->getScheduler()->cancelTask($this->getTaskId());
		} else {
			if ($this->countdown == 30) {
				if (in_array($this->invitedname, Core::getInstance()->duelInvites)) {
					$this->invited->sendMessage("§cYour previous duel invite has expired!");
					$this->player->sendMessage("§cYour previous duel invite has expired!");
					unset(Core::getInstance()->duelInvites[$this->invitedname]);
					Core::getInstance()->getScheduler()->cancelTask($this->getTaskId());
				} else {
					Core::getInstance()->getScheduler()->cancelTask($this->getTaskId());
				}
			}
		}
	}


}