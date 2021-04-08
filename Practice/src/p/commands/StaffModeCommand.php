<?php

namespace p\commands;


use p\Core;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;


class StaffModeCommand extends PluginCommand
{

	/**
	 * StaffModeCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("staffmode", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool|mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (in_array(Core::getInstance()->getRank($sender), ["owner","admin","mod","trial-mod"])) {
			if (empty($args[0])) {
				$sender->sendMessage(TextFormat::RED . "Usage: /staffmode (on/off)");
			} else {
				if ($args[0] == "on") {
					Core::getInstance()->staffMode[$sender->getName()] = true;
					Core::setStaffMode($sender, "on");
				} else if ($args[0] == "off") {
					unset(Core::getInstance()->staffMode[$sender->getName()]);
					Core::setStaffMode($sender, "off");
				} else {
					$sender->sendMessage(TextFormat::RED . "Usage: /staffmode (on/off)");
				}
			}
		} else {
			$sender->sendMessage(TextFormat::RED."You Do Not Have Permission To Use This Command.");
		}
		return true;
	}

}