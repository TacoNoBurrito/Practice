<?php

namespace p\commands;


use p\Core;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;


class BanCommand extends PluginCommand
{

	/**
	 * BanCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("ban", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool|mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (in_array(Core::getInstance()->getRank($sender), ["owner", "admin"])) {
			if (empty($args[0])) {
				$sender->sendMessage(TextFormat::RED."Usage: /ban (player) (reason).");
				return true;
			}
			$player = Core::getInstance()->getServer()->getPlayer($args[0]);
			if ($player == null) {
				$sender->sendMessage(TextFormat::RED."This Player Is Not Online Or Doesn't Exist.");
				return true;
			}
			unset($args[0]);
			unset($args[1]);
			$reason = implode(" ", $args);
			if (empty($reason)) {
				$reason = "Not Set.";
			}
			$sender->sendMessage(TextFormat::RED.TextFormat::ITALIC."Successfully Banned.");
			Core::getInstance()->setBanned($player, $reason, $sender->getName());
		} else {
			$sender->sendMessage(TextFormat::RED."You Do Not Have Permission To Use This Command!");
		}
		return true;
	}

}