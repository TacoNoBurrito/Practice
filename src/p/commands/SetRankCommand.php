<?php

namespace p\commands;


use p\Core;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;


class SetRankCommand extends PluginCommand
{

	/**
	 * SetRankCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("setrank", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool|mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		$usage = "§cUsage: /setrank (playername) (rank).";
		if (!$sender instanceof Player or in_array(Core::getInstance()->getRank($sender), ["admin", "owner"])) {
			if (empty($args[0] or empty($args[1]))) {
				$sender->sendMessage($usage);
				return true;
			}
			$player = Core::getInstance()->getServer()->getPlayer($args[0]);
			if ($player == null) {
				$sender->sendMessage("§cThis Is Not A Valid Player.");
				return true;
			}
			Core::getInstance()->setRank($player, $args[1]);
			$sender->sendMessage("§aSuccessfully Set ".$player->getName()."'s Rank To ".$args[1].".");
		} else {
			$sender->sendMessage("§cYou Do Not Have Permission To Do This.");
		}
		return true;
	}

}