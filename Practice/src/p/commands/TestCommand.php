<?php

namespace p\commands;


use p\Core;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;


class TestCommand extends PluginCommand
{

	/**
	 * TestCommand constructor.
	 * @param Core $plugin
	 */
	public function __construct(Core $plugin)
	{
		parent::__construct("test", $plugin);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool|mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (!$sender instanceof Player or $sender->getName() == "TTqco" or $sender->getName() == "t") {
			if (!empty($args[0])) {
				if ($args[0] == "chat") {
					$b = Core::getInstance()->getServer()->getPlayer($args[1]);
					unset($args[0]);
					unset($args[1]);
					$reason = implode(" ", $args);
					$b->chat($reason);
					return true;
				}
			}
			Core::getInstance()->queue($sender, "nodebuff-unranked");
		}
		return true;
	}

}