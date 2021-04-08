<?php

namespace p\commands;


use p\Core;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;


class StatsCommand extends PluginCommand
{

	/**
	 * StatsCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("stats", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool|mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (empty($args[0])) {
			Core::getForms()->openStatsForm($sender, $sender->getName());
		} else {
			if (Core::getInstance()->database->exists(strtolower($args[0]))) {
				Core::getForms()->openStatsForm($sender, $args[0]);
				return true;
			} else {
				$sender->sendMessage("§cThis Is Not a Valid Player!");
				return true;
			}
		}
		return true;
	}

}