<?php

namespace p\commands;


use p\Core;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;


class RekitCommand extends PluginCommand
{

	/**
	 * StatsCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("rekit", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool|mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		switch($sender->getLevel()->getFolderName()) {
			case "NoDebuff":
				Core::getKits()->giveNoDebuffKit($sender);
				$sender->sendMessage(TextFormat::GREEN."Successfully Re-Kitted!");
				break;
			default:
				$sender->sendMessage(TextFormat::RED."You Arent In An Arena :thonk:");
		}
		return true;
	}

}