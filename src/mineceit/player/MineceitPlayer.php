<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-07
 * Time: 13:50
 */

declare(strict_types=1);

namespace mineceit\player;

use mineceit\arenas\FFAArena;
use mineceit\fixes\player\PMPlayer;
use mineceit\game\entities\FishingHook;
use mineceit\game\FormUtil;
use mineceit\kits\AbstractKit;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\network\requests\Request;
use mineceit\network\requests\RequestPool;
use mineceit\player\autodetector\checks\AbstractCheck;
use mineceit\player\autodetector\checks\ConsistencyCheck;
use mineceit\player\autodetector\checks\CPSCheck;
use mineceit\player\autodetector\checks\DiggingCheck;
use mineceit\player\info\clicks\ClicksInfo;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\language\Language;
use mineceit\player\ranks\Rank;
use mineceit\player\tasks\OnDeathTask;
use mineceit\scoreboard\Scoreboard;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\form\Form;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Consumable;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\lang\TextContainer;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\utils\TextFormat;

class MineceitPlayer extends PMPlayer
{

    public const TAG_DUELS = 'tag.duels';
    public const TAG_NORMAL = 'tag.normal';

    public const PERMISSION_BUILDER_MODE = 'place-break';
    public const PERMISSION_TAG = 'tag';
    public const PERMISSION_RESERVE_EVENT_SLOT = 'event';
    public const PERMISSION_LIGHTNING_ON_DEATH = 'lightning';

    public const ANDROID = 1;
    public const IOS = 2;
    public const OSX = 3;
    public const FIREOS = 4;
    public const VRGEAR = 5;
    public const VRHOLOLENS = 6;
    public const WINDOWS_10 = 7;
    public const WINDOWS_32 = 8;
    public const DEDICATED = 9;
    public const TVOS = 10;
    public const PS4 = 11;
    public const SWITCH = 12;
    public const XBOX = 13;

    public const LINUX = 20; // For linux people.

    public const KEYBOARD = 1;
    public const TOUCH = 2;
    public const CONTROLLER = 3;
    public const MOTION_CONTROLLER = 4;

    // Declare the instance variables.

    /** @var array */
    private $reports = []; // Prevents report spam.

    private $tagType = self::TAG_NORMAL;

    /* @var string */
    protected $version = ProtocolInfo::MINECRAFT_VERSION_NETWORK;

    /* @var Scoreboard|null */
    private $scoreboard = null;

    /* @var string */
    private $scoreboardType = Scoreboard::SCOREBOARD_NONE;

    private $formData = [];

    /* @var string|null */
    private $currentKit = null;

    /* @var FishingHook|null */
    private $fishing = null;

    /** @var bool */
    private $lookingAtForm = false;

    /** @var bool */
    private $displayPlayer = true;

    /* @var bool */
    private $spectatorMode = false;

    /* Combat values */
    private $secsInCombat = 0;
    private $combat = false;

    /* Enderpearl values */
    private $secsEnderpearl = 0;
    private $throwPearl = true;

    /** @var int
     * Determines how many seconds the player has een on the server.
     */
    private $currentSecond = 0;

    /* @var FFAArena|null */
    private $currentArena = null;

    /* @var bool */
    private $frozen = false;

    /* @var array */
    private $duelHistory = [];

    /** @var bool */
    private $dead = false;

    private $spam = 0;
    private $tellSpam = 0;

    /** @var int */
    private $limitedFeaturesTime = 0; //Determines whether the player has limited features.

    /** @var int */
    private $lastTimePlayed = -1; // Gets he time when the player played last.

    /** @var array -> Gets the device OS Values */
    private $deviceOSVals = [
        self::ANDROID => 'Android',
        self::IOS => 'iOS',
        self::OSX => 'OSX',
        self::FIREOS => 'FireOS',
        self::VRGEAR => 'VRGear',
        self::VRHOLOLENS => 'VRHololens',
        self::WINDOWS_10 => 'Win10',
        self::WINDOWS_32 => 'Win32',
        self::DEDICATED => 'Dedicated',
        self::TVOS => 'TVOS',
        self::PS4 => 'PS4',
        self::SWITCH => 'Nintendo Switch',
        self::XBOX => 'Xbox',
        self::LINUX => 'Linux'
    ];

    /** @var array -> Gets the input values. */
    private $inputVals = [
        self::KEYBOARD => 'Keyboard',
        self::TOUCH => 'Touch',
        self::CONTROLLER => 'Controller',
        self::MOTION_CONTROLLER => 'Motion-Controller'
    ];

    // TODO
    /* private $guiScaleVals = [
        -2 => "Minimum",
        -1 => "Medium",
        0 => "Maximum"
    ]; */

    /** @var array -> Gets the ui values. */
    const UI_VALUES = ['Classic UI', 'Pocket UI'];

    /** @var int */
    private $ping = 0;

    /* @var array */
    private $playerData = [];

    /** @var array|null */
    private $disguised = null;

    /** @var bool
     * Enables translation of other player's messages. */
    private $translate = false;

    /** @var array */
    private $reportsSearchHistory = [];

    /** @var bool */
    private $swishSound = true;

    /** @var bool */
    private $previousLimitedFeat = false; // Determines whether player has previous limited feature.

    /** @var CPSCheck|null */
    private $clickSpeedCheck = null;
    /** @var DiggingCheck|null */
    private $diggingCheck = null;
    /** @var ConsistencyCheck|null */
    private $consistencyCheck = null;

    /** @var int */
    private $lastAction = -1;
    /** @var int */
    private $lastActionTime = -1;

    /** @var int */
    private $currentAction = -1;
    /** @var int */
    private $currentActionTime = -1;

    /** @var ClicksInfo|null */
    private $clicksInfo;

    /** @var bool */
    private $loadedData = false;

    // ---------------------------------- PLAYER DATA -----------------------------------

    /** @var int */
    private $kills = 0, $deaths = 0;

    /** @var bool */
    private $peOnly = false, $scoreboardEnabled = true, $builderMode = false, $muted = false, $changeTag = false, $lightningEnabled = false;

    /** @var array */
    private $builderLevels = [];

    /** @var string */
    private $currentLang = Language::ENGLISH_US;

    /** @var string */
    private $customTag = '';

    /** @var bool */
    private $particlesEnabled = false;

    /** @var int[] */
    private $elo = [];

    /** @var array|Rank[] */
    private $ranks = [];

    /**
     * @return bool
     */
    public function isDisplayPlayers(): bool
    {

        //return $this->displayPlayer;
        // TODO RE ADD EVENTUALLY
        return $this->displayPlayer;
    }

    public function showPlayers(bool $changeVal = true): void
    {
        $this->displayPlayer = $changeVal;
        // TODO RE ADD EVENTUALLY

        /* $this->displayPlayers(false);
        if($changeVal === false)
            $this->displayPlayer = true; */
    }

    public function hidePlayers(bool $changeVal = true): void
    {
        $this->displayPlayer = $changeVal;
        // TODO RE ADD EVENTUALLY

        /* $this->displayPlayers(true);
        if($changeVal === false)
            $this->displayPlayer = false; */
    }

    /**
     * @param bool $hide
     */
    private function displayPlayers(bool $hide = false): void
    {

        $players = $this->getLevel()->getPlayers();

        if ($hide === true) {

            foreach ($players as $player) {
                $name = $player->getName();
                if ($name === $this->getName())
                    continue;

                $this->hidePlayer($player);
            }
        } else {

            foreach ($players as $player) {

                $name = $player->getName();
                if ($name === $this->getName())
                    continue;

                $this->showPlayer($player);
            }
        }

        $this->displayPlayer = !$hide;
    }

    /**
     * Occurs once this player joins the world.
     */
    public function onJoin(): void
    {

        // Init everything.
        $this->clicksInfo = new ClicksInfo($this);
        $this->clickSpeedCheck = new CPSCheck($this);
        $this->diggingCheck = new DiggingCheck($this);
        $this->consistencyCheck = new ConsistencyCheck($this);
    }


    /**
     * @param LoginPacket $pkt
     * @return bool
     *
     * Handles the login sequence.
     */
    public function handleLogin(LoginPacket $pkt): bool
    {

        $result = parent::handleLogin($pkt);

        if (!$result) {
            return false;
        }

        // Hack for getting device model & os for xbox & linux.
        // Could change in the future.

        $clientData = $pkt->clientData;
        $this->version = $clientData["GameVersion"];

        $deviceModel = (string)$clientData["DeviceModel"];
        $deviceOS = (int)$clientData["DeviceOS"];

        if (trim($deviceModel) === "") {
            switch ($deviceOS) {
                case self::ANDROID:
                    $deviceOS = self::LINUX;
                    $deviceModel = "Linux";
                    break;
                case self::XBOX:
                    $deviceModel = "Xbox One";
                    break;
            }
        }

        $clientData["DeviceModel"] = $deviceModel;
        $clientData["DeviceOS"] = $deviceOS;

        $this->playerData['clientData'] = $clientData;
        $this->playerData['clientId'] = $pkt->clientId;
        $this->playerData['clientUUID'] = $pkt->clientUUID;
        $this->playerData['chainData'] = $pkt->chainData;

        return $result;
    }

    protected function completeLoginSequence()
    {
        parent::completeLoginSequence();
        $ipManager = MineceitCore::getPlayerHandler()->getIPManager();
        $ipManager->checkIPSafe($this);
    }

    /**
     * @param string $scoreboardType
     */
    public function setScoreboard(string $scoreboardType): void
    {

        switch ($scoreboardType) {
            case Scoreboard::SCOREBOARD_SPAWN:
                $this->setSpawnScoreboard();
                break;
            case Scoreboard::SCOREBOARD_DUEL:
                $this->setDuelScoreboard();
                break;
            case Scoreboard::SCOREBOARD_FFA:
                $this->setFFAScoreboard();
                break;
            case Scoreboard::SCOREBOARD_SPECTATOR:
                $this->setSpectatorScoreboard();
                break;
            case Scoreboard::SCOREBOARD_REPLAY:
                $this->setReplayScoreboard();
                break;
            case Scoreboard::SCOREBOARD_EVENT_SPEC:
                $this->setEventSpectatorScoreboard();
                break;
            case Scoreboard::SCOREBOARD_EVENT_DUEL:
                $this->setEventDuelScoreboard();
                break;
            default:
        }
    }

    /**
     * Sets the spawn scoreboard.
     */
    private function setSpawnScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        $language = $this->getLanguage();

        $onlineStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_ONLINE);
        $inFightsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_INFIGHTS);
        $inQueuesStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_INQUEUES);

        $server = $this->getServer();

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $online = count($server->getOnlinePlayers());

        $inQueues = MineceitCore::getDuelHandler()->getEveryoneInQueues();

        $color = MineceitUtil::getThemeColor();

        $onlineSb = TextFormat::WHITE . " $onlineStr: " . $color . $online;
        $inFightsSb = TextFormat::WHITE . " $inFightsStr: " . $color . '0 ';
        $inQueuesSb = TextFormat::WHITE . " $inQueuesStr: " . $color . "$inQueues ";

        if ($language->getLocale() === Language::ARABIC) {
            $onlineSb = ' ' . $color . $online . TextFormat::WHITE . " :$onlineStr";
            $inFightsSb = ' ' . $color . '0 ' . TextFormat::WHITE . ":$inFightsStr";
            $inQueuesSb = ' ' . $color . '0 ' . TextFormat::WHITE . ":$inQueuesStr";
        }

        $this->scoreboard->addLine(0, '                 ');
        $this->scoreboard->addLine(1, $onlineSb);

        $this->scoreboard->addLine(2, '');
        $this->scoreboard->addLine(3, TextFormat::WHITE . ' Ping: ' . $color . $this->getPing());
        $this->scoreboard->addLine(4, ' ');
        $this->scoreboard->addLine(5, $inFightsSb);

        $this->scoreboard->addLine(6, $inQueuesSb);
        $this->scoreboard->addLine(7, '  ');

        $duelHandler = MineceitCore::getDuelHandler();

        $this->scoreboardType = Scoreboard::SCOREBOARD_SPAWN;

        if ($this->isInQueue()) {
            $queue = $duelHandler->getQueueOf($this);
            $this->addQueueToScoreboard($queue->isRanked(), $queue->getQueue());
        } else $this->scoreboard->addLine(8, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');
    }

    private function setDuelScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        $duelHandler = MineceitCore::getDuelHandler();

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $duel = $duelHandler->getDuel($this);

        $opponent = $duel->getOpponent($this);

        $lang = $this->getLanguage();

        $color = MineceitUtil::getThemeColor();

        $durationStr = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . '00:00';
        $opponentStrTop = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_OPPONENT) . ': ';
        $opponentStrBottom = $color . ' ' . $opponent->getDisplayName() . ' ';
        $yourPing = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING) . ': ' . $color . $this->getPing();
        $theirPing = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING) . ': ' . $color . $opponent->getPing();
        $yourCps = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ': ' . $color . 0 . ' ';
        $theirCps = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ': ' . $color . 0 . ' ';

        if ($lang->getLocale() === Language::ARABIC) {
            $durationStr = $color . ' 00:00' . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
            $opponentStrTop = TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_OPPONENT);
            $opponentStrBottom = $color . ' ' . $opponent->getDisplayName() . ' ';
            $yourPing = $color . ' ' . $this->getPing() . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING);
            $theirPing = $color . ' ' . $opponent->getPing() . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING);
            $yourCps = ' ' . $color . 0 . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ' ';
            $theirCps = ' ' . $color . 0 . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ' ';
        }

        $this->scoreboard->addLine(0, ' ');
        $this->scoreboard->addLine(1, $durationStr);
        $this->scoreboard->addLine(2, '     ');
        $this->scoreboard->addLine(3, $opponentStrTop);
        $this->scoreboard->addLine(4, $opponentStrBottom);
        $this->scoreboard->addLine(5, '  ');
        $this->scoreboard->addLine(6, $yourPing);
        $this->scoreboard->addLine(7, $yourCps);
        $this->scoreboard->addLine(8, '');
        $this->scoreboard->addLine(9, $theirPing);
        $this->scoreboard->addLine(10, $theirCps);
        $this->scoreboard->addLine(11, '    ');
        $this->scoreboard->addLine(12, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');

        $this->scoreboardType = Scoreboard::SCOREBOARD_DUEL;
    }

    private function setSpectatorScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $duelHandler = MineceitCore::getDuelHandler();

        $duel = $duelHandler->getDuelFromSpec($this);

        $lang = $this->getLanguage();

        $color = MineceitUtil::getThemeColor();

        if ($duel !== null) {

            $ranked = $duel->isRanked();

            $queue = $duel->getQueue();

            $duration = $duel->getDuration();

            $durationStr = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . "$duration";

            $queueLineTop = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE) . ': ';
            $queueLineBottom = ' ' . $color . $lang->getRankedStr($ranked) . ' ' . $queue . ' ';

            $this->scoreboard->addLine(0, '     ');
            $this->scoreboard->addLine(1, $durationStr);
            $this->scoreboard->addLine(2, '   ');
            $this->scoreboard->addLine(3, $queueLineTop);
            $this->scoreboard->addLine(4, $queueLineBottom);
            $this->scoreboard->addLine(5, ' ');
            $this->scoreboard->addLine(6, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');

            $this->scoreboardType = Scoreboard::SCOREBOARD_SPECTATOR;
        }
    }

    private function setFFAScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $language = $this->getLanguage();

        $arenaStr = $language->scoreboard(Language::FFA_SCOREBOARD_ARENA);
        $killsStr = $language->scoreboard(Language::FFA_SCOREBOARD_KILLS);
        $deathsStr = $language->scoreboard(Language::FFA_SCOREBOARD_DEATHS);

        $kills = $this->kills;
        $deaths = $this->deaths;

        $color = MineceitUtil::getThemeColor();

        $arenaSb = TextFormat::WHITE . " $arenaStr: " . $color . $this->currentArena->getName();
        $killsSb = TextFormat::WHITE . " $killsStr: " . $color . $kills;
        $deathsSb = TextFormat::WHITE . " $deathsStr: " . $color . $deaths;

        if ($language->getLocale() === Language::ARABIC) {
            $arenaSb = ' ' . $color . $this->currentArena->getName() . TextFormat::WHITE . " :$arenaStr";
            $killsSb = ' ' . $color . $kills . TextFormat::WHITE . " :$killsStr";
            $deathsSb = ' ' . $color . $deaths . TextFormat::WHITE . " :$deathsStr";
        }

        $this->scoreboard->addLine(0, '                 ');
        $this->scoreboard->addLine(1, $arenaSb);
        $this->scoreboard->addLine(2, '');
        $this->scoreboard->addLine(3, TextFormat::WHITE . ' Ping: ' . $color . $this->getPing());
        $this->scoreboard->addLine(4, TextFormat::WHITE . " CPS: " . $color . 0);
        $this->scoreboard->addLine(5, ' ');
        $this->scoreboard->addLine(6, $killsSb);
        $this->scoreboard->addLine(7, $deathsSb);
        $this->scoreboard->addLine(8, '  ');
        $this->scoreboard->addLine(9, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');

        $this->scoreboardType = Scoreboard::SCOREBOARD_FFA;
    }

    private function setReplayScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $lang = $this->getLanguage();

        $replay = MineceitCore::getReplayManager()->getReplayFrom($this);

        if ($replay !== null) {

            $color = MineceitUtil::getThemeColor();

            $queue = $replay->getQueue();
            $ranked = $replay->isRanked();

            $duration = $replay->getDuration();

            $durationStrTop = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ';
            $durationStrBottom = ' ' . $color . "$duration" . TextFormat::WHITE . " | " . $color . $replay->getMaxDuration() . ' ';

            $queueLineTop = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE) . ': ';
            $queueLineBottom = ' ' . $color . $lang->getRankedStr($ranked) . ' ' . $queue . ' ';

            if ($lang->getLocale() === Language::ARABIC) {
                $durationStrTop = TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                $queueLineTop = TextFormat::WHITE . ' :' . $lang->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);
            }

            $this->scoreboard->addLine(0, '     ');
            $this->scoreboard->addLine(1, $durationStrTop);
            $this->scoreboard->addLine(2, $durationStrBottom);
            $this->scoreboard->addLine(3, ' ');
            $this->scoreboard->addLine(4, $queueLineTop);
            $this->scoreboard->addLine(5, $queueLineBottom);
            $this->scoreboard->addLine(6, '');
            $this->scoreboard->addLine(7, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');

            $this->scoreboardType = Scoreboard::SCOREBOARD_REPLAY;
        }
    }

    /**
     * Sets the event spectator scoreboard.
     */
    private function setEventSpectatorScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $color = MineceitUtil::getThemeColor();
        $lang = $this->getLanguage();

        $event = MineceitCore::getEventManager()->getEventFromPlayer($this);

        $eventType = $event->getName();
        $players = strval($event->getPlayers(true));
        $eliminated = strval($event->getEliminated(true));

        $playersLine = " " . $lang->getMessage(Language::PLAYERS_LABEL) . ": {$color}{$players} ";
        $eventTypeLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_EVENT_TYPE, ["type" => $eventType]) . " ";
        $eliminatedLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ["num" => $eliminated]) . " ";
        if ($lang->getLocale() === Language::ARABIC) {
            $playersLine = " {$color}{$players}" . TextFormat::WHITE . " :" . $lang->getMessage(Language::PLAYERS_LABEL) . " ";
        }

        $start = 1;

        $this->scoreboard->addLine(0, '');
        if (!$event->hasStarted()) {

            $startingTime = $event->getTimeUntilStart();
            $startingInLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_STARTING_IN, ["time" => $startingTime]) . " ";

            $this->scoreboard->addLine(1, $startingInLine);
            $this->scoreboard->addLine(2, '   ');
            $start = 3;
        }

        $this->scoreboard->addLine($start, $playersLine);
        $this->scoreboard->addLine($start + 1, $eventTypeLine);
        $this->scoreboard->addLine($start + 2, ' ');
        $this->scoreboard->addLine($start + 3, $eliminatedLine);
        $this->scoreboard->addLine($start + 4, '  ');
        $this->scoreboard->addLine($start + 5, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');

        $this->scoreboardType = Scoreboard::SCOREBOARD_EVENT_SPEC;
    }

    private function setEventDuelScoreboard(): void
    {

        $title = TextFormat::GRAY . TextFormat::BOLD . '» ' . MineceitUtil::getServerName(true) . TextFormat::GRAY . ' «';

        if ($this->scoreboard === null)
            $this->scoreboard = new Scoreboard($this, $title);
        else $this->scoreboard->clearScoreboard();

        $event = MineceitCore::getEventManager()->getEventFromPlayer($this);
        $duel = $event->getCurrentDuel();

        $opponent = $duel->getOpponent($this);

        $color = MineceitUtil::getThemeColor();
        $lang = $this->getLanguage();

        $durationStr = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . '00:00';
        $opponentStrTop = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_OPPONENT) . ': ';
        $opponentStrBottom = $color . ' ' . $opponent->getDisplayName() . ' ';
        $yourPing = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING) . ': ' . $color . $this->getPing();
        $theirPing = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING) . ': ' . $color . $opponent->getPing();
        $yourCps = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ': ' . $color . 0 . ' ';
        $theirCps = TextFormat::WHITE . ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ': ' . $color . 0 . ' ';

        if ($lang->getLocale() === Language::ARABIC) {
            $durationStr = $color . ' 00:00' . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
            $opponentStrTop = TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_OPPONENT);
            $opponentStrBottom = $color . ' ' . $opponent->getDisplayName() . ' ';
            $yourPing = $color . ' ' . $this->getPing() . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING);
            $theirPing = $color . ' ' . $opponent->getPing() . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING);
            $yourCps = ' ' . $color . 0 . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ' ';
            $theirCps = ' ' . $color . 0 . TextFormat::WHITE . ' :' . $lang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ' ';
        }

        $this->scoreboard->addLine(0, ' ');
        $this->scoreboard->addLine(1, $durationStr);
        $this->scoreboard->addLine(2, '     ');
        $this->scoreboard->addLine(3, $opponentStrTop);
        $this->scoreboard->addLine(4, $opponentStrBottom);
        $this->scoreboard->addLine(5, '  ');
        $this->scoreboard->addLine(6, $yourPing);
        $this->scoreboard->addLine(7, $yourCps);
        $this->scoreboard->addLine(8, '');
        $this->scoreboard->addLine(9, $theirPing);
        $this->scoreboard->addLine(10, $theirCps);
        $this->scoreboard->addLine(11, '    ');
        $this->scoreboard->addLine(12, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');

        $this->scoreboardType = Scoreboard::SCOREBOARD_EVENT_DUEL;
    }

    public function addQueueToScoreboard(bool $ranked, string $queue): void
    {

        $language = $this->getLanguage();

        $queueStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);

        $color = MineceitUtil::getThemeColor();

        if ($this->scoreboardType === Scoreboard::SCOREBOARD_SPAWN) {
            $this->scoreboard->addLine(8, " $queueStr:");
            $this->scoreboard->addLine(9, ' ' . $color . $language->getRankedStr($ranked) . ' ' . $queue . ' ');
            $this->scoreboard->addLine(10, '    ');
            $this->scoreboard->addLine(11, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');
        }
    }

    public function removeQueueFromScoreboard(): void
    {

        if ($this->scoreboardType === Scoreboard::SCOREBOARD_SPAWN) {
            $this->scoreboard->removeLine(10);
            $this->scoreboard->removeLine(9);
            $this->scoreboard->addLine(8, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');
        }
    }

    public function addPausedToScoreboard(): void
    {

        $language = $this->getLanguage();

        $paused = $language->scoreboard(Language::SCOREBOARD_REPLAY_PAUSED);

        if ($this->scoreboardType === Scoreboard::SCOREBOARD_REPLAY) {
            $this->scoreboard->addLine(7, TextFormat::RED . TextFormat::BOLD . " $paused");
            $this->scoreboard->addLine(8, '        ');
            $this->scoreboard->addLine(9, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');
        }
    }

    public function removePausedFromScoreboard(): void
    {

        if ($this->scoreboardType === Scoreboard::SCOREBOARD_REPLAY) {
            $this->scoreboard->removeLine(9);
            $this->scoreboard->removeLine(8);
            $this->scoreboard->addLine(7, TextFormat::GRAY . ' Twitter ' . TextFormat::WHITE . '@MineCeit ');
        }
    }

    public function removeScoreboard(): void
    {

        if ($this->scoreboard !== null)
            $this->scoreboard->removeScoreboard();

        $this->scoreboardType = Scoreboard::SCOREBOARD_NONE;
        $this->scoreboard = null;
    }

    /**
     * Reloads the scoreboard.
     */
    public function reloadScoreboard(): void
    {
        $this->setScoreboard($this->scoreboardType);
    }

    /**
     * @param int $id
     * @param string $line
     */
    public function updateLineOfScoreboard(int $id, string $line): void
    {
        if ($this->scoreboard !== null and $this->scoreboardType !== Scoreboard::SCOREBOARD_NONE) {
            $this->scoreboard->addLine($id, $line);
        }
    }

    /**
     * @return string
     */
    public function getScoreboardType(): string
    {
        return $this->scoreboardType;
    }

    /**
     * @param MineceitPlayer|CommandSender $player
     * @return bool
     */
    public function equalsPlayer($player): bool
    {
        if ($player !== null and $player instanceof MineceitPlayer)
            return $player->getName() === $this->getName() and $player->getId() === $this->getId();
        return false;
    }

    /**
     * @return int
     */
    public function getClientRandomID(): int
    {
        return intval($this->playerData['clientData']['ClientRandomId']);
    }

    /**
     * @return string
     */
    public function getDeviceId(): string
    {
        return strval($this->playerData['clientData']['DeviceId']);
    }

    /**
     * @return string
     */
    public function getDeviceModel(): string
    {

        $model = (string)$this->playerData['clientData']['DeviceModel'];

        $info = MineceitCore::getPlayerHandler()->getDeviceInfo();

        return $info->getDeviceFromModel($model) ?? $model;
    }

    /**
     * @param bool $strval
     * @return int|string
     */
    public function getDeviceOS(bool $strval = false)
    {

        $osVal = intval($this->playerData['clientData']['DeviceOS']);
        $result = $strval ? 'Unknown' : $osVal;
        if ($strval === true and isset($this->deviceOSVals[$osVal])) {
            $result = strval($this->deviceOSVals[$osVal]);
        }

        return $result;
    }

    /**
     * @param bool $strval
     * @return int|string
     */
    public function getInput(bool $strval = false)
    {
        $input = intval($this->playerData['clientData']['CurrentInputMode']);
        $result = ($strval === true) ? 'Unknown' : $input;
        if ($strval === true and isset($this->inputVals[$input])) {
            $result = strval($this->inputVals[$input]);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getUIProfile(): string
    {
        $data = $this->playerData['clientData'];
        $result = 'Unknown';
        if (isset($data['UIProfile'])) {
            $result = strval(self::UI_VALUES[$data['UIProfile']]);
        }
        return $result;
    }

    /**
     * @return MineceitPlayer|null
     */
    public function getPlayer()
    {
        return $this;
    }

    /**
     * @return bool
     *
     * Determines if the player is a pe player.
     */
    public function isPe(): bool
    {

        $deviceOS = $this->getDeviceOS();

        $invalidDevices = [
            self::PS4 => true,
            self::XBOX => true,
            self::WINDOWS_10 => true,
            self::LINUX => true,
        ];

        if (isset($invalidDevices[$deviceOS])) {
            return false;
        }

        return $deviceOS === self::TOUCH;
    }


    /**
     * @param string|AbstractKit $kit
     */
    public function setCurrentKit($kit): void
    {
        $this->currentKit = ($kit instanceof AbstractKit) ? $kit->getLocalizedName() : strval($kit);
    }

    /**
     * Clears the kit.
     */
    public function clearKit(): void
    {
        $this->currentKit = null;
    }

    /**
     * @return bool
     *
     * Determines if the player has a kit.
     */
    public function hasKit(): bool
    {
        return $this->currentKit !== null;
    }

    public function attack(EntityDamageEvent $source): void
    {

        // $time = microtime(true);

        $kits = MineceitCore::getKits();
        $duelHandler = MineceitCore::getDuelHandler();

        // To ensure that players don't have unlimited attack time
        if ($this->attackTime > 0 or $this->isCreative()) {
            $source->setCancelled();
            return;
        } elseif ($this->isInArena()) {
            if ($this->currentArena->isWithinProtection($this)) {
                $source->setCancelled();
                return;
            }
        }

        if ($this->isInDuel()) {

            $duel = $duelHandler->getDuel($this);

            if ($duel->cantDamagePlayers()) {

                $cancel = true;

                if ($duel->isSpleef() and $source instanceof EntityDamageByChildEntityEvent) {
                    $child = $source->getChild();
                    if ($child instanceof Snowball) {
                        $cancel = false;
                    }
                }

                if ($cancel) {
                    $source->setCancelled();
                    return;
                }
            }
        }

        parent::attack($source);

        if ($source->isCancelled()) return;

        $speed = $source->getAttackCooldown();

        if ($this->currentKit !== null and $kits->isKit($this->currentKit)) {

            $kit = $kits->getKit($this->currentKit);

            if ($source instanceof EntityDamageByEntityEvent) {

                $damager = $source->getDamager();

                if ($damager instanceof MineceitPlayer) {

                    if ($damager->hasKit()) $speed = $kit->getSpeed();

                    $this->attackTime = $speed;

                    if ($damager->isInArena() and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE) {
                        $damager->setInCombat(true);
                    } elseif ($damager->isInDuel() and $this->isInDuel()) {
                        $duel = $duelHandler->getDuel($this);
                        $duel->addHitTo($damager, $source->getFinalDamage());
                    }
                }
            }
        }

        // Prevents the players from getting the death screen since they are bugged as of 1.13.
        $health = $this->getHealth();

        if ($health <= 1.0) {

            $source->setCancelled();

            $core = MineceitCore::getInstance();

            $this->setInSpectatorMode();

            $this->onDeath();

            $this->clearInventory();

            $this->setHealth($this->getMaxHealth());

            if ($this->isInEvent()) {
                return;
            }

            $task = new OnDeathTask($this);

            $core->getScheduler()->scheduleDelayedTask($task, 10);
        } elseif ($this->isInArena()) {
            $this->setInCombat(true);
        }
    }


    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4): void
    {

        $xzKb = $base;
        $yKb = $base;

        $kits = MineceitCore::getKits();

        if ($this->currentKit !== null and $kits->isKit($this->currentKit)) {
            $kit = $kits->getKit($this->currentKit);
            if ($attacker instanceof MineceitPlayer and $attacker->hasKit()) {
                $xzKb = $kit->getXKb();
                $yKb = $kit->getYKb();
            }
        }


        $f = sqrt($x * $x + $z * $z);

        if ($f <= 0) return;

        if (mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()) {
            $f = 1 / $f;

            $motion = clone $this->motion;

            $motion->x /= 2;
            $motion->y /= 2;
            $motion->z /= 2;
            $motion->x += $x * $f * $xzKb;
            $motion->y += $yKb;
            $motion->z += $z * $f * $xzKb;

            if ($motion->y > $yKb) {
                $motion->y = $yKb;
            }

            $this->setMotion($motion);
        }
    }

    /**
     * @param Item $item
     * @param bool $animate
     * @return bool
     *
     * Player uses the fishing rod.
     */
    public function useRod(Item $item, bool $animate = false): bool
    {

        $exec = !$this->spectatorMode and !$this->isImmobile();

        $duelHandler = MineceitCore::getDuelHandler();
        $duel = $duelHandler->getDuel($this);

        if ($exec and $duel !== null)
            $exec = !$duel->isCountingDown();

        if ($exec) {

            $damage = false;

            $players = $this->getLevel()->getPlayers();

            if ($this->isFishing()) {
                $this->stopFishing();
                $damage = true;
            } else {
                $this->startFishing();
            }

            if ($animate) {
                $pkt = new AnimatePacket();
                $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                $pkt->entityRuntimeId = $this->getId();
                $this->getServer()->broadcastPacket($players, $pkt);
            }

            if ($damage and !$this->isCreative()) {
                $inv = $this->getInventory();
                $newItem = Item::get($item->getId(), $item->getDamage() + 1);
                if ($item->getDamage() > 65)
                    $newItem = Item::get(0);
                $inv->setItemInHand($newItem);
            }

            if ($duel !== null)
                $duel->setFishingFor($this, $this->isFishing());
        }

        return $exec;
    }

    /**
     * @param int $seconds
     *
     * Sets the player on fire.
     */
    public function setOnFire(int $seconds): void
    {
        parent::setOnFire($seconds);

        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setOnFire($this, $seconds);
        }
    }

    /**
     * Extinguishes the player.
     */
    public function extinguish(): void
    {
        parent::extinguish();
        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setOnFire($this, false);
        }
    }

    /**
     * @param EnderPearl $item
     * @param bool $animate
     * @return bool
     *
     * Throws the enderpearl.
     */
    public function throwPearl(EnderPearl $item, bool $animate = false): bool
    {

        $exec = !$this->spectatorMode and !$this->isImmobile();

        $duelHandler = MineceitCore::getDuelHandler();
        $duel = $duelHandler->getDuel($this);

        if ($exec and $duel !== null) {
            $exec = !$duel->isCountingDown();
        }

        if ($exec) {

            $players = $this->getLevel()->getPlayers();

            $item->onClickAir($this, $this->getDirectionVector());

            $this->setThrowPearl(false);

            if ($animate === true) {
                $pkt = new AnimatePacket();
                $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                $pkt->entityRuntimeId = $this->getId();
                $this->getServer()->broadcastPacket($players, $pkt);
            }

            if (!$this->isCreative()) {
                $inv = $this->getInventory();
                $index = $inv->getHeldItemIndex();
                $count = $item->getCount();
                if ($count > 1) $inv->setItem($index, Item::get($item->getId(), $item->getDamage(), $count));
                else $inv->setItem($index, Item::get(0));
            }
        }

        if ($exec and $duel !== null)
            $duel->setThrowFor($this, $item);

        return $exec;
    }

    /**
     * @param SplashPotion $item
     * @param bool $animate
     * @return bool
     *
     * Throws the potion.
     */
    public function throwPotion(SplashPotion $item, bool $animate = false): bool
    {

        $exec = !$this->spectatorMode and !$this->isImmobile();

        $duelHandler = MineceitCore::getDuelHandler();
        $duel = $duelHandler->getDuel($this);

        if ($exec and $duel !== null)
            $exec = !$duel->isCountingDown();

        if ($exec) {

            $players = $this->getLevel()->getPlayers();

            $item->onClickAir($this, $this->getDirectionVector());

            if ($animate) {
                $pkt = new AnimatePacket();
                $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                $pkt->entityRuntimeId = $this->getId();
                $this->getServer()->broadcastPacket($players, $pkt);
            }

            if (!$this->isCreative()) {
                $inv = $this->getInventory();
                $inv->setItem($inv->getHeldItemIndex(), Item::get(0));
            }
        }

        if ($exec and $duel !== null)
            $duel->setThrowFor($this, $item);

        return $exec;
    }

    /**
     * Starts fishing.
     */
    private function startFishing(): void
    {

        $player = $this->getPlayer();

        if ($player !== null and !$this->isFishing()) {

            $tag = Entity::createBaseNBT($player->add(0.0, $player->getEyeHeight(), 0.0), $player->getDirectionVector(), floatval($player->yaw), floatval($player->pitch));
            $rod = Entity::createEntity('FishingHook', $player->getLevel(), $tag, $player);

            if ($rod !== null) {
                $x = -sin(deg2rad($player->yaw)) * cos(deg2rad($player->pitch));
                $y = -sin(deg2rad($player->pitch));
                $z = cos(deg2rad($player->yaw)) * cos(deg2rad($player->pitch));
                $rod->setMotion(new Vector3($x, $y, $z));
            }

            //$item->count--;
            if (!is_null($rod) and $rod instanceof FishingHook) {
                $ev = new ProjectileLaunchEvent($rod);
                $ev->call();
                if ($ev->isCancelled()) {
                    $rod->flagForDespawn();
                } else {
                    $rod->spawnToAll();
                    $this->fishing = $rod;
                    $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
                }
            }
        }
    }

    /**
     * @param bool $click
     * @param bool $killEntity
     */
    public function stopFishing(bool $click = true, bool $killEntity = true): void
    {

        if ($this->isFishing() and $this->fishing instanceof FishingHook) {
            $rod = $this->fishing;
            if ($click === true) {
                $rod->reelLine();
            } elseif ($rod !== null) {
                if (!$rod->isClosed() and $killEntity === true) {
                    $rod->kill();
                    $rod->close();
                }
            }
        }

        $this->fishing = null;
    }

    /**
     * @return bool
     */
    public function isFishing(): bool
    {
        return $this->fishing !== null;
    }

    /**
     * Updates the tag of the player.
     */
    public function updateNameTag(): void
    {

        switch ($this->tagType) {
            case self::TAG_NORMAL:
                if ($this->frozen)
                    $this->setFrozenNameTag();
                else $this->setSpawnNameTag();
                break;
            case self::TAG_DUELS:
                $this->setDuelNameTag();
                break;
        }
    }

    /**
     * Sets the spawn name tag.
     */
    public function setSpawnNameTag(): void
    {

        $rankHandler = MineceitCore::getRankHandler();
        $rankFormat = $rankHandler->formatRanksForTag($this);
        $health = (int)$this->getHealth();
        $format = TextFormat::DARK_GRAY . '[' . TextFormat::RED . $health . TextFormat::DARK_GRAY . ']';
        $name = $rankFormat . " $format\n" . TextFormat::WHITE . $this->getDeviceOS(true) . TextFormat::GRAY . ' | ' . TextFormat::WHITE . $this->getInput(true);
        $this->setNameTag($name);
        $this->tagType = self::TAG_NORMAL;
    }

    /**
     * Sets the duels name tag.
     */
    public function setDuelNameTag(): void
    {

        $health = (int)$this->getHealth();
        $healthStr = TextFormat::DARK_GRAY . '[' . TextFormat::RED . $health . TextFormat::DARK_GRAY . ']';
        $tag = TextFormat::GREEN . $this->getDisplayName() . ' ' . $healthStr;
        $this->setNameTag($tag);
        $this->tagType = self::TAG_DUELS;
    }

    public function setFrozenNameTag(): void
    {

        $rankHandler = MineceitCore::getRankHandler();
        $rankFormat = $rankHandler->formatRanksForTag($this);
        $health = (int)$this->getHealth();
        $format = TextFormat::DARK_GRAY . '[' . TextFormat::RED . $health . TextFormat::DARK_GRAY . ']';
        $name = $rankFormat . " $format\n" . TextFormat::DARK_GRAY . '[' . TextFormat::GOLD . 'Frozen' . TextFormat::GOLD . TextFormat::DARK_GRAY . ']' . TextFormat::WHITE . ' ' . $this->getDeviceOS(true) . TextFormat::GRAY . ' | ' . TextFormat::WHITE . $this->getInput(true);
        $this->setNameTag($name);
    }

    /**
     * @param float $amount
     *
     * Sets the health of the player while updating the nametags.
     */
    public function setHealth(float $amount): void
    {
        parent::setHealth($amount);
        if ($this->tagType === self::TAG_DUELS) {
            $this->setDuelNameTag();
        } else {
            $this->setSpawnNameTag();
        }
    }

    /**
     * @param Form $form
     * @param array $addedContent
     *
     * Sends the form to a player.
     */
    public function sendFormWindow(Form $form, array $addedContent = []): void
    {
        if (!$this->lookingAtForm) {

            $formToJSON = $form->jsonSerialize();

            $content = [];

            if (isset($formToJSON['content']) and is_array($formToJSON['content'])) {
                $content = $formToJSON['content'];
            } elseif (isset($formToJSON['buttons']) and is_array($formToJSON['buttons'])) {
                $content = $formToJSON['buttons'];
            }

            if (!empty($addedContent)) {
                $content = array_replace($content, $addedContent);
            }

            $this->formData = $content;

            $this->lookingAtForm = true;

            $this->sendForm($form);
        }
    }

    /**
     * @return array
     *
     * Officially removes the form data from the player.
     */
    public function removeFormData(): array
    {

        $data = $this->formData;
        $this->formData = [];

        return $data;
    }


    /**
     * @param int $formId
     * @param mixed $responseData
     *
     * @return bool
     */
    public function onFormSubmit(int $formId, $responseData): bool
    {

        $this->lookingAtForm = false;

        $result = parent::onFormSubmit($formId, $responseData);
        if (isset($this->forms[$formId])) {
            unset($this->forms[$formId]);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isInHub(): bool
    {

        $level = $this->level;
        $defaultLevel = $this->getServer()->getDefaultLevel();
        $notInArena = $this->currentArena === null;

        return $defaultLevel !== null ? $level->getName() === $defaultLevel->getName() and $notInArena : $notInArena;
    }

    /**
     * @return bool
     */
    public function isInArena(): bool
    {
        return $this->currentArena !== null;
    }

    /**
     * @return FFAArena|null
     */
    public function getArena()
    {
        return $this->currentArena;
    }

    /**
     * @param string|FFAArena $arena
     * @return bool
     *
     * Teleports the player to the ffa arena.
     */
    public function teleportToFFAArena($arena): bool
    {

        if ($this->isFrozen() or $this->isInEvent()) {
            return false;
        }

        $arenaHandler = MineceitCore::getArenas();
        $arena = ($arena instanceof FFAArena) ? $arena : $arenaHandler->getArena($arena);

        if ($arena !== null and $arena instanceof FFAArena and $arena->isOpen()) {

            $this->setPlayerFlying(false);

            $this->currentArena = $arena;

            $inventory = $this->getInventory();
            $armorInv = $this->getArmorInventory();

            $inventory->clearAll();
            $armorInv->clearAll();

            $arena->teleportPlayer($this);

            if ($this->scoreboardType !== Scoreboard::SCOREBOARD_NONE)
                $this->setScoreboard(Scoreboard::SCOREBOARD_FFA);

            if (!$this->displayPlayer) {
                $this->showPlayers(false);
            }

            return true;
        }

        return false;
    }

    /**
     * Generally updates the player within the task.
     */
    public function update(): void
    {

        if (!$this->throwPearl) {
            $this->removeSecInThrow();
            if ($this->secsEnderpearl <= 0) $this->setThrowPearl();
        }

        if ($this->combat) {
            $this->secsInCombat--;
            if ($this->secsInCombat <= 0)
                $this->setInCombat(false);
        }

        if ($this->spam > 0) {
            $this->spam--;
        }

        if ($this->tellSpam > 0)
            $this->tellSpam--;


        if ($this->previousLimitedFeat !== $this->hasLimitedFeatures()) {
            if (!$this->hasLimitedFeatures()) {
                // TODO SEND MESSAGE
            }
        }

        if ($this->currentSecond % 5 === 0) {
            $this->doUpdatePing();
        }

        $this->previousLimitedFeat = $this->hasLimitedFeatures();
        $this->currentSecond++;
    }


    /**
     * @param int $currentTick
     *
     * Updates the cps trackers.
     */
    public function updateCPSTrackers(int $currentTick): void
    {

        if ($currentTick !== 0 and MineceitCore::AUTOCLICK_DETECTOR_ENABLED) {

            if ($this->clickSpeedCheck !== null) {

                $violations = $this->clickSpeedCheck->getViolationCount();

                if ($currentTick % 20 === 0) {

                    if ($violations > AbstractCheck::MAX_VIOLATIONS_BAN_CLICK_SPEED and !$this->hasTrialModPermissions()) {
                        MineceitUtil::autoBan($this, "Autoclick Detector");
                        // MineceitUtil::broadcastMessage("Should ban " . $this->getName() . " for autoclicking.");
                        return;
                    }
                }

                if ($currentTick % 60 === 0) {
                    $this->clickSpeedCheck->decreaseViolation();
                }

                if ($currentTick % 10 === 0 and $violations > AbstractCheck::MAX_VIOLATIONS_ALERT) {
                    $this->clickSpeedCheck->sendAlert();
                }
            }

            if ($this->diggingCheck !== null) {

                $violations = $this->diggingCheck->getViolationCount();

                if ($currentTick % 20 === 0) {

                    if ($violations > AbstractCheck::MAX_VIOLATIONS_BAN_DIGGING and !$this->hasTrialModPermissions()) {
                        MineceitUtil::autoBan($this, "Autoclick Detector");
                        // MineceitUtil::broadcastMessage("Should ban " . $this->getName() . " for autoclicking.");
                        return;
                    }

                    $this->consistencyCheck->decreaseViolation();
                }

                if ($currentTick % 10 === 0) {
                    if ($violations > AbstractCheck::MAX_VIOLATIONS_ALERT) {
                        $this->diggingCheck->sendAlert();
                    }
                    $this->diggingCheck->decreaseViolation();
                }
            }

            if ($this->consistencyCheck !== null) {

                $violations = $this->consistencyCheck->getViolationCount();

                if ($currentTick % 20 === 0) {

                    if ($violations > AbstractCheck::MAX_VIOLATIONS_BAN_CONSISTENCY and !$this->hasTrialModPermissions()) {
                        MineceitUtil::autoBan($this, "Autoclick Detector");
                        // MineceitUtil::broadcastMessage("Should ban " . $this->getName() . " for autoclicking.");
                        return;
                    }

                    // $this->consistencyCheck->decreaseViolation();
                }

                if ($currentTick % 10 === 0 and $violations > AbstractCheck::MAX_VIOLATIONS_ALERT) {
                    $this->consistencyCheck->sendAlert();
                }
            }
        }
    }


    /**
     * Updates the cps of the player.
     */
    public function updateCps(): void
    {

        $clicksInfo = $this->getClicksInfo();
        $update = $clicksInfo->updateCPS();
        if (!$update) {
            return;
        }

        $cps = $clicksInfo->getCps();

        $color = MineceitUtil::getThemeColor();

        $yourLang = $this->getLanguage();

        $duel = MineceitCore::getDuelHandler()->getDuel($this);

        if ($this->scoreboardType === Scoreboard::SCOREBOARD_FFA) {
            $this->updateLineOfScoreboard(4, TextFormat::WHITE . " CPS: " . $color . $cps);
        } elseif ($duel !== null) {

            $duel = MineceitCore::getDuelHandler()->getDuel($this);

            if (($opponent = $duel->getOpponent($this)) !== null) {

                $theirLang = $opponent->getLanguage();

                $yourCps = TextFormat::WHITE . ' ' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ': ' . $color . $cps . ' ';

                if ($yourLang->getLocale() === Language::ARABIC)
                    $yourCps = ' ' . $color . $cps . TextFormat::WHITE . ' :' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ' ';

                $theirCps = TextFormat::WHITE . ' ' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ': ' . $color . $cps . ' ';

                if ($theirLang->getLocale() === Language::ARABIC)
                    $theirCps = ' ' . $color . $cps . TextFormat::WHITE . ' :' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ' ';

                $this->updateLineOfScoreboard(7, $yourCps);
                $opponent->updateLineOfScoreboard(10, $theirCps);
            }
        } elseif ($this->isInEventDuel()) {

            $event = MineceitCore::getEventManager()->getEventFromPlayer($this);
            $eventDuel = $event->getCurrentDuel();

            $opponent = $eventDuel->getOpponent($this);

            if ($eventDuel->getStatus() < 2 and $opponent !== null) {

                $theirLang = $opponent->getLanguage();

                $yourCps = TextFormat::WHITE . ' ' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ': ' . $color . $cps . ' ';

                if ($yourLang->getLocale() === Language::ARABIC)
                    $yourCps = ' ' . $color . $cps . TextFormat::WHITE . ' :' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_CPS) . ' ';

                $theirCps = TextFormat::WHITE . ' ' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ': ' . $color . $cps . ' ';

                if ($theirLang->getLocale() === Language::ARABIC)
                    $theirCps = ' ' . $color . $cps . TextFormat::WHITE . ' :' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_CPS) . ' ';

                $this->updateLineOfScoreboard(7, $yourCps);
                $opponent->updateLineOfScoreboard(10, $theirCps);
            }
        }
    }

    /**
     * @param bool $clickedBlock
     * @param Position|null $posClicked
     *
     * Adds the cps to the player.
     */
    public function addCps(bool $clickedBlock, Position $posClicked = null): void
    {

        if (MineceitCore::AUTOCLICK_DETECTOR_ENABLED) {

            if ($this->clickSpeedCheck !== null) {
                $this->clickSpeedCheck->addClick();
            }

            if ($this->consistencyCheck !== null) {
                $this->consistencyCheck->addClick();
            }
        }

        if ($clickedBlock) {
            if ($this->lastAction === PlayerActionPacket::ACTION_ABORT_BREAK && $this->currentAction === PlayerActionPacket::ACTION_START_BREAK) {
                $difference = $this->currentActionTime - $this->lastActionTime;
                var_dump($difference);
                if ($difference > 5 && $this->clicksInfo !== null) {
                    $this->clicksInfo->addClick($clickedBlock);
                    if ($this->diggingCheck !== null and $posClicked !== null) {
                        $this->diggingCheck->checkBlocks($posClicked);
                    }
                }
            }
        } else {
            $this->clicksInfo->addClick($clickedBlock);
        }
    }

    /**
     * @return ClicksInfo
     *
     * Gets the clicks info of the player.
     */
    public function getClicksInfo(): ClicksInfo
    {
        if ($this->clicksInfo === null) {
            $this->clicksInfo = new ClicksInfo($this);
        }

        return $this->clicksInfo;
    }

    /**
     * Sets the player in normal spam.
     */
    public function setInSpam(): void
    {
        $this->spam = 5;
    }

    /**
     * Sets the player in tell spam.
     */
    public function setInTellSpam(): void
    {
        $this->tellSpam = 5;
    }

    /**
     *
     * @param bool $command
     *
     * @return bool
     */
    public function canChat(bool $command = false): bool
    {

        $spam = $command ? $this->tellSpam : $this->spam;

        if ($spam > 0 or $this->isMuted()) {

            if (!$this->isOp()) {

                $lang = $this->getLanguage();

                $msg = null;

                if ($spam > 0) {
                    $msg = $lang->getMessage(Language::NO_SPAM);
                } elseif ($this->isMuted()) {
                    $this->sendPopup($lang->getMessage(Language::MUTED));
                }

                if ($msg !== null) {
                    $this->sendMessage(MineceitUtil::getPrefix() . " " . TextFormat::RESET . $msg);
                }

                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * @param bool $throw
     * @param bool $message
     */
    public function setThrowPearl(bool $throw = true, bool $message = true): void
    {

        $language = $this->getLanguage();

        if (!$throw) {

            $this->secsEnderpearl = 15;
            $this->setXpProgress(1);
            $this->setXpLevel(15);

            $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::SET_IN_ENDERPEARLCOOLDOWN);

            if ($message and $this->throwPearl) {
                $this->sendMessage($msg);
            }

        } else {

            $this->secsEnderpearl = 0;
            $this->setXpProgress(0);
            $this->setXpLevel(0);

            $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::REMOVE_FROM_ENDERPEARLCOOLDOWN);

            if ($message and !$this->throwPearl) {
                $this->sendMessage($msg);
            }
        }

        $this->throwPearl = $throw;
    }

    /**
     * @return bool
     */
    public function canThrowPearl(): bool
    {
        return $this->throwPearl;
    }

    /**
     * Updates the seconds in enderpearl cooldown.
     */
    private function removeSecInThrow(): void
    {
        $this->secsEnderpearl--;
        $maxSecs = 15;
        $sec = $this->secsEnderpearl;
        if ($sec < 0) $sec = 0;
        $percent = floatval($this->secsEnderpearl / $maxSecs);
        if ($percent < 0) $percent = 0;
        $p = $this->getPlayer();
        $p->setXpLevel($sec);
        $p->setXpProgress($percent);
    }

    /**
     * @param bool $combat
     * @param bool $sendMessage
     */
    public function setInCombat(bool $combat = true, bool $sendMessage = true): void
    {

        $language = $this->getLanguage();

        if ($combat) {
            $this->secsInCombat = 10;
            $msg = MineceitUtil::getPrefix() . ' ' . $language->generalMessage(Language::SET_IN_COMBAT);
            if (!$this->combat and $sendMessage) {
                $this->sendMessage($msg);
            }

        } else {
            $this->secsInCombat = 0;
            $msg = MineceitUtil::getPrefix() . ' ' . $language->generalMessage(Language::REMOVE_FROM_COMBAT);
            if ($this->combat and $sendMessage) {
                $this->sendMessage($msg);
            }
        }

        $this->combat = $combat;
    }

    /**
     * @return bool
     */
    public function isInCombat(): bool
    {
        return $this->combat;
    }

    /**
     * @param bool $clearInv
     * @param bool $teleportSpawn
     */
    public function reset(bool $clearInv = false, bool $teleportSpawn = false): void
    {

        $this->currentArena = null;

        if ($this->isSpectator()) {
            $this->setInSpectatorMode(false);
        }

        if ($this->displayPlayer === false)
            $this->hidePlayers();
        else $this->showPlayers();

        $this->setFood($this->getMaxFood());
        $this->setSaturation($this->getMaxSaturation());

        $this->setHealth($this->getMaxHealth());

        $this->setXpLevel(0);
        $this->setXpProgress(0);

        $this->setGamemode(0);

        $this->clearKit();

        $this->extinguish();

        $this->removeAllEffects();

        if ($this->isImmobile()) {
            $this->setImmobile(false);
        }

        if ($this->combat) {
            $this->setInCombat(false, false);
        }

        if ($this->throwPearl) {
            $this->setThrowPearl(true);
        }

        if ($clearInv) {
            $this->clearInventory();
        }

        if ($teleportSpawn) {
            $level = $this->getServer()->getDefaultLevel();
            if ($level !== null) {
                $pos = $level->getSpawnLocation();
                $this->teleport($pos);
            }
            $this->setAllowFlight($this->canFlyInLobby());
        }
    }

    /**A
     * @return float
     */
    public function getMaxSaturation(): float
    {
        return $this->attributeMap->getAttribute(Attribute::SATURATION)->getMaxValue();
    }

    public function respawn(): void
    {

        parent::respawn();

        $this->currentArena = null;

        if ($this->isSpectator()) {
            $this->setInSpectatorMode(false);
        }

        if ($this->isFlying()) {
            $this->setFlying(false);
        }

        if ($this->displayPlayer) {
            $this->showPlayers();
        } else {
            $this->hidePlayers();
        }

        // TODO RE ADD DISPLAYING PLAYERS EVENTUALLY

        /* $players = $this->getLevel()->getPlayers();

        foreach($players as $player) {
            if($player instanceof MineceitPlayer) {
                $display = $player->isDisplayPlayers();
                if($display === false)
                    $player->displayPlayers(false);
            }
        } */

        if ($this->scoreboardType !== Scoreboard::SCOREBOARD_NONE) {
            $this->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
        }

        $this->setInCombat(false, false);
        $this->setThrowPearl(true);

        MineceitCore::getItemHandler()->spawnHubItems($this, true);

        $this->dead = false;

        $this->setAllowFlight($this->canFlyInLobby());
    }

    /**
     * @param int $gm
     * @param bool $client
     * @return bool
     */
    public function setGamemode(int $gm, bool $client = false): bool
    {

        $result = parent::setGamemode($gm, $client);

        if ($gm === 3) {
            $this->spectatorMode = $result;
        } else $this->spectatorMode = false;
        return $result;
    }

    /**
     * @param bool $spec
     * @param bool $forDuels
     */
    public function setInSpectatorMode(bool $spec = true, bool $forDuels = false): void
    {

        if ($spec === true) {

            if (!$forDuels) $this->setGamemode(3);
            else {
                // canHitPlayer = false
                $this->setGamemode(0);
                $this->setInvisible(true);
                $this->setPlayerFlying(true);
                $this->spectatorMode = true;
            }
        } else {
            // canHitPlayer = true
            $this->setGamemode(0);
            $this->setInvisible(false);
            $this->setPlayerFlying(false);
            $this->spectatorMode = false;
        }
    }

    /**
     * @return bool
     */
    public function isPlayerFlying(): bool
    {
        return $this->getAllowFlight();
    }

    /**
     * @param bool $res
     */
    public function setPlayerFlying(bool $res = true): void
    {
        $this->setAllowFlight($res);
        $this->setFlying($res);
    }


    /**
     * @return bool
     *
     * Determines whether the player can fly in the lobby.
     */
    public function canFlyInLobby(): bool
    {

        foreach ($this->ranks as $rank) {
            if ($rank->canFly()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isSpectator(): bool
    {
        return parent::isSpectator() or $this->spectatorMode;
    }

    /**
     * @return bool
     */
    public function isADuelSpec(): bool
    {
        $duelHandler = MineceitCore::getDuelHandler();
        $duel = $duelHandler->getDuelFromSpec($this);
        return $duel !== null;
    }

    /**
     * @return bool
     *
     * Determines if the player has trial mod permissions.
     */
    public function hasTrialModPermissions(): bool
    {
        foreach ($this->ranks as $rank) {
            if ($rank->getPermission() !== Rank::PERMISSION_NONE) {
                return true;
            }
        }

        return $this->isOp();
    }


    /**
     * @return bool
     *
     * Determines if the player has mod permissions.
     */
    public function hasModPermissions(): bool
    {

        foreach ($this->ranks as $rank) {
            if ($rank->getPermission() === Rank::PERMISSION_MOD or $rank->getPermission() === Rank::PERMISSION_ADMIN or $rank->getPermission() === Rank::PERMISSION_OWNER) {
                return true;
            }
        }

        return $this->isOp();
    }


    /**
     * @return bool
     *
     * Determines if the player has admin permissions.
     */
    public function hasAdminPermissions(): bool
    {

        foreach ($this->ranks as $rank) {
            if ($rank->getPermission() === Rank::PERMISSION_ADMIN or $rank->getPermission() === Rank::PERMISSION_OWNER) {
                return true;
            }
        }

        return $this->isOp();
    }


    /**
     * @return bool
     *
     * Determines if the player has owner permissions.
     */
    public function hasOwnerPermissions(): bool
    {

        foreach ($this->ranks as $rank) {
            if ($rank->getPermission() === Rank::PERMISSION_OWNER) {
                return true;
            }
        }

        return $this->isOp();
    }


    /**
     * @return bool
     *
     * Determines if the player has the permission to fly in the lobby.
     */
    public function hasFlightPermission(): bool
    {

        foreach ($this->ranks as $rank) {
            if ($rank->canFly()) {
                return true;
            }
        }

        return $this->isOp();
    }


    /**
     * Occurs when players die.
     */
    public function onDeath(): void
    {

        if (!$this->dead) {

            $this->dead = true;

            $this->setInCombat(false, false);
            $this->setThrowPearl(true, false);

            $cause = $this->getLastDamageCause();

            $addDeath = false;

            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $event = MineceitCore::getEventManager()->getEventFromPlayer($this);

            // MineceitUtil::spawnLightningBolt($this);

            $skip = false;

            if ($cause !== null) {

                $causeAction = $cause->getCause();

                if ($causeAction === EntityDamageEvent::CAUSE_SUICIDE or $causeAction === EntityDamageEvent::CAUSE_VOID) {

                    if ($duel !== null) {
                        $duel->setEnded();
                        $skip = true;
                    } elseif ($this->isInEventDuel()) {
                        $eventDuel = $event->getCurrentDuel();
                        $eventDuel->setResults();
                        $skip = true;
                    }
                }

                if (!$skip) {

                    $duelWinner = null;

                    if ($cause instanceof EntityDamageByEntityEvent) {

                        $killer = $cause->getDamager();

                        if ($killer !== null and $killer instanceof MineceitPlayer) {

                            if ($this->isInArena()) {

                                $killer->addKill();
                                $killer->setInCombat(false, false);
                                $addDeath = true;

                            } elseif ($duel !== null) {
                                $killer = $killer->getPlayer();
                                if ($duel->isPlayer($killer)) {
                                    $duelWinner = $killer;
                                }
                            } elseif ($this->isInEventDuel()) {
                                $eventDuel = $event->getCurrentDuel();
                                if ($eventDuel->isPlayer($killer)) {
                                    $duelWinner = $killer;
                                }
                            }
                        }
                    } elseif ($cause instanceof EntityDamageByChildEntityEvent) {

                        $childOwner = $cause->getDamager();

                        if ($childOwner instanceof MineceitPlayer and $duel !== null and $duel->isPlayer($childOwner)) {
                            $duelWinner = $childOwner->getPlayer();
                        }
                    }

                    if ($duel !== null) {
                        $duel->setEnded($duelWinner);
                    } elseif ($this->isInEventDuel()) {
                        $eventDuel = $event->getCurrentDuel();
                        $eventDuel->setResults($duelWinner);
                    }
                }

                if ($addDeath) {
                    $this->addDeath();
                }
            }


            // ------------------------------------------------------------------


            //Crafting grid must always be evacuated even if keep-inventory is true. This dumps the contents into the
            //main inventory and drops the rest on the ground.
            $this->doCloseInventory();

            $ev = new PlayerDeathEvent($this, []);
            $ev->call();

            if (!$ev->getKeepInventory()) {
                foreach ($ev->getDrops() as $item) {
                    $this->level->dropItem($this, $item);
                }

                if ($this->inventory !== null) {
                    $this->inventory->setHeldItemIndex(0);
                    $this->inventory->clearAll();
                }
                if ($this->armorInventory !== null) {
                    $this->armorInventory->clearAll();
                }
            }

            $this->level->dropExperience($this, 0);
            $this->setXpAndProgress(0, 0.0);

            $msg = $ev->getDeathMessage();

            if ($duel !== null) {
                $opponent = $duel->getOpponent($this);
                $duel->broadcastDeathMessage($opponent, $this);
            } elseif ($msg !== '') {
                if ($msg instanceof TextContainer)
                    $this->getServer()->broadcastMessage($msg);
                else MineceitUtil::broadcastMessage($msg);
            }
        }
    }

    /**
     * @return bool
     */
    public function isInQueue(): bool
    {
        $duelHandler = MineceitCore::getDuelHandler();
        return $duelHandler->isInQueue($this);
    }

    /**
     * @return bool
     *
     * Determines if the player is in an event.
     */
    public function isInEvent(): bool
    {
        $eventManager = MineceitCore::getEventManager();
        return $eventManager->getEventFromPlayer($this) !== null;
    }

    /**
     * @return bool
     *
     * Determines if the player is in an event duel.
     */
    public function isInEventDuel(): bool
    {
        if ($this->isInEvent()) {
            $eventManager = MineceitCore::getEventManager();
            $event = $eventManager->getEventFromPlayer($this);
            return ($duel = $event->getCurrentDuel()) !== null and $duel->isPlayer($this);
        }
        return false;
    }

    /**
     * @param bool $fromLocale
     *
     * @return Language|null
     *
     * Gets the language of the player either from locale or from the current language.
     */
    public function getLanguage(bool $fromLocale = false)
    {

        $playerHandler = MineceitCore::getPlayerHandler();

        $localeLang = $playerHandler->getLanguage($this->locale);

        if ($fromLocale) {
            return $localeLang ?? $playerHandler->getLanguage();
        }

        return $playerHandler->getLanguage($this->currentLang);
    }


    /**
     * @return bool
     *
     * Checks weather the player has a different language from assigned & locale.
     */
    public function hasDifferentLocale(): bool
    {

        $localeLang = $this->getLanguage(true);

        $regularLang = $this->getLanguage();

        return !$localeLang->equals($regularLang);
    }

    /**
     * @param string $lang
     *
     * Sets the language of the player.
     */
    public function setLanguage(string $lang): void
    {
        $this->currentLang = $lang;
    }

    /**
     * Clears the inventory of the player.
     */
    public function clearInventory(): void
    {
        $this->getArmorInventory()->clearAll();
        $this->getInventory()->clearAll();
    }

    /**
     * @param bool $frozen
     * Used for /freeze to not get confused for immobile.
     */
    public function setFrozen(bool $frozen = false): void
    {
        $this->setImmobile($frozen);
        $this->frozen = $frozen;
    }

    /**
     * @return bool
     * Used to determine if player is getting ssed or not.
     */
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * @return bool
     *
     * Determines if the player is in a duel.
     */
    public function isInDuel(): bool
    {
        $duelHandler = MineceitCore::getDuelHandler();
        $duel = $duelHandler->getDuel($this);
        return $duel !== null;
    }

    /**
     * @param string $title
     * @param string $subtitle
     * @param int $fadein
     * @param int $stay
     * @param int $fadeout
     *
     * Sends the player a title.
     */
    public function sendTitle(string $title, string $subtitle = '', int $fadein = 0, int $stay = 0, int $fadeout = 0): void
    {

        $this->setTitleAnimationTimes($fadein, $stay, $fadeout);
        $this->setSubTitle($subtitle);

        $pkt = new SetTitlePacket();
        $pkt->text = $title;
        $pkt->type = SetTitlePacket::TYPE_SET_TITLE;
        $this->dataPacket($pkt);
    }

    /**
     * @param int $fadein
     * @param int $stay
     * @param int $fadeout
     *
     * Sets the title animation times.
     */
    private function setTitleAnimationTimes(int $fadein, int $stay, int $fadeout): void
    {

        if ($fadein !== 0 and $stay !== 0 and $fadeout !== 0) {

            $pkt = new SetTitlePacket();
            $pkt->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
            $pkt->fadeInTime = $fadein;
            $pkt->fadeOutTime = $fadeout;
            $pkt->stayTime = $stay;
            $this->dataPacket($pkt);
        }
    }

    /**
     * @param string $subtitle
     *
     * Sets the subtitle.
     */
    private function setSubTitle(string $subtitle)
    {
        if ($subtitle !== '') {
            $pkt = new SetTitlePacket();
            $pkt->type = SetTitlePacket::TYPE_SET_SUBTITLE;
            $pkt->text = $subtitle;
            $this->dataPacket($pkt);
        }
    }

    /**
     * @param int $tickDiff
     *
     * Does the food tick.
     */
    protected function doFoodTick(int $tickDiff = 1): void
    {

        if ($this->isInHub() or ($this->isInEvent() and !$this->isInEventDuel())) {
            $this->setFood($this->getMaxFood());
            $this->setSaturation($this->getMaxSaturation());
            return;
        }

        parent::doFoodTick($tickDiff);
    }

    /**
     * @param DuelInfo $winner
     * @param DuelInfo $loser
     * @param bool $draw
     */
    public function addToDuelHistory(DuelInfo $winner, DuelInfo $loser, bool $draw = false): void
    {
        $this->duelHistory[] = ['winner' => $winner, 'loser' => $loser, 'draw' => $draw];
    }


    /**
     * @param DuelReplayInfo $info
     */
    public function addReplayDataToDuelHistory(DuelReplayInfo $info): void
    {
        $length = count($this->duelHistory);
        if ($length <= 0) {
            return;
        }
        $lastIndex = $length - 1;
        $this->duelHistory[$lastIndex]['replay'] = $info;
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getDuelInfo(int $id)
    {

        $result = null;

        if (isset($this->duelHistory[$id])) {
            $result = $this->duelHistory[$id];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDuelHistory(): array
    {
        return $this->duelHistory;
    }


    /**
     * Turns on the coordinates of the client. -> Unused
     */
    public function turnOnCoords(): void
    {
        $pkt = new GameRulesChangedPacket();
        $pkt->gameRules = ['showcoordinates' => [1, true]];
        $this->dataPacket($pkt);
    }

    /**
     * @return bool
     *
     * Determines if the player is in a party.
     */
    public function isInParty(): bool
    {
        $partyManager = MineceitCore::getPartyManager();
        return $partyManager->getPartyFromPlayer($this) !== null;
    }

    public function jump(): void
    {
        parent::jump();
        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setJumpFor($this);
        }
    }

    public function consumeObject(Consumable $consumable): bool
    {
        $drink = $consumable instanceof Potion;

        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setConsumeFor($this, $drink);
        }

        return parent::consumeObject($consumable);
    }


    public function addEffect(EffectInstance $effect): bool
    {
        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setEffectFor($this, $effect);
        }
        return parent::addEffect($effect);
    }


    /**
     * @return bool
     */
    public function isWatchingReplay(): bool
    {
        $replayManager = MineceitCore::getReplayManager();
        return $replayManager->getReplayFrom($this) !== null;
    }

    /**
     * @return ArmorInventory|null
     */
    public function getArmorInventory(): ArmorInventory
    {
        return $this->armorInventory;
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function dropItem(Item $item): bool
    {
        if (!$this->spawned or !$this->isAlive()) {
            return false;
        }

        if ($item->isNull()) {
            $this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
            return true;
        }

        $motion = $this->getDirectionVector()->multiply(0.4);

        $this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setDropItem($this, $item, $motion);
        }

        return true;
    }

    /**
     * @param bool $value
     */
    public function setSneaking(bool $value = true): void
    {
        parent::setSneaking($value);
        if ($this->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($this);
            $duel->setSneakingFor($this, $value);
        }
    }


    /**
     * @return bool
     *
     * Determines whether the player translates all of the messages.
     */
    public function doesTranslateMessages(): bool
    {
        return $this->translate;
    }


    /**
     * @param bool $value
     *
     * Sets the translate value.
     */
    public function setTranslateMessages(bool $value = true): void
    {
        $this->translate = $value;
    }


    /**
     * @param bool $value
     * @param bool $sendMessage
     * @param bool $alias
     *
     * Sets the player with limited feature mode.
     */
    public function setLimitedFeatureMode(bool $value, bool $sendMessage = true, bool $alias = false): void
    {

        if (!MineceitCore::LIMITED_FEATURES_ENABLED) {
            return;
        }

        // Time is in seconds.
        $limitedFeatures = $this->hasLimitedFeatures();

        if (!$value) {
            $this->limitedFeaturesTime = 0;
        } else {
            $this->limitedFeaturesTime = time() + MineceitUtil::ONE_WEEK_SECONDS * 2;
        }

        if ($limitedFeatures !== $value) {
            // TODO SEND MESSAGE
        }

        $playerHandler = MineceitCore::getPlayerHandler();
        $aliases = $this->getAliases();
        $server = $this->getServer();

        foreach ($aliases as $alia) {
            if ($alia !== $this->getName()) {
                if (($player = $server->getPlayer($alia)) !== null and $player instanceof MineceitPlayer and $player->isOnline()) {
                    $player->setLimitedFeatureMode($value, $sendMessage, false);
                } else {
                    $playerHandler->updatePlayerData((string)$alia, ['limited-features' => $this->limitedFeaturesTime, 'lastTimePlayed' => time()]);
                }
            }
            // TODO UPDATE DATA OF ALIASES
        }
    }

    /**
     * Determines if player has limited features.
     *
     * @return bool
     */
    public function hasLimitedFeatures(): bool
    {

        $limitedFeatures = $this->limitedFeaturesTime - time();
        return $limitedFeatures > 0;
    }

    /**
     * Gets the information of the player.
     *
     * @param Language|null $lang
     *
     * @return array|string[]
     */
    public function getInfo(Language $lang = null): array
    {

        $x = (int)$this->x;
        $y = (int)$this->y;
        $z = (int)$this->z;

        $levelName = $this->level->getName();

        $pRanks = $this->getRanks();

        $ranks = [];

        $locale = $lang != null ? $lang->getLocale() : null;

        foreach ($pRanks as $rank) {
            $ranks[] = $rank->getName();
        }

        $ranksStr = implode(", ", $ranks);
        if (strlen($ranksStr) <= 0) {
            $ranksStr = $lang->getMessage(Language::NONE);
        }

        $aliases = $this->getAliases();

        $clicksInfo = $this->getClicksInfo();

        return [
            "Name" => $this->getName() . ($this->isDisguised() ? " (Disguise -> {$this->getDisplayName()})" : ""),
            "IP" => $this->getAddress(),
            "Ping" => $this->getPing(),
            "Version" => $this->version,
            "Device OS" => $this->getDeviceOS(true),
            "Device Model" => $this->getDeviceModel(),
            "Device ID" => $this->getDeviceId(),
            "UI" => $this->getUIProfile(),
            "Ranks" => $ranksStr,
            //"Gui Scale" => $this->getGuiScale()
            "Controls" => $this->getInput(true),
            "Health" => intval($this->getHealth()) . "HP",
            "Position" => "X: $x, Y: $y, Z: $z",
            "Level" => $levelName,
            "Language" => $this->getLanguage()->getNameFromLocale($locale ?? Language::ENGLISH_US),
            "Aliases" => implode(", ", $aliases),
            "Current CPS" => $clicksInfo->getCps(),
            /* "Avg CPS" => $clicksInfo->getAverageCPS(true, 3),
            "Avg Click Duration" => $clicksInfo->getAverageDurationClicked(true, true, 3),
            "Total Avg Consistency" => $clicksInfo->getAverageConsistency(false, true, 3),
            "Previous Avg Consistency" => $clicksInfo->getLastAvgConsistency(), */
            "Limited Features" => $this->hasLimitedFeatures() ? "True" : "False"
        ];
    }


    /**
     * Adds a kill to the player.
     */
    public function addKill(): void
    {

        $this->kills += 1;

        $kills = $this->kills;

        $language = $this->getLanguage();

        $color = MineceitUtil::getThemeColor();

        $killsStr = $language->scoreboard(Language::FFA_SCOREBOARD_KILLS);
        $killsSb = TextFormat::WHITE . " $killsStr: " . $color . $kills;
        if ($language->getLocale() === Language::ARABIC)
            $killsSb = ' ' . $color . $kills . TextFormat::WHITE . " :$killsStr";

        $this->updateLineOfScoreboard(6, $killsSb);
        $this->setHealth($this->getMaxHealth());
    }

    /**
     * Gets the number of kills the player has.
     *
     * @return int
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * Adds a death to the player.
     */
    public function addDeath(): void
    {
        $this->deaths += 1;
    }

    /**
     * Gets the number of deaths the player has.
     *
     * @return int
     */
    public function getDeaths(): int
    {
        return $this->deaths;
    }

    /**
     * @return bool
     *
     * Determines if the player is set to pe only queues.
     */
    public function isPeOnly(): bool
    {
        return $this->peOnly;
    }

    /**
     * @param bool $enabled
     *
     * Sets the player as queued for pes only.
     */
    public function setPeOnly(bool $enabled): void
    {
        $this->peOnly = $enabled;
    }

    /**
     * @return bool
     *
     * Determines if the player has scoreboard enabled.
     */
    public function isScoreboardEnabled(): bool
    {
        return $this->scoreboardEnabled;
    }


    /**
     * @param bool $enabled
     *
     * Sets the scoreboard as enabled.
     */
    public function setScoreboardEnabled(bool $enabled): void
    {
        $this->scoreboardEnabled = $enabled;
    }


    /**
     * @param string $kit
     * @return int|array|int[]|null
     *
     * Gets the elo based on kit.
     */
    public function getElo(string $kit = null)
    {

        if ($kit !== null and isset($this->elo[strtolower($kit)]))
            return (int)$this->elo[strtolower($kit)];
        elseif ($kit !== null and $kit === 'global') {
            $result = 0;
            $values = array_values($this->elo);
            $length = count($this->elo);
            foreach ($values as $value) {
                $result += $value;
            }

            if ($length === 0) {
                return null;
            }
            return intval($result / $length);
        } elseif ($kit !== null and !isset($this->elo[strtolower($kit)])) {
            $kits = MineceitCore::getKits();
            $kit = strtolower($kit);
            if ($kits->isKit($kit)) {
                $this->elo[$kit] = 1000;
                return $this->elo[$kit];
            }
        }

        return $this->elo;
    }


    /**
     * @param string $kit
     * @param int $elo
     *
     * Sets the elo based on kit.
     */
    public function setElo(string $kit, int $elo): void
    {
        $this->elo[$kit] = $elo;
    }

    /**
     * @return bool
     *
     * Determines if player can place & break blocks.
     */
    public function isBuilderModeEnabled(): bool
    {
        return $this->builderMode;
    }

    /**
     * @param bool $builderMode
     *
     * Gives the player the ability to place and break blocks.
     */
    public function setBuilderMode(bool $builderMode): void
    {
        $this->builderMode = $builderMode;
    }

    /**
     * @return bool
     *
     * Determines whether the player can build.
     */
    public function canBuild(): bool
    {

        if (!$this->getPermission(MineceitPlayer::PERMISSION_BUILDER_MODE) and !$this->isOp()) {
            return false;
        }

        $level = $this->getLevel();

        return $this->builderMode and isset($this->builderLevels[$level->getName()]) and $this->builderLevels[$level->getName()];
    }


    /**
     * @param string $level
     * @param bool $value
     *
     * Sets whether the player can build in the level.
     */
    public function setBuildInLevel(string $level, bool $value): void
    {

        if (isset($this->builderLevels[$level])) {
            $this->builderLevels[$level] = $value;
        }
    }


    /**
     * @return array
     */
    public function getBuilderLevels(): array
    {
        return $this->builderLevels;
    }


    /**
     * Updates the builder levels.
     */
    public function updateBuilderLevels(): void
    {

        $levels = $this->getServer()->getLevels();
        $duelManager = MineceitCore::getDuelHandler();
        $replayManager = MineceitCore::getReplayManager();

        foreach ($levels as $level) {
            $name = $level->getName();
            if ($duelManager->isDuelLevel($name) or $replayManager->isReplayLevel($level)) {
                continue;
            }

            if (!isset($this->builderLevels[$name])) {
                $this->builderLevels[$name] = true;
            }
        }
    }


    /**
     * @param bool $muted
     *
     * Sets the player as muted.
     */
    public function setMuted(bool $muted): void
    {

        if ($muted !== $this->muted) {

            $lang = $this->getLanguage();

            if ($muted) {
                $this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->getMessage(Language::PLAYER_SET_MUTED));
            } else {
                $this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->getMessage(Language::PLAYER_SET_UNMUTED));
            }
        }

        $this->muted = $muted;
    }


    /**
     * @return bool
     *
     * Determines if the player is muted or not.
     */
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * @param string $type
     * @return bool
     *
     * Determines if the player has a permission.
     */
    public function getPermission(string $type): bool
    {

        $callable = null;

        switch ($type) {
            case self::PERMISSION_BUILDER_MODE:
                $callable = function (Rank $rank) {
                    return $rank->canEditWorld();
                };
                break;
            case self::PERMISSION_LIGHTNING_ON_DEATH:
                $callable = function (Rank $rank) {
                    return $rank->activateLightningOnKill();
                };
                break;
            case self::PERMISSION_RESERVE_EVENT_SLOT:
                $callable = function (Rank $rank) {
                    return $rank->reserveSpotInEvent();
                };
                break;
            case self::PERMISSION_TAG:
                $callable = function (Rank $rank) {
                    return $this->changeTag or $rank->canChangeTag();
                };
                break;
        }

        if ($callable !== null) {
            return $this->isOp() or $this->getPermissionFromRanks($callable);
        }

        return $this->isOp();
    }

    /**
     * @return int
     *
     * Gets the report permissions of the players.
     */
    public function getReportPermissions(): int
    {

        if ($this->hasAdminPermissions()) {
            return ReportInfo::PERMISSION_MANAGE_REPORTS;
        } elseif ($this->hasTrialModPermissions()) {
            return ReportInfo::PERMISSION_VIEW_ALL_REPORTS;
        }

        return ReportInfo::PERMISSION_NORMAL;
    }


    /**
     * @param string $tag
     * @return bool
     *
     * Sets the player's custom tag.
     */
    public function setCustomTag(string $tag): bool
    {

        $perm = $this->getPermission(self::PERMISSION_TAG);

        if ($perm) {

            $oldTag = $this->customTag;

            $lengthOfNew = strlen(TextFormat::clean($tag));

            if ($oldTag === $tag or ($lengthOfNew <= 0 or $lengthOfNew > 15)) return false;

            $this->customTag = $tag;

            return true;
        }

        return false;
    }

    /**
     * @return string
     *
     * Gets the player's custom tag.
     */
    public function getCustomTag(): string
    {
        return $this->customTag;
    }

    /**
     *
     * Gets the ranks of the player.
     *
     * @param bool $asString
     *
     * @return array|Rank[]|string[]
     */
    public function getRanks(bool $asString = false): array
    {

        if ($asString) {

            $ranks = [];

            foreach ($this->ranks as $rank)
                $ranks[] = $rank->getLocalName();

            return $ranks;
        }

        return $this->ranks;
    }


    /**
     * Sets the ranks of the player.
     *
     * @param array|Rank[] $ranks
     */
    public function setRanks($ranks = []): void
    {

        $size = count($ranks);

        if ($size > 0) {

            $this->ranks = $ranks;
            $this->updateNameTag();
        }
    }


    /**
     * Removes the rank of a player.
     *
     * @param string $rank
     */
    public function removeRank(string $rank): void
    {

        $keys = array_keys($this->ranks);

        foreach ($keys as $key) {

            $theRank = $this->ranks[$key];

            if ($theRank->getLocalName() === $rank) {
                unset($this->ranks[$key]);
                break;
            }
        }

        if (!$this->canFlyInLobby() and $this->isPlayerFlying()) {
            $this->setPlayerFlying(false);
        }

        $this->updateNameTag();
    }

    /**
     * @return array
     *
     * Gets the aliases of the player.
     */
    public function getAliases()
    {
        $playerHandler = MineceitCore::getPlayerHandler();
        $aliasManager = $playerHandler->getAliasManager();
        return $aliasManager->getAliases($this);
    }


    /**
     * @param LevelSoundEventPacket $packet
     * @return bool
     *
     * Handles the level sound event packet.
     */
    public function handleLevelSoundEvent(LevelSoundEventPacket $packet): bool
    {

        $sounds = [41 => true, 42 => true, 43 => true];

        $sound = $packet->sound;

        if (isset($sounds[$sound])) {

            MineceitUtil::broadcastDataPacket($this, $packet, function (MineceitPlayer $player) {
                return $player->isSwishEnabled();
            });

        } else {

            $this->getLevel()->broadcastPacketToViewers($this, $packet);
        }

        return true;
    }

    /**
     * @return bool
     *
     * Placement function for when Mineceit actually adds particles.
     */
    public function isParticlesEnabled(): bool
    {
        return $this->particlesEnabled;
    }

    /**
     * @param bool $particle
     *
     * Placement function for when Mineceit actually adds particles
     */
    public function setParticlesEnabled(bool $particle): void
    {
        $this->particlesEnabled = $particle;
    }

    /**
     * @param bool $enabled
     *
     * Enables/Disables the swish sound.
     */
    public function setSwishEnabled(bool $enabled): void
    {
        $this->swishSound = $enabled;
    }

    /**
     * @return bool
     *
     * Is the swish sound enabled.
     */
    public function isSwishEnabled(): bool
    {
        return $this->swishSound;
    }

    /**
     * @return bool
     *
     * Can change the player's tag -> used for data storage.
     */
    public function canChangeTag(): bool
    {
        return $this->changeTag;
    }


    /**
     * @return bool
     *
     * Determines whether the player has lightning enabled when killing other player.
     */
    public function lightningEnabled(): bool
    {
        return $this->lightningEnabled;
    }

    /**
     * Loads the player's data.
     * @param array $data
     */
    public function loadData($data = []): void
    {

        if (isset($data['kills']))
            $this->kills = (int)$data['kills'];
        if (isset($data['deaths']))
            $this->deaths = (int)$data['deaths'];
        if (isset($data['scoreboards-enabled']))
            $this->scoreboardEnabled = (bool)$data['scoreboards-enabled'];
        if (isset($data['pe-only']))
            $this->peOnly = (bool)$data['pe-only'];
        if (isset($data['place-break']))
            $this->builderMode = (bool)$data['place-break'];
        if (isset($data['muted']))
            $this->muted = (bool)$data['muted'];
        if (isset($data['swish-sound'])) {
            $this->swishSound = (bool)$data['swish-sound'];
        }

        $this->limitedFeaturesTime = 0;
        if (isset($data['limited-features']) and MineceitCore::LIMITED_FEATURES_ENABLED) {
            $time = (int)$data['limited-features'];
            if ($time > 0) {
                $this->limitedFeaturesTime = $time;
            }
        }

        $this->lastTimePlayed = -1;
        if (isset($data['lastTimePlayed'])) {
            // Updates the limited features time.
            $this->lastTimePlayed = (int)$data['lastTimePlayed'];
        }


        if (isset($data['language'])) {

            $languageFromData = (string)$data['language'];

            $language = MineceitCore::getPlayerHandler()->getLanguageFromOldName($languageFromData);
            if ($language !== null and $this->locale !== $language->getLocale()) {
                $this->currentLang = $this->locale;
                $form = FormUtil::getLanguageForm($this->locale);
                $this->sendFormWindow($form, ['locale' => $this->locale]);
            } else {
                $this->currentLang = $languageFromData;
            }

        }
        if (isset($data['particles']))
            $this->particlesEnabled = (bool)$data['particles'];
        if (isset($data['tag']))
            $this->customTag = (string)$data['tag'];
        if (isset($data['elo']))
            $this->elo = (array)$data['elo'];
        if (isset($data['translate']))
            $this->translate = (bool)$data['translate'];
        if (isset($data['ranks'])) {
            $ranks = $data['ranks'];
            $size = count($ranks);
            $rankHandler = MineceitCore::getRankHandler();
            $result = [];
            if ($size > 0) {
                foreach ($ranks as $rankName) {
                    $rankName = strval($rankName);
                    $rank = $rankHandler->getRank($rankName);
                    if ($rank !== null)
                        $result[] = $rank;
                }
            } else {
                $defaultRank = $rankHandler->getDefaultRank();
                if ($defaultRank !== null)
                    $result = [$defaultRank];
            }
            $this->ranks = $result;
        }

        // Removes permissions.

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            if (isset($permissions[self::PERMISSION_TAG])) {
                $this->changeTag = (bool)$permissions[self::PERMISSION_TAG];
            }
        }

        if (isset($data['change-tag'])) {
            $this->changeTag = (bool)$data['change-tag'];
        }

        if (isset($data['lightning-enabled'])) {
            $this->lightningEnabled = (bool)$data['lightning-enabled'];
        }

        /* if(isset($data['permissions'])) {
            $permissions = $data['permissions'];
            $keys = array_keys($permissions);
            foreach($keys as $key)
                $this->permissions[$key] = $permissions[$key];
        } */

        $levels = $this->server->getLevels();

        $duelHandler = MineceitCore::getDuelHandler();
        $replayHandler = MineceitCore::getReplayManager();

        foreach ($levels as $level) {
            $name = $level->getName();
            if ($duelHandler->isDuelLevel($name) or $replayHandler->isReplayLevel($name)) {
                continue;
            }

            $this->builderLevels[$name] = true;
        }

        if ($this->scoreboardEnabled) {
            $this->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
        }

        $itemHandler = MineceitCore::getItemHandler();

        $itemHandler->spawnHubItems($this);
        $this->setSpawnNameTag();

        $this->setAllowFlight($this->canFlyInLobby());

        if ($this->hasLimitedFeatures() and MineceitCore::LIMITED_FEATURES_ENABLED) {

            $limitedFeaturesTime = $this->limitedFeaturesTime - time();

            $days = strval(intval($limitedFeaturesTime / 86400) % 7);
            $weeks = strval(intval($limitedFeaturesTime / 604800));
            $minutes = strval(intval($limitedFeaturesTime / 60) % 60);
            $hours = strval(intval($limitedFeaturesTime / 3600) % 60);

            $message = TextFormat::RED . "You are in limited features mode for {$weeks} Weeks, {$days} Days, {$hours} Hours, & {$minutes} minutes.";
            $this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
        }

        $this->loadedData = true;
    }


    /**
     * @param callable $function
     * @return bool
     *
     * Generates the function.
     */
    private function getPermissionFromRanks(callable $function): bool
    {
        $ranks = $this->getRanks();
        if (count($ranks) <= 0) {
            return false;
        }

        foreach ($ranks as $rank) {
            if ($function($rank)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     *
     * Determines whether the player has loaded their data or not.
     */
    public function hasLoadedData(): bool
    {
        return $this->loadedData;
    }


    /**
     * @param string|null $name
     * @param Skin|null $disguise
     *
     * Sets the player's disguised.
     */
    public function setDisguise(string $name = null, Skin $disguise = null): void
    {

        if ($name === null and $disguise === null) {
            $this->disguised = null;
        } else {
            $this->disguised = [
                'name' => $name,
                'disguise' => $disguise
            ];
            $this->setDisplayName($name);
        }
        // TODO SEND MESSAGE SAYING PLAYER IS DISGUISED AND UPDATE SKINS.
    }

    /**
     * @return bool
     *
     * Returns whether the player is disguised or not.
     */
    public function isDisguised(): bool
    {
        return $this->disguised !== null;
    }


    /**
     * @param ReportInfo $info
     */
    public function addReport(ReportInfo $info): void
    {
        $reporter = $info->getReporter();
        $reportType = $info->getReportType();
        if (!isset($this->reports[$reportType])) {
            $this->reports[$reportType] = [];
        }
        $this->reports[$reportType][$reporter] = $info;
    }

    /**
     * @param MineceitPlayer $reporter
     * @param int $reportType
     * @return bool
     *
     * Determines if the player has reported you.
     */
    public function hasReport(MineceitPlayer $reporter, int $reportType): bool
    {
        if (isset($this->reports[$reportType])) {
            $reports = (array)$this->reports[$reportType];
            return isset($reports[$reporter->getName()]);
        }
        return false;
    }

    /**
     *
     * @param int $reportType
     *
     * @return int
     *
     * Gets the number of reports on you while you were.
     */
    public function getOnlineReportsCount(int $reportType = ReportInfo::TYPE_HACK): int
    {

        if (!isset($this->reports[$reportType])) {
            return 0;
        }

        return count($this->reports[$reportType]);
    }


    /**
     * @return int
     *
     * Gets the last time the player played.
     */
    public function getLastTimePlayed(): int
    {
        return $this->lastTimePlayed;
    }


    /**
     * @return int
     *
     * Gets the limited features time.
     */
    public function getLimitedFeaturesTime(): int
    {
        return $this->limitedFeaturesTime;
    }


    /**
     * @param bool $allReports
     * @param int ...$reportTypes
     *
     * @return ReportInfo[]|array
     *
     * Gets all of the reports on you.
     */
    public function getReports(bool $allReports = false, int...$reportTypes)
    {

        if (!$allReports) {
            $result = [];
            foreach ($reportTypes as $type) {
                if (!isset($this->reports[$type])) {
                    continue;
                }
                $reports = (array)$this->reports[$type];
                foreach ($reports as $key => $value) {
                    if ($value instanceof ReportInfo) {
                        $result[$value->getLocalName()] = $value;
                    }
                }
            }
            return $result;
        } else {
            $reportManager = MineceitCore::getReportManager();
            return $reportManager->getReportsOf($this->getName(), $reportTypes);
        }
    }

    /**
     * @return array|ReportInfo[]
     *
     * Gets the report history of the current player.
     */
    public function getReportHistory()
    {
        $reportManager = MineceitCore::getReportManager();
        return $reportManager->getReportHistoryOf($this->getName());
    }

    /**
     * @return string
     *
     * Gets the time zone.
     */
    public function getTimeZone(): string
    {
        $playerManager = MineceitCore::getPlayerHandler();
        $ipManager = $playerManager->getIPManager();
        return $ipManager->getTimeZone($this);
    }


    /**
     * @param array $data
     *
     * Adds the report search to the history.
     */
    public function setReportSearchHistory(array $data): void
    {
        $this->reportsSearchHistory[count($this->reportsSearchHistory)] = $data;
    }

    /**
     * @return array|null
     *
     * Gets the last search report.
     */
    public function getLastSearchReportHistory()
    {
        $index = count($this->reportsSearchHistory) - 1;
        return isset($this->reportsSearchHistory[$index]) ? $this->reportsSearchHistory[$index] : null;
    }


    /**
     * @return bool
     *
     * Determines whether the player can duel.
     */
    public function canDuel(): bool
    {
        return !$this->isFrozen() and !$this->isInEvent() and !$this->isInParty() and !$this->isInDuel() and !$this->isInArena() and !$this->isADuelSpec();
    }

    /**
     * @param int $number
     *
     * Disables the player for a few.
     */
    public function disableForChecks(int $number): void
    {
        if ($this->clickSpeedCheck !== null) {
            $this->clickSpeedCheck->setDisabled($number);
        }
        if ($this->diggingCheck !== null) {
            $this->diggingCheck->setDisabled($number);
        }
    }


    /**
     * @return CPSCheck|null
     *
     * Gets the click speed check.
     */
    public function getCPSCheck()
    {
        return $this->clickSpeedCheck;
    }


    /**
     * @return DiggingCheck|null
     */
    public function getDiggingCheck()
    {
        return $this->diggingCheck;
    }


    /**
     * @return ConsistencyCheck|null
     */
    public function getConsistencyCheck()
    {
        return $this->consistencyCheck;
    }


    public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null): bool
    {
        $result = parent::teleport($pos, $yaw, $pitch);

        if ($result and $this->clickSpeedCheck !== null) {
            $this->disableForChecks(1500);
        }

        return $result;
    }


    /**
     * @param int $action
     *
     * Tracks the player's current action.
     */
    public function setAction(int $action): void
    {

        $currentTime = round(microtime(true) * 1000);

        if ($this->currentAction === -1) {

            $this->currentAction = $action;
            $this->currentActionTime = $currentTime;
            $this->lastAction = PlayerActionPacket::ACTION_ABORT_BREAK;
            $this->lastActionTime = 0;

        } else {

            $this->lastAction = $this->currentAction;
            $this->lastActionTime = $this->currentActionTime;

            $this->currentAction = $action;
            $this->currentActionTime = $currentTime;
        }
    }


    /**
     * Updates the ping of the player.
     */
    private function doUpdatePing(): void
    {

        $pkt = MineceitUtil::constructCustomPkt(["GetPing", $this->getName()]);
        RequestPool::addRequest(Request::TYPE_PING, $this, $pkt->buffer, function ($data, $uuid) {
            if (($player = MineceitUtil::getPlayerFromUUID($uuid)) != null && $player instanceof MineceitPlayer) {
                $player->completePingUpdate($data['ping']);
            }
        });
        $this->dataPacket($pkt);
    }

    /**
     * @param int $ping
     *
     * Updates the ping accordingly.
     */
    public function completePingUpdate(int $ping): void
    {

        $this->ping = $ping;

        $this->updatePingSB($ping);
    }


    /**
     * @param int $ping
     *
     * Updates the ping for scoreboard.
     */
    private function updatePingSB(int $ping): void
    {

        $color = MineceitUtil::getThemeColor();

        $yourLang = $this->getLanguage();

        if ($this->scoreboardType === Scoreboard::SCOREBOARD_SPAWN or $this->scoreboardType === Scoreboard::SCOREBOARD_FFA) {

            $this->updateLineOfScoreboard(3, TextFormat::WHITE . ' Ping: ' . $color . $ping);

        } elseif ($this->scoreboardType === Scoreboard::SCOREBOARD_DUEL) {

            $duel = MineceitCore::getDuelHandler()->getDuel($this);

            if ($duel !== null and $duel->getOpponent($this) !== null) {

                $opponent = $duel->getOpponent($this);

                $theirLang = $opponent->getLanguage();

                $yourPing = TextFormat::WHITE . ' ' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING) . ': ' . $color . $ping;
                $theirPing = TextFormat::WHITE . ' ' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING) . ': ' . $color . $ping;

                if ($yourLang->getLocale() === Language::ARABIC)
                    $yourPing = $color . ' ' . $ping . TextFormat::WHITE . ' :' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING);

                if ($theirLang->getLocale() === Language::ARABIC)
                    $theirPing = $color . ' ' . $ping . TextFormat::WHITE . ' :' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING);

                $this->updateLineOfScoreboard(6, $yourPing);
                $opponent->updateLineOfScoreboard(9, $theirPing);
            }
        } elseif ($this->isInEventDuel()) {

            $event = MineceitCore::getEventManager()->getEventFromPlayer($this);
            $eventDuel = $event->getCurrentDuel();

            $opponent = $eventDuel->getOpponent($this);

            if ($eventDuel->getStatus() < 2 and $opponent !== null) {

                $theirLang = $opponent->getLanguage();

                $yourPing = TextFormat::WHITE . ' ' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING) . ': ' . $color . $ping;
                $theirPing = TextFormat::WHITE . ' ' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING) . ': ' . $color . $ping;

                if ($yourLang->getLocale() === Language::ARABIC)
                    $yourPing = $color . ' ' . $ping . TextFormat::WHITE . ' :' . $yourLang->scoreboard(Language::DUELS_SCOREBOARD_YOUR_PING);

                if ($theirLang->getLocale() === Language::ARABIC)
                    $theirPing = $color . ' ' . $ping . TextFormat::WHITE . ' :' . $theirLang->scoreboard(Language::DUELS_SCOREBOARD_THEIR_PING);

                $this->updateLineOfScoreboard(6, $yourPing);
                $opponent->updateLineOfScoreboard(9, $theirPing);
            }
        }
    }
}
