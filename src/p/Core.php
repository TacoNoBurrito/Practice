<?php
namespace p;
use p\commands\BanCommand;
use p\commands\DuelCommand;
use p\commands\HubCommand;
use p\commands\KickCommand;
use p\commands\RekitCommand;
use p\commands\SetRankCommand;
use p\commands\StaffModeCommand;
use p\commands\StatsCommand;
use p\commands\TestCommand;
use p\tasks\AnnouncementTask;
use p\tasks\ArenaStartTask;
use p\tasks\LeaderboardTask;
use p\tasks\PlayerTask;
use p\utils\Forms;
use p\utils\Kits;
use p\utils\ScoreboardUtils;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class Core extends PluginBase {

	/**
	 * @var self
	 */
	protected static Core $instance;

	/**
	 * @var Forms
	 */
	protected static Forms $forms;

	/**
	 * @var Kits
	 */
	protected static Kits $kits;

	/**
	 * @var ScoreboardUtils
	 */
	protected static ScoreboardUtils $scoreboards;

	/**
	 * @var Config
	 */
	public Config $database;

	/**
	 * @var Config
	 */
	public Config $punishments;

	/**
	 * @var Config
	 */
	public Config $other;

	/**
	 * @var FloatingTextParticle
	 */
	public FloatingTextParticle $killsLeaderboard;

	/**
	 * @var FloatingTextParticle
	 */
	public FloatingTextParticle $deathsLeaderboard;

	/**
	 * @var FloatingTextParticle
	 */
	public FloatingTextParticle $perPlayerText;

	/**
	 * @var array
	 */
	public array $staffMode = [];

	/**
	 * @var array
	 */
	public array $frozen = [];

	/**
	 * @var array
	 */
	public array $pcooldown = [];

	/**
	 * @var array
	 */
	public array $combatTag = [];

	/**
	 * @var array
	 */
	public array $clicks = [];

	/**
	 * @var array
	 */
	public array $queued = [];

	/**
	 * @var array
	 */
	public array $fighting = [];

	/**
	 * @var array
	 */
	public array $fightingType = [];

	/**
	 * @var array
	 */
	public array $lastHit = [];

	/**
	 * @var int[]
	 */
	public array $fightingCount = ["nodebuff-unranked" => 0];

	/**
	 * @var array
	 */
	public array $duelInvites = [];


	public function onEnable() : void {
		self::$instance = $this;
		self::$forms = new Forms();
		self::$kits = new Kits();
		array_push($this->fightingCount, ["nodebuff-unranked" => 0]);
		self::$scoreboards = new ScoreboardUtils();
		$this->database = new Config($this->getDataFolder() . "PlayerData.yml", Config::YAML);
		$this->other = new Config($this->getDataFolder() . "Other.yml", Config::YAML);
		$this->punishments = new Config($this->getDataFolder() . "PunishmentData.yml", Config::YAML);
		if (!$this->other->exists("serverinfo")) {
			$this->other->set("serverinfo", ["new-players-joined" => 0]);
			$this->other->save();
		}
		$this->killsLeaderboard = new FloatingTextParticle(new Vector3(18.5, 7, 34.5), "");
		$this->deathsLeaderboard = new FloatingTextParticle(new Vector3(22.5, 7, 40.5), "");
		$this->perPlayerText = new FloatingTextParticle(new Vector3(23.5, 6, 7.5), "");
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		$this->getServer()->getCommandMap()->register("stats", new StatsCommand($this));
		$this->getServer()->getCommandMap()->register("setrank", new SetRankCommand($this));
		$this->getServer()->getCommandMap()->register("staffmode", new StaffModeCommand($this));
		$this->getServer()->getCommandMap()->register("hub", new HubCommand($this));
		$this->getServer()->getCommandMap()->register("rekit", new RekitCommand($this));
		$this->getServer()->getCommandMap()->register("test", new TestCommand($this));
		$this->getServer()->getCommandMap()->register("duel", new DuelCommand($this));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("ban"));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("kick"));
		$this->getServer()->getCommandMap()->register("ban", new BanCommand($this));
		$this->getServer()->getCommandMap()->register("kick", new KickCommand($this));
		$this->getScheduler()->scheduleRepeatingTask(new PlayerTask(), 20);
		$worlds = ["Hub", "NoDebuff"];
		foreach($worlds as $world) {
			$this->getServer()->loadLevel($world);
		}
		$this->getScheduler()->scheduleRepeatingTask(new AnnouncementTask(), 20 * 120);
		$this->getScheduler()->scheduleRepeatingTask(new LeaderboardTask(), 20 * 20);//CHANGE THE COOLDOWN! (done)
	}

	/**
	 * @param string $type
	 */
	public function add(string $type) : void {
		switch($type) {
			case "ndbu":
				$this->fightingCount["nodebuff-unranked"] = $this->fightingCount["nodebuff-unranked"] + 1;
				break;
		}
	}

	/**
	 * @param string $type
	 */
	public function subtract(string $type) : void {
		switch($type) {
			case "ndbu":
				$this->fightingCount["nodebuff-unranked"] = $this->fightingCount["nodebuff-unranked"] - 1;
				break;
		}
	}


	/**
	 * @param Player $player
	 * @param string $type
	 * Could most likely be improved - fix later!
	 */
	public function queue(Player $player, string $type) : void {
		if (count($this->queued) == 0) {
			$this->queued[$type] = $player->getName();
			$player->sendMessage("§aYou Have Successfully Been Queued!");
			self::setQueueMode($player);
			return;
		} else {
			$p = "";
			foreach($this->queued as $gtype => $name) {
				if ($gtype == $type) {
					$p = $name;
					break;
				}
			}
			if ($p == "") {
				$this->queued[$type] = $player->getName();
				$player->sendMessage("§aYou Have Successfully Been Queued!");
				self::setQueueMode($player);
				return;
			} else {
				unset($this->queued[$type]);
				$player->sendMessage("§aA Match Has Been Found! Generating World....");
				$p = $this->getServer()->getPlayer($p);
				new Arena($player, $p, $type);
			}
		}
	}

	/**
	 * @param Player $winner
	 * @param Player $looser
	 * @param string $type
	 */
	public function endDuel(Player $winner, Player $looser, string $type) : void {
		$d = $winner;
		$player = $looser;
		Core::getInstance()->addKill($d);
		if (strstr($type, "unranked")) {
			$this->addUnrankedWin($d);
		} else {

		}
		unset(Core::getInstance()->fighting[$player->getName()]);
		unset(Core::getInstance()->fighting[$d->getName()]);
		Core::getInstance()->getServer()->broadcastMessage("§7".$player->getName()." has lost to ".$d->getName()." in a ".$type." duel!");
		Core::getInstance()->setHubMode($d);
		switch($type) {
			case "nodebuff-unranked":
				$this->subtract("ndbu");
				break;
		}
	}

	/**
	 * @param Player $player
	 * @return string
	 */
	public function getDuelType(Player $player) : string {
		return $this->fightingType[$player->getName()];
	}

	/**
	 * @return int
	 */
	public function getQueuedUnranked() : int {
		return count($this->queued);
	}

	/**
	 * @return int
	 */
	public function getFightingUnranked() : int {//add more as u go on
		return $this->fightingCount["nodebuff-unranked"];
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function isFighting(Player $player) : bool {
		return in_array($player->getName(), $this->fighting);
	}

	/**
	 * @param Player $player
	 * @return string
	 */
	public function getOpponent(Player $player) : string {
		if (in_array($player->getName(), $this->fighting)) {
			return $this->fighting[$player->getName()];
		} else {
			return "idk this bug";
		}
	}

	/**
	 * @param Player $player
	 */
	public static function setQueueMode(Player $player) : void {
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getInventory()->setItem(8, Item::get(ItemIds::REDSTONE)->setCustomName("§r§bLeave Queue"));
	}

	/**
	 * @param Player $player
	 */
	public static function setHubMode(Player $player) {
		unset(self::$instance->queued[$player->getName()]);
		$player->setGamemode(0);
		$player->teleport(self::$instance->getServer()->getDefaultLevel()->getSafeSpawn());
		$item = Item::get(ItemIds::DIAMOND_SWORD)->setCustomName("§r§bFFA Warps");
		$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getInventory()->setItem(0, $item);
		$item = Item::get(ItemIds::MOB_HEAD)->setCustomName("§r§bStats");
		$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getInventory()->setItem(4, $item);
		$item = Item::get(ItemIds::IRON_SWORD)->setCustomName("§r§bDuels");
		$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 10));
		$player->getInventory()->setItem(1, $item);
	}


	/**
	 * @return ScoreboardUtils
	 */
	public static function getScoreboardManager() : ScoreboardUtils {
		return self::$scoreboards;
	}

	/**
	 * @return static
	 */
	public static function getInstance() : self {
		return self::$instance;
	}

	/**
	 * @param Player $player
	 */
	public function updateFT(Player $player) : void {
		$this->perPlayerText->setTitle("§l§bNA Practice!\n\n\n§r§fWelcome! §b".$player->getName()." §fTo §bZylphix Practice!\n§fHere Are Your Current Statistics!\n\n§fK: §b".$this->getKills($player)." §fD: §b".$this->getDeaths($player)."\n§fKillstreak: §b".$this->getKillstreak($player)."\n§fBest Killstreak: §b".$this->getBestKillstreak($player)."\n\n§bHope You Enjoy Your Time On Zylphix Practice!");
		$this->getServer()->getLevelByName("Hub")->addParticle($this->perPlayerText, [$player]);
	}

	/**
	 * @param Player $player
	 */
	public function updateNametag(Player $player) : void {
		$player->setNameTag(TextFormat::GREEN.$player->getName()."\n".TextFormat::RED."♥".round($player->getHealth()).TextFormat::GRAY." | ".TextFormat::YELLOW."CPS".TextFormat::DARK_GRAY.": ".TextFormat::LIGHT_PURPLE.$this->getCps($player));
	}

	/**
	 * @return Forms
	 */
	public static function getForms() : Forms {
		return self::$forms;
	}

	/**
	 * @param Player $player
	 * @param string $reason
	 * @param string $banner
	 */
	public function setBanned(Player $player, string $reason, string $banner) : void {
		$this->punishments->setNested(strtolower($player->getName()).".banned", true);
		$this->punishments->save();
		$this->punishments->setNested(strtolower($player->getName()).".ban-reason", $reason);
		$this->punishments->save();
		$this->getServer()->broadcastMessage("§fThe Player §c".$player->getName()." §fHas Been Banned By §c".$banner."\n§fReason: §c".$reason);
		$player->kick("§cYou Are Now §lBANNED!\n§r§cReason: ".$reason, false);
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function isBanned(Player $player) : bool {
		return $this->punishments->get(strtolower($player->getName()))["banned"];
	}

	/**
	 * @param Player $player
	 * @param string $reason
	 * @param string $kicker
	 */
	public function setKicked(Player $player, string $reason, string $kicker) {
		$this->getServer()->broadcastMessage("§fThe Player §c".$player->getName()." §fHas Been Kicked By §c".$kicker."\n§fReason: §c".$reason);
		$player->kick("§cYou Have Been Kicked!\n§r§cReason: ".$reason, false);
	}

	/**
	 * @return Kits
	 */
	public static function getKits() : Kits {
		return self::$kits;
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function hasAccount(Player $player) : bool {
		return $this->database->exists(strtolower($player->getName()));
	}

	/**
	 * @param Player $player
	 */
	public function addUnrankedWin(Player $player) : void {
		$this->database->setNested(strtolower($player->getName()).".unranked-wins", $this->database->get(strtolower($player->getName()))["unranked-wins"] + 1);
		$this->database->save();
	}

	/**
	 * @param $player
	 * @return string
	 */
	public function getColoredRank($player) : string {
		switch(Core::getInstance()->getRank($player)) {
			case "default":
				return "";
			case "trial-mod":
				return "§l§bTRIAL-MOD";
			case "mod":
				return "§l§dMOD";
			case "admin":
				return "§l§cADMIN";
			case "owner":
				return "§l§cOWNER";
		}
	}

	/**
	 * @param Player $player
	 * @return int
	 */
	public function getUnrankedWins(Player $player) : int {
		return $this->database->get(strtolower($player->getName()))["unranked-wins"];
	}

	/**
	 * @param Player $player
	 */
	public function createAccount(Player $player) : void {
		$this->database->set(strtolower($player->getName()), ["unranked-wins" => 0, "kills" => 0, "deaths" => 0, "killstreak" => 0, "best-killstreak" => 0, "rank" => "default"]);
		$this->database->save();
		$this->other->setNested("serverinfo.new-players-joined", $this->other->get("serverinfo")["new-players-joined"] + 1);
		$this->other->save();
		$this->punishments->setNested(strtolower($player->getName()).".banned", false);
		$this->punishments->save();
		$this->punishments->setNested(strtolower($player->getName()).".ban-reason", "");
		$this->punishments->save();
		$this->getServer()->broadcastMessage("§2+ §l§aNEW! §r§a".$player->getName(). " §7(§5#".Core::getInstance()->other->get("serverinfo")["new-players-joined"]."§7).");
	}

	/**
	 * @param Player $player
	 * @return int
	 */
	public function getKills(Player $player) : int {
		if($this->database->get(strtolower($player->getName()))["kills"] == 0) return 0;
		return $this->database->get(strtolower($player->getName()))["kills"];
	}

	/**
	 * @param Player $player
	 * @return int
	 */
	public function getKillstreak(Player $player) : int {
		return $this->database->get(strtolower($player->getName()))["killstreak"];
	}

	/**
	 * @param Player $player
	 * @return int
	 */
	public function getBestKillstreak(Player $player) : int {
		return $this->database->get(strtolower($player->getName()))["best-killstreak"];
	}

	/**
	 * @param Player $player
	 */
	public function addKill(Player $player) : void {
		$this->database->setNested(strtolower($player->getName()). ".kills", $this->getKills($player) + 1);
		$this->database->save();
		$this->database->setNested(strtolower($player->getName()). ".killstreak", $this->getKillstreak($player) + 1);
		$this->database->save();
		if ($this->getBestKillstreak($player) < $this->getKillstreak($player)) $this->database->setNested(strtolower($player->getName()). ".best-killstreak", $this->getKillstreak($player));
		$this->database->save();
	}

	/**
	 * @param Player $player
	 * @return string
	 */
	public function getRank(Player $player) : string {
		return $this->database->get(strtolower($player->getName()))["rank"];
	}

	/**
	 * @return string
	 */
	public function getKillsLeaderboard() : string {
		$array = [];
		for ($i=0;$i<count($this->database->getAll());$i++) {
			$b = $this->database->getAll(true)[$i];
			if (empty($this->database->get($b)["kills"])) continue;
			$array[$this->database->getAll(true)[$i]] = $this->database->get($b)["kills"];
		}
		arsort($array);
		$string = "§bTop Kills Overall.\n";
		$num = 1;
		foreach($array as $name => $kills) {
			if ($num > 10) break;
			$string .= "§7{$num}§f. {$name}§7: §b{$kills}\n";
			$num++;
		}
		return $string;
	}

	/**
	 * @param int $int
	 * @return string
	 */
	public static function intToString(int $int) : string {
		$m = floor($int / 60);
		$s = floor($int % 60);
		return (($m < 10 ? "0" : "").$m.":".((float)$s < 10 ? "0" : "").(float)$s);

	}

	/**
	 * @return string
	 */
	public function getDeathsLeaderboard() : string {
		$array = [];
		for ($i=0;$i<count($this->database->getAll());$i++) {
			$b = $this->database->getAll(true)[$i];
			if (empty($this->database->get($b)["deaths"])) continue;
			$array[$this->database->getAll(true)[$i]] = $this->database->get($b)["deaths"];
		}
		arsort($array);
		$string = "§bTop Deaths Overall.\n";
		$num = 1;
		foreach($array as $name => $kills) {
			if ($num > 10) break;
			$string .= "§7{$num}§f. {$name}§7: §b{$kills}\n";
			$num++;
		}
		return $string;
	}


	/**
	 * @param Player $player
	 * @param string $rank
	 */
	public function setRank(Player $player, string $rank) : void {
		$this->database->setNested(strtolower($player->getName()).".rank", $rank);
		$this->database->save();

	}

	/**
	 * @param Player $player
	 * @return int
	 */
	public function getDeaths(Player $player) : int {
		if ($this->database->get(strtolower($player->getName()))["deaths"] == 0) return 0;
		return $this->database->get(strtolower($player->getName()))["deaths"];
	}

	/**
	 * @param Player $player
	 */
	public function addDeath(Player $player) : void {
		$this->database->setNested(strtolower($player->getName()). ".deaths", $this->getDeaths($player) + 1);
		$this->database->save();
	}

	/**
	 * @param string $message
	 */
	public function sendMessageToStaff(string $message) : void {
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			if (in_array($this->getRank($player), ["trial-mod", "mod", "admin", "owner"])) {
				$player->sendMessage($message);
			}
		}
	}

	/**
	 * @param $player
	 * @param $type
	 */
	public static function setStaffMode($player, $type) {
		switch($type) {
			case "on":
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->setGamemode(3);
				$player->getInventory()->setItem(0, Item::get(ItemIds::WOOL)->setCustomName("§r§bRandom Teleport"));
				$player->getInventory()->setItem(1, Item::get(ItemIds::PACKED_ICE)->setCustomName("§r§bFreeze Player"));
				break;
			case "off":
				self::setHubMode($player);
				break;

		}
	}

	/**
	 * @param $player
	 * @return bool
	 */
	public function isInArray($player):bool{
		$name=$player->getName();
		return ($name !== null) and isset($this->clicks[$name]);
	}

	/**
	 * @param Player $player
	 */
	public function addToArray(Player $player){
		if(!$this->isInArray($player)){
			$this->clicks[$player->getName()]=[];
		}
	}

	/**
	 * @param Player $player
	 */
	public function removeFromArray(Player $player){
		if($this->isInArray($player)){
			unset($this->clicks[$player->getName()]);
		}
	}

	/**
	 * @param Player $player
	 */
	public function addClick(Player $player){
		array_unshift($this->clicks[$player->getName()], microtime(true));
		if(count($this->clicks[$player->getName()]) >= 100){
			array_pop($this->clicks[$player->getName()]);
		}
		$player->sendTip("§b".$this->getCps($player));
	}

	/**
	 * @param Player $player
	 * @param float $deltaTime
	 * @param int $roundPrecision
	 * @return float
	 */
	public function getCps(Player $player, float $deltaTime=1.0, int $roundPrecision=1):float{
		if(!$this->isInArray($player) or empty($this->clicks[$player->getName()])){
			return 0.0;
		}
		$mt=microtime(true);
		return round(count(array_filter($this->clicks[$player->getName()], static function(float $t) use ($deltaTime, $mt):bool{
				return ($mt - $t) <= $deltaTime;
			})) / $deltaTime, $roundPrecision);
	}

}