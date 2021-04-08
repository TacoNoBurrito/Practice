<?php
namespace p;
use pocketmine\command\Command;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {

	/**
	 * @var array
	 */
	private array $antispam = [];

	/**
	 * @var array
	 */
	private array $combatTag = [];

	/**
	 * @var array
	 */
	private array $interactCooldown = [];


	/**
	 * @param PlayerPreLoginEvent $event
	 */
	public function onPreJoin(PlayerPreLoginEvent $event) : void {
		$player = $event->getPlayer();
		Core::getInstance()->combatTag[strtolower($player->getName())]  = 1000;
		$this->antispam[strtolower($player->getName())] = 100;
		$this->interactCooldown[strtolower($player->getName())] = 100;
		if (!Core::getInstance()->hasAccount($player)) {
			Core::getInstance()->createAccount($player);
			return;
		}
		if (Core::getInstance()->isBanned($player)) {
			$player->kick("§cYou Are Banned!\n§cReason: ".Core::getInstance()->punishments->get(strtolower($player->getName()))["ban-reason"]);
			return;
		}
	}

	/**
	 * @param PlayerExhaustEvent $event
	 */
	public function onHungry(PlayerExhaustEvent $event) : void {
		$event->setCancelled(true);
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		Core::setHubMode($player);
		Core::getInstance()->updateFT($player);
		$event->setJoinMessage("§2+ §a".$player->getName());
	}

	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onQuit(PlayerQuitEvent $event) : void {
		$player = $event->getPlayer();
		$bool = false;
		if (Core::getInstance()->isFighting($player)) {
			$bool = true;
			$p = Core::getInstance()->getServer()->getPlayer(Core::getInstance()->getOpponent($player));
			$d = $p;
			$p->sendMessage(TextFormat::RED."Your Opponent Has Forfeited!");
			Core::getInstance()->endDuel($p, $player, Core::getInstance()->getDuelType($player));
		}
		if (!$bool) {
			if (time() - Core::getInstance()->combatTag[strtolower($player->getName())] < 15) {
				if (!empty(Core::getInstance()->lastHit[$player->getName()])) {
					if (Core::getInstance()->getServer()->getPlayer(Core::getInstance()->lastHit[$player->getName()]) != null) {
						$d = Core::getInstance()->getServer()->getPlayer(Core::getInstance()->lastHit[$player->getName()]);
						Core::getInstance()->getServer()->broadcastMessage("§c" . $player->getName() . "§4[" . Core::getInstance()->getKills($player) . "]§e has been slain by §c" . $d->getName() . "§4[" . Core::getInstance()->getKills($d) . "]§e whilst combat logging.");
					}
					$player->kill();
				}
			}
		}
		unset(Core::getInstance()->lastHit[$player->getName()]);
		$event->setQuitMessage("§4- §c".$player->getName());
		unset(Core::getInstance()->queued[$player->getName()]);
	}

	/**
	 * @param PlayerDeathEvent $event
	 */
	public function onDeath(PlayerDeathEvent $event) : void {
		$player = $event->getPlayer();
		Core::getInstance()->combatTag[strtolower($player->getName())]  = 1000;
		$cause = $player->getLastDamageCause();
		$event->setDrops([Item::get(ItemIds::NETHER_STAR)->setCustomName(TextFormat::RED.$player->getName() . "'s Soul")]);
		Core::getInstance()->addDeath($player);
		if ($cause instanceof EntityDamageByEntityEvent) {
			$d = $player->getLastDamageCause()->getDamager();
			if ($d instanceof Player) {
				if (Core::getInstance()->isFighting($player)) {
					Core::getInstance()->endDuel($d, $player, Core::getInstance()->getDuelType($player));
					$event->setDeathMessage("");
					return;
				}
				Core::getInstance()->addKill($d);
				$d->sendMessage("§aYou Are Now On A KillStreak Of " . Core::getInstance()->getKillstreak($d) . "!");
				$event->setDeathMessage("§c" . $player->getName() . "§4[" . Core::getInstance()->getKills($player) . "]§e has been slain by §c" . $d->getName() . "§4[" . Core::getInstance()->getKills($d) . "]§e.");
			}
		} else {
			$event->setDeathMessage("§c" . $player->getName() . "§4[" . Core::getInstance()->getKills($player) . "] §ehas died.");
		}
	}

	/**
	 * @param PlayerRespawnEvent $event
	 */
	public function onRespawn(PlayerRespawnEvent $event) : void {
		$player = $event->getPlayer();
		Core::setHubMode($player);
		Core::getInstance()->updateNametag($player);
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) : void {
		$player = $event->getPlayer();
		$array = ["builder", "owner"];
		if (in_array(Core::getInstance()->getRank($player), $array)) return;
		$event->setCancelled(true);
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event) : void {
		$player = $event->getPlayer();
		$array = ["builder", "owner"];
		if (in_array(Core::getInstance()->getRank($player), $array)) return;
		$event->setCancelled(true);
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onEnderPearl(PlayerInteractEvent $event){
		$item = $event->getItem();
		if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR) {
			if($item instanceof EnderPearl) {
				$cooldown = 12;
				$player = $event->getPlayer();
				if (isset(Core::getInstance()->pcooldown[$player->getName()]) and time() - Core::getInstance()->pcooldown[$player->getName()] < $cooldown) {
					$event->setCancelled(true);
					$time = time() - Core::getInstance()->pcooldown[$player->getName()];
					$message = "§cYou Are On Cooldown For {cooldown} More Seconds.";
					$message = str_replace("{cooldown}", ($cooldown - $time), $message);
					$player->sendMessage($message);
				} else {
					Core::getInstance()->pcooldown[$player->getName()] = time();
				}
			}
		}
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) : void {
		$player = $event->getPlayer();
		$countdown = 2;
		if(!isset($this->antispam[strtolower($player->getName())])){
			$this->antispam[strtolower($player->getName())]=time();
		}else {
			if (!$player->isOp() && time() - $this->antispam[strtolower($player->getName())] < $countdown) {
				$event->setCancelled(true);
				$time = time() - $this->antispam[strtolower($player->getName())];
				$cd = $countdown - $time;
				$player->sendMessage("§cPlease don't spam. §7(".$cd.").");
			} else {
				$kill  = Core::getInstance()->getKills($player);
				$rank = Core::getInstance()->getColoredRank($player);
				$msg = $event->getMessage();
				$name = $player->getName();
				$event->setFormat("§7[§c{$kill}§7] §r{$rank}§r §f{$name}§7: {$msg}");
				$this->antispam[strtolower($player->getName())] = time();
			}
		}
	}

	/**
	 * @param PlayerDropItemEvent $event
	 */
	public function onDrop(PlayerDropItemEvent $event) : void {
		$player = $event->getPlayer();
		if ($player->getGamemode() == 1) return;
		$event->setCancelled(true);
	}

	/**
	 * @param InventoryPickupItemEvent $event
	 */
	public function onPickup(InventoryPickupItemEvent $event) : void {
		$event->setCancelled(true);
	}

	/**
	 * @param PlayerCommandPreprocessEvent $event
	 */
	public function onCommandPreProccessEvent(PlayerCommandPreprocessEvent $event) : void {
		$message = $event->getMessage();
		$player = $event->getPlayer();
		if (strstr($message, "/")) {
			if (Core::getInstance()->isFighting($player)) {
				$player->sendMessage(TextFormat::RED."You May Not Use Commands While In Duels!");
				$event->setCancelled(true);
				return;
			}
			if (time() - Core::getInstance()->combatTag[strtolower($player->getName())] < 15) {
				$event->setCancelled(true);
				$player->sendMessage("§cYou Are Still Combat Tagged!");
				return;
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onDamage(EntityDamageEvent $event) : void {
		$player = $event->getEntity();
		if ($player instanceof Player) {
			$cause = $event->getCause();
			switch ($cause) {
				case EntityDamageEvent::CAUSE_FALL:
				case EntityDamageEvent::CAUSE_DROWNING:
				case EntityDamageEvent::CAUSE_SUFFOCATION:
					$event->setCancelled(true);
					break;
				case EntityDamageEvent::CAUSE_VOID:
					$event->setCancelled(true);
					Core::setHubMode($player);
					break;
			}
			if ($event instanceof EntityDamageByEntityEvent) {
				$damager = $event->getDamager();
				if ($damager->getInventory()->getItemInHand()->getCustomName() == "§r§bFreeze Player") {
					if (in_array($player->getName(), Core::getInstance()->frozen)) {
						unset(Core::getInstance()->frozen[$player->getName()]);
						$player->sendMessage(TextFormat::GREEN . "You Are No Longer Frozen!");
						$player->setImmobile(false);
						$damager->sendMessage(TextFormat::GREEN . "Successfully Thawed The Player!");
						Core::setHubMode($player);
					} else {
						$player->getInventory()->clearAll();
						$player->getArmorInventory()->clearAll();
						Core::getInstance()->frozen[$player->getName()] = true;
						$player->setImmobile(true);
						$damager->sendMessage(TextFormat::GREEN . "Successfully Froze The Player!");
						$player->sendMessage("§7---------------\n§fOh No! You Have Been §bFrozen!\n\n§fBut Dont Worry!\nIf You Listen And Comply With The\nStaff Team, You Could Be Un-Frozen Quickly!\n§7---------------");
					}
				}
				if (in_array($player->getName(), Core::getInstance()->frozen)) {
					$event->setCancelled(true);
					$damager->sendMessage(TextFormat::RED . "You Cannot Damage Frozen Players!");
					return;
				}
				if ($player->getLevel()->getFolderName() == "Hub") {
					$event->setCancelled(true);
					return;
				}
				if ($damager instanceof Player) {
					if ($damager->distance($player) > 4.5) {
						Core::getInstance()->sendMessageToStaff("§7[§4ANTICHEAT§7] §eThe player §c" . round($damager->getName(), 2) . "§e is currently reaching §c" . $damager->distance($player) . "§e blocks! §7(§a" . $damager->getPing() . "ms§7).");
					}
				}
				Core::getInstance()->lastHit[$player->getName()] = $damager->getName();
				Core::getInstance()->updateNametag($player);
				Core::getInstance()->combatTag[strtolower($player->getName())] = time();
				$event->setKnockBack(0.42);
				Core::getInstance()->combatTag[strtolower($damager->getName())] = time();
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onData(DataPacketReceiveEvent $event) : void {
		$packet = $event->getPacket();
		if ($packet instanceof InventoryTransactionPacket) {
			$transactionType = $packet->transactionType;
			if ($transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
				if (Core::getInstance()->isInArray($event->getPlayer())) {
					Core::getInstance()->addClick($event->getPlayer());
				} else {
					Core::getInstance()->addToArray($event->getPlayer());
				}
			}
		}
	}

	/**
	 * @param ItemSpawnEvent $event
	 */
	public function onItemSpawn(ItemSpawnEvent $event) : void {
		$entity = $event->getEntity();
		$name = $entity->getItem()->getCustomName();
		$entity->setNameTag($name);
		$entity->setNameTagVisible(true);
		$entity->setNameTagAlwaysVisible(true);
		Core::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($entity): void {$entity->close();}), 10 * 20);
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event) : void {
		$player = $event->getPlayer();
		if ($player->getLevel()->getFolderName() == "Hub") {
			if (time() - $this->interactCooldown[strtolower($player->getName())] < 1) {
				$event->setCancelled(true);
			} else {
				$this->interactCooldown[strtolower($player->getName())] = time();
				$itemname = $event->getItem()->getCustomName();
				switch ($itemname) {
					case "§r§bFFA Warps":
						Core::getForms()->openFFAForm($player);
						break;
					case "§r§bStats":
						Core::getForms()->openStatsForm($player, $player->getName());
						break;
					case "§r§bRandom Teleport":
						$array = [];
						foreach (Core::getInstance()->getServer()->getOnlinePlayers() as $p) {
							$array[] = $p;
						}
						$count = count($array);
						$random = $array[mt_rand(0, $count - 1)];
						$player->teleport($random);
						break;
					case "§r§bLeave Queue":
						foreach(Core::getInstance()->queued as $type => $name) {
							if ($name == $player->getName()) {
								unset(Core::getInstance()->queued[$type]);
							}
						}
						Core::setHubMode($player);
						$player->sendMessage("§cSuccessfully Left Queue!");
						break;
					case "§r§bDuels":
						Core::getForms()->openPreDuelForm($player);
						break;
				}

			}
		} else {

		}
	}


}