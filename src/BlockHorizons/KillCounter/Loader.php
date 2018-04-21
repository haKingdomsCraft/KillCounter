<?php

namespace BlockHorizons\KillCounter;

use BlockHorizons\KillCounter\achievements\AchievementManager;
use BlockHorizons\KillCounter\commands\CommandOverloads;
use BlockHorizons\KillCounter\commands\KillStatsCommand;
use BlockHorizons\KillCounter\commands\KillsTopCommand;
use BlockHorizons\KillCounter\handlers\KillingSpreeHandler;
use BlockHorizons\KillCounter\listeners\PlayerEventListener;
use BlockHorizons\KillCounter\providers\BaseProvider;
use BlockHorizons\KillCounter\providers\MySQLProvider;
use BlockHorizons\KillCounter\providers\SQLiteProvider;
use onebone\economyapi\EconomyAPI;
use EssentialsPE\EventHandlers\PlayerEventHandler;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

class Loader extends PluginBase {

	private $provider;
	private $economizer;
	private $economyEnabled = false;

	private $killingSpreeHandler;
	private $achievementManager;

	public function onLoad() {
		CommandOverloads::initialize();
	if($this->getConfig()->get("Economy-Support") === true) {

		$this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
	if (!$this->economyapi) {
			$this->getLogger()->info(TF::AQUA . "Economy support enabled, using economy API");
			$this->economyEnabled = true;
	}
			return true;
		}
	}

	public function onEnable() {
		if(!is_dir($this->getDataFolder())) {
			mkdir($this->getDataFolder());
		}
		$this->saveResource("config.yml");
		$this->selectProvider();
		$this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);

		$this->registerCommands();

		$this->killingSpreeHandler = new KillingSpreeHandler($this);
		$this->achievementManager = new AchievementManager($this);
	}

	public function onDisable() {
		$this->getProvider()->closeDatabase();

		$this->getKillingSpreeHandler()->save();
	}

	/**
	 * @return bool
	 */
	public function registerCommands(): bool {
		$commands = [
			new KillStatsCommand($this),
			new KillsTopCommand($this)
		];
		foreach($commands as $command) {
			$this->getServer()->getCommandMap()->register($command->getName(), $command);
		}
		return true;
	}

	/**
	 * @return KillingSpreeHandler
	 */
	public function getKillingSpreeHandler(): KillingSpreeHandler {
		return $this->killingSpreeHandler;
	}

	/**
	 * @return AchievementManager
	 */
	public function getAchievementManager(): AchievementManager {
		return $this->achievementManager;
	}

	/**
	 * @return BaseProvider
	 */
	public function getProvider(): BaseProvider {
		return $this->provider;
	}

	/**
	 * @return BaseProvider
	 */
	public function selectProvider(): BaseProvider {
		switch(strtolower($this->getConfig()->get("Provider"))) {
			default:
			case "sqlite":
			case "sqlite3":
				$this->provider = new SQLiteProvider($this);
				break;
			case "mysql":
			case "mysqli":
				$this->provider = new MySQLProvider($this);
				break;
		}
		return $this->provider;
	}

	/**
	 * @return bool
	 */
	public function isEconomyEnabled(): bool {
		return $this->economyEnabled;
	}

	public function getEconomyAPI(){
		$pl = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		if(!$pl) return $pl;
		if(!$pl->isEnabled()) return null;
		return $pl;
	}
}
