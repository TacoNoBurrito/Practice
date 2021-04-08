<?php

namespace p\commands;


use p\Arena;
use p\Core;
use p\tasks\DuelInviteTask;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;


class DuelCommand extends PluginCommand
{

	/**
	 * DuelCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("duel", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if (empty($args[0])) {
			$sender->sendMessage("§cUsage: /duel (player)");
			return true;
		}
		if ($args[0] == "accept") {
			if (isset(Core::getInstance()->duelInvites[$sender->getName()])) {
				if (Core::getInstance()->getServer()->getPlayer(Core::getInstance()->duelInvites[$sender->getName()]["player"])->getLevel()->getFolderName() != "Hub") {
					$sender->sendMessage("§cThe person who invited you must be in the hub to accept the invite!");
				} else {
					$sender->sendMessage("§cDuel Accepted!");
					new Arena($sender, Core::getInstance()->getServer()->getPlayer(Core::getInstance()->duelInvites[$sender->getName()]["player"]), Core::getInstance()->duelInvites[$sender->getName()]["type"]);
				}
			} else {
				$sender->sendMessage("§cYou do not have a pending invite.");
			}
		} else {
			if (in_array($sender->getName(), Core::getInstance()->duelInvites)) {
				$sender->sendMessage("§cYou already have a pending duel invite.");
				return true;
			}
			$p = Core::getInstance()->getServer()->getPlayer($args[0]);
			if ($p->getName() == $sender->getName()) {
				$sender->sendMessage("§cYou cannot duel yourself!");
				return true;
			}
			if ($p == null) {
				$sender->sendMessage("§cThat Player Is Not Online Or Doesn't Exist!");
				return true;
			}
			if (Core::getInstance()->isFighting($p)) {
				$sender->sendMessage("§cThat Player Is Already In a Duel!");
				return true;
			}
			if (in_array($p->getName(), Core::getInstance()->duelInvites)) {
				$sender->sendMessage("§cThat Player Has Already Been Invited To Another Duel!");
				return true;
			}
			Core::getForms()->openDuelPlayerForm($sender, $p);
		}
		return true;
	}

}