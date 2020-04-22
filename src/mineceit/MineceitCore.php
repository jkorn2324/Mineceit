<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-07
 * Time: 13:49
 */

declare(strict_types=1);

namespace mineceit;

use mineceit\arenas\ArenaHandler;
use mineceit\commands\arenas\CreateArena;
use mineceit\commands\arenas\DeleteArena;
use mineceit\commands\arenas\EventArena;
use mineceit\commands\arenas\SetArenaSpawn;
use mineceit\commands\bans\MineceitBanCommand;
use mineceit\commands\bans\MineceitBanIPCommand;
use mineceit\commands\bans\MineceitBanListCommand;
use mineceit\commands\bans\MineceitPardonCommand;
use mineceit\commands\bans\MineceitPardonIPCommand;
use mineceit\commands\bans\MineceitResetBans;
use mineceit\commands\basic\FlyCommand;
use mineceit\commands\basic\FreezeCommand;
use mineceit\commands\basic\GamemodeCommand;
use mineceit\commands\basic\HealCommand;
use mineceit\commands\basic\HubCommand;
use mineceit\commands\other\MineceitMeCommand;
use mineceit\commands\other\MineceitTellCommand;
use mineceit\commands\basic\MuteCommand;
use mineceit\commands\basic\PlayerInfoCommand;
use mineceit\commands\basic\SetLeaderboardHologram;
use mineceit\commands\basic\StatsCommand;
use mineceit\commands\duels\DuelCommand;
use mineceit\commands\duels\SpecCommand;
use mineceit\commands\kits\ListKits;
use mineceit\commands\other\MineceitTeleportCommand;
use mineceit\commands\ranks\CreateRank;
use mineceit\commands\ranks\DeleteRank;
use mineceit\commands\ranks\ListRanks;
use mineceit\commands\ranks\SetRanks;
use mineceit\data\mysql\AsyncCreateDatabase;
use mineceit\discord\DiscordUtil;
use mineceit\duels\level\classic\ClassicDuelGen;
use mineceit\duels\level\classic\ClassicSpleefGen;
use mineceit\duels\level\classic\ClassicSumoGen;
use mineceit\duels\DuelHandler;
use mineceit\duels\level\duel\BurntDuelGen;
use mineceit\events\EventManager;
use mineceit\game\entities\FishingHook;
use mineceit\game\entities\MineceitItemEntity;
use mineceit\game\entities\ReplayArrow;
use mineceit\game\entities\ReplayHuman;
use mineceit\game\entities\ReplayItemEntity;
use mineceit\game\entities\SplashPotion;
use mineceit\game\items\ItemHandler;
use mineceit\game\leaderboard\Leaderboards;
use mineceit\game\level\gen\MineceitGenManager;
use mineceit\kits\Kits;
use mineceit\maitenance\reports\ReportManager;
use mineceit\parties\PartyManager;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\info\duels\duelreplay\ReplayManager;
use mineceit\player\PlayerHandler;
use mineceit\player\ranks\RankHandler;
use pocketmine\command\Command;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MineceitCore extends PluginBase
{

    // TODO POSSIBLY ADD RESOURCE PACKS SO THAT THEY SHOW UP IN FORMS
    // TODO ADD CUSTOM DEATH MESSAGES.
    // TODO FIX CONSISTENCY AUTOCLICK DETECTOR -> LATER
    // TODO ADD CHECK IF PLAYER SWINGS THEIR ARMS WHEN HITTING THE OTHER
    // TODO FIX DUELS NOT ENDING WHEN PLAYER DIES
    // TODO ADD CUSTOM KIT SETUP
    // TODO FIX PERMISSIONS FOR PLAYERS -> BASED OFF RANKS NOT PLAYERS -> TESTED
    // TODO COME UP WITH FEATURES TO INCENTIVIZE PEOPLE TO BUY VIP & VIP+
    // TODO PARTICLES -> Figure out how
    // TODO FIGURE OUT HOW TERRAIN GENERATION WORKS

    /* @var Kits */
    private static $kits;

    /* @var PlayerHandler */
    private static $playerHandler;

    /* @var RankHandler */
    private static $rankHandler;

    /* @var ItemHandler */
    private static $itemHandler;

    /* @var ArenaHandler */
    private static $arenas;

    /* @var DuelHandler */
    private static $duelHandler;

    /* @var MineceitCore */
    private static $instance;

    /* @var Leaderboards */
    private static $leaderboard;

    /* @var PartyManager */
    private static $partyManager;

    /* @var ReplayManager */
    private static $replayManager;

    /** @var ReportManager */
    private static $reportManager;

    /** @var EventManager */
    private static $eventManager;

    /** @var MineceitGenManager */
    private static $generatorManager;

    /** @var string */
    private static $dataFolder;

    /* @var bool
     * Determines whether parties are enabled
     */
    public const PARTIES_ENABLED = false;

    /* @var bool
     * Determines whether replays are enabled.
     */
    public const REPLAY_ENABLED = true;

    /**
     * @var bool
     * Determines whether mysql is enabled.
     */
    public const MYSQL_ENABLED = false;

    /**
     * @var bool
     * Determines whether discord logs is enabled.
     */
    public const DISCORD_ENABLED = true;


    /**
     *  @var bool
     *  Determines whether the limited features thing is enabled or not.
     */
    public const LIMITED_FEATURES_ENABLED = false;


    /** @var bool
     * Determines whether the autoclick detection is enabled.
     */
    public const AUTOCLICK_DETECTOR_ENABLED = false; // TODO TURN BACK ON


    /**
     * @var bool
     */
    public const PROXY_ENABLED = false;

    /**
     * When the plugins.
     */
    public function onEnable() {

        self::$instance = $this;

        self::$generatorManager = new MineceitGenManager($this);
        $this->registerGenerators();

        self::$dataFolder = $this->getDataFolder();
        $this->loadLevels();

        $this->saveResource("mineceit.yml");

        $this->registerEntities();
        $this->registerCommands();

        self::$rankHandler = new RankHandler($this);
        self::$playerHandler = new PlayerHandler($this);
        self::$itemHandler = new ItemHandler();
        self::$kits = new Kits($this);
        self::$arenas = new ArenaHandler($this);
        self::$duelHandler = new DuelHandler($this);
        self::$replayManager = new ReplayManager($this);
        self::$partyManager = new PartyManager($this);
        self::$reportManager = new ReportManager($this);
        self::$eventManager = new EventManager($this);

        self::$leaderboard = new Leaderboards($this);

        if(self::MYSQL_ENABLED) {
            $task = new AsyncCreateDatabase(self::$kits->getKitsLocal());
            $this->getServer()->getAsyncPool()->submitTask($task);
        }

        $title = DiscordUtil::boldText("Online");
        $data = self::getServerType();
        $message = "{$data} is now " . DiscordUtil::boldText("ON");
        DiscordUtil::sendStatusUpdate($title, $message, DiscordUtil::GREEN);

        $this->getServer()->getPluginManager()->registerEvents(new MineceitListener($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new MineceitTask($this), 1);
    }

    public function onDisable()
    {
        $title = DiscordUtil::boldText("Offline");
        $data = self::getServerType();
        $message = "{$data} is now " . DiscordUtil::boldText("OFF");
        DiscordUtil::sendStatusUpdate($title, $message, DiscordUtil::RED);
    }

    /**
     * Loads the levels.
     */
    private function loadLevels() : void {

        $worlds = MineceitUtil::getLevelsFromFolder($this);

        $size = count($worlds);

        $server = $this->getServer();

        if($size > 0) {
            foreach($worlds as $world) {
                $world = strval($world);
                if ((is_numeric($world) and strlen($world) <= 3) or strpos($world, 'replay') !== false) {
                    MineceitUtil::deleteLevel($world);
                } elseif(!$server->isLevelLoaded($world) and (strpos($world, '.') === false)) {
                    $server->loadLevel($world);
                }
            }
        }
    }

    /**
     * @return EventManager
     */
    public static function getEventManager() : EventManager {
        return self::$eventManager;
    }


    /**
     * @return MineceitGenManager
     */
    public static function getGeneratorManager() : MineceitGenManager {
        return self::$generatorManager;
    }


    /**
     * @return ReportManager
     */
    public static function getReportManager() : ReportManager {
        return self::$reportManager;
    }

    /**
     * @return PartyManager
     */
    public static function getPartyManager() : PartyManager {
        return self::$partyManager;
    }

    /**
     * @return Leaderboards
     */
    public static function getLeaderboards() : Leaderboards {
        return self::$leaderboard;
    }

    /**
     * @return MineceitCore
     */
    public static function getInstance() : MineceitCore {
        return self::$instance;
    }

    /**
     * @return RankHandler
     */
    public static function getRankHandler() : RankHandler {
        return self::$rankHandler;
    }

    /**
     * @return PlayerHandler
     */
    public static function getPlayerHandler() : PlayerHandler {
        return self::$playerHandler;
    }

    /**
     * @return Kits
     */
    public static function getKits() : Kits {
        return self::$kits;
    }

    /**
     * @return ArenaHandler
     */
    public static function getArenas() : ArenaHandler {
        return self::$arenas;
    }

    /**
     * @return ItemHandler
     */
    public static function getItemHandler() : ItemHandler {
        return self::$itemHandler;
    }

    /**
     * @return DuelHandler
     */
    public static function getDuelHandler() : DuelHandler {
        return self::$duelHandler;
    }

    /**
     * @return ReplayManager
     */
    public static function getReplayManager() : ReplayManager {
        return self::$replayManager;
    }

    /**
     * @return string
     *
     * Gets the resources folder.
     */
    public function getResourcesFolder() : string {
        return $this->getFile() . 'resources/';
    }


    /**
     * @return array
     *
     * Gets the discord webhooks.
     */
    public static function getDiscordWebhooks() : array {

        $config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

        if ($config->exists("webhooks")) {
            return (array)$config->get("webhooks");
        }

        return ["logs" => "", "reports" => "", "status" => ""];
    }


    /**
     * @return array
     *
     * Gets the mysql data.
     */
    public static function getMysqlData() : array {

        $config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

        if($config->exists("mysql")) {
            return (array)$config->get("mysql");
        }

        return ["username" => "", "database" => "", "password" => "", "ip" => "", "port" => 3306];
    }


    /**
     * @return string
     *
     * Gets the server type.
     */
    public static function getServerType() : string {

        $config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

        $result = "Mineceit Test";

        if($config->exists("server-type")) {
            $result = (string)$config->get("server-type");
        }

        return $result === "" ? "Mineceit Test" : $result;
    }


    /**
     * Registers the commands.
     */
    private function registerCommands() : void {

        $this->unregisterCommand('gamemode');
        $this->unregisterCommand('ban');
        $this->unregisterCommand('ban-ip');
        $this->unregisterCommand('banlist');
        $this->unregisterCommand('pardon');
        $this->unregisterCommand('pardon-ip');
        $this->unregisterCommand('tp');
        $this->unregisterCommand('tell');
        $this->unregisterCommand('me');

        $this->registerCommand(new ListKits());
        $this->registerCommand(new CreateArena());
        // $this->registerCommand(new PlaceBreakCommand());
        $this->registerCommand(new CreateRank());
        $this->registerCommand(new GamemodeCommand());
        $this->registerCommand(new FreezeCommand());
        $this->registerCommand(new FlyCommand());
        $this->registerCommand(new DuelCommand());
        $this->registerCommand(new HubCommand());
        $this->registerCommand(new HealCommand());
        $this->registerCommand(new DeleteArena());
        $this->registerCommand(new DeleteRank());
        $this->registerCommand(new ListRanks());
        $this->registerCommand(new StatsCommand());
        $this->registerCommand(new SpecCommand());
        $this->registerCommand(new SetRanks());
        $this->registerCommand(new SetLeaderboardHologram());
        // $this->registerCommand(new EnableTagCommand());
        $this->registerCommand(new PlayerInfoCommand());
        // $this->registerCommand(new VerifyCommand());
        $this->registerCommand(new MineceitBanCommand("ban"));
        $this->registerCommand(new MineceitBanIPCommand("ban-ip"));
        // $this->registerCommand(new LinkCommand());
        $this->registerCommand(new MineceitBanListCommand('banlist'));
        $this->registerCommand(new MineceitPardonCommand('pardon'));
        $this->registerCommand(new MineceitPardonIPCommand('pardon-ip'));
        $this->registerCommand(new MineceitTeleportCommand('tp'));
        $this->registerCommand(new MuteCommand(true));
        $this->registerCommand(new MuteCommand(false));
        $this->registerCommand(new MineceitTellCommand("tell"));
        $this->registerCommand(new MineceitMeCommand("me"));
        $this->registerCommand(new MineceitResetBans());
        $this->registerCommand(new EventArena());
        $this->registerCommand(new SetArenaSpawn());

    }

    /**
     * @param Command $command
     *
     * Registers a command.
     */
    private function registerCommand(Command $command) : void {

        $this->getServer()->getCommandMap()->register($command->getName(), $command);
    }

    /**
     * @param string $commandName
     *
     * Unregisters a command.
     */
    private function unregisterCommand(string $commandName) : void {

        $commandMap = $this->getServer()->getCommandMap();
        $cmd = $commandMap->getCommand($commandName);
        if($cmd !== null) {
            $commandMap->unregister($cmd);
        }

    }

    /**
     * Registers the entities.
     */
    private function registerEntities() : void {

        Entity::registerEntity(SplashPotion::class, false, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);
        Entity::registerEntity(FishingHook::class, false, ["FishingHook", "minecraft:fishing_hook"]);
        Entity::registerEntity(MineceitItemEntity::class, false, ['Item', 'minecraft:item']);
        Entity::registerEntity(ReplayItemEntity::class, false, ["ReplayItem"]);
        Entity::registerEntity(ReplayArrow::class, false, ["ReplayArrow"]);
        Entity::registerEntity(ReplayHuman::class, true);

    }


    /**
     * Registers the generators.
     */
    private function registerGenerators() : void {

        self::$generatorManager->registerGenerator(MineceitUtil::CLASSIC_DUEL_GEN, ClassicDuelGen::class, WorldReplayData::TYPE_DUEL);
        self::$generatorManager->registerGenerator(MineceitUtil::CLASSIC_SUMO_GEN, ClassicSumoGen::class, WorldReplayData::TYPE_SUMO);
        self::$generatorManager->registerGenerator(MineceitUtil::CLASSIC_SPLEEF_GEN, ClassicSpleefGen::class, WorldReplayData::TYPE_SPLEEF);

        // self::$generatorManager->registerGenerator(MineceitUtil::RIVER_DUEL_GEN, RiverDuelGen::class, WorldReplayData::TYPE_DUEL);
        // self::$generatorManager->registerGenerator(MineceitUtil::BURNT_DUEL_GEN, BurntDuelGen::class, WorldReplayData::TYPE_DUEL);
    }
}