<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 19:14
 */

declare(strict_types=1);

namespace mineceit\player;

use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\data\players\AsyncLoadPlayerData;
use mineceit\data\players\AsyncSavePlayerData;
use mineceit\data\players\AsyncUpdatePlayerData;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\alias\AliasManager;
use mineceit\player\info\alias\tasks\AsyncSearchAliases;
use mineceit\player\info\device\DeviceInfo;
use mineceit\player\info\ips\IPManager;
use mineceit\player\language\Language;
use mineceit\player\language\LanguageHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Timezone;

class PlayerHandler
{

    /* @var string */
    private $path;

    /* @var LanguageHandler */
    private static $languages;

    /** @var ranks\RankHandler */
    private $rankHandler;

    /** @var array */
    private $closedInventoryIDs;

    /** @var Server */
    private $server;

    /** @var MineceitCore */
    private $core;

    /** @var DeviceInfo */
    private $deviceInfo;

    /** @var IPManager */
    private $ipManager;

    /** @var AliasManager */
    private $aliasManager;

    /** @var MineceitPlayer[]|array */
    private $kickedOnJoin;

    public function __construct(MineceitCore $core)
    {
        $this->closedInventoryIDs = [];
        $this->kickedOnJoin = [];

        $this->rankHandler = MineceitCore::getRankHandler();
        $this->path = $core->getDataFolder() . 'player/';
        self::$languages = new LanguageHandler($core);
        $this->deviceInfo = new DeviceInfo($core);
        $this->ipManager = new IPManager($core, $this->path);
        $this->aliasManager = new AliasManager($core);
        $this->server = $core->getServer();

        $this->core = $core;

        $this->initFolder();
    }

    /**
     * Initializes the folder.
     */
    private function initFolder() : void {
        if(!is_dir($this->path))
            mkdir($this->path);
    }

    /**
     * @param MineceitPlayer $player
     * @param string $reason
     *
     * Sets the player to be kicked when joining.
     */
    public function setKickOnJoin(MineceitPlayer $player, string $reason) : void {
        $this->kickedOnJoin[$player->getUniqueId()->toString()] = [$player, $reason];
    }

    /**
     * @param MineceitPlayer $player
     * @return bool
     *
     * Kicks the player when joining.
     */
    public function doKickOnJoin(MineceitPlayer $player) : bool {
        $uuid = $player->getUniqueId()->toString();
        if(isset($this->kickedOnJoin[$uuid])) {
            $reason = $this->kickedOnJoin[$uuid][1];
            $player->kick($reason, false);
            unset($this->kickedOnJoin[$uuid]);
            return true;
        }
        return false;
    }

    /**
     * @return IPManager
     */
    public function getIPManager() : IPManager {
        return $this->ipManager;
    }

    /**
     * @return AliasManager
     */
    public function getAliasManager() : AliasManager {
        return $this->aliasManager;
    }

    /**
     * @return DeviceInfo
     */
    public function getDeviceInfo() : DeviceInfo {
        return $this->deviceInfo;
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $update
     *
     * Updates all aliases.
     */
    public function updateAliases(MineceitPlayer $player, bool $update = true, bool $saveIP = true) : void {

        $ipData = $this->ipManager->collectInfo($player, $saveIP);

        $aliasIps = $ipData['alias-ips'];
        $locIP = $ipData['loc-ip'];

        $aliasData = $this->aliasManager->collectData($player);

        $aliasUUIDs = $aliasData['alias-uuid'];
        $locUUID = $aliasData['uuid'];

        $name = $player->getName();

        $task = new AsyncSearchAliases($name, $locIP, $locUUID, $aliasIps, $aliasUUIDs, $update);
        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * @param MineceitPlayer $player
     * @return int
     */
    public function getOpenChestID(MineceitPlayer $player): int
    {

        $result = 1;

        while (array_search($result, $this->closedInventoryIDs) !== false or !is_null($player->getWindow($result)))
            $result++;

        return $result;
    }

    /**
     * @param int $id
     * @param MineceitPlayer $player
     * @return bool
     */
    public function setClosedInventoryID(int $id, MineceitPlayer $player): bool {

        $result = false;

        $index = array_search($id, $this->closedInventoryIDs);

        if (is_bool($index) and $index === false) $index = null;

        if (is_null($index)) {
            $this->closedInventoryIDs[$player->getName()] = $id;
            $result = true;
        }

        return $result;
    }

    /**
     * @param MineceitPlayer $player
     */
    public function setOpenInventoryID(MineceitPlayer $player): void {

        $name = $player->getName();

        $id = $this->getClosedChestID($player);

        if ($id !== -1) {
            unset($this->closedInventoryIDs[$name]);
        }
    }

    /**
     * @param MineceitPlayer $player
     * @return int
     */
    private function getClosedChestID(MineceitPlayer $player): int {

        $name = $player->getName();

        $id = -1;

        if (isset($this->closedInventoryIDs[$name]))
            $id = intval($this->closedInventoryIDs[$name]);

        return $id;
    }

    /**
     * @param MineceitPlayer $player
     */
    public function loadPlayerData(MineceitPlayer $player) : void {

        $name = $player->getName();

        $filePath = $this->path . "$name.yml";

        $rankHandler = MineceitCore::getRankHandler();

        $validRanks = $rankHandler->getValidRanks();

        $mysqlStream = MineceitUtil::getMysqlStream($player);

        $statements = [];

        $tables = ["PlayerSettings", "PlayerStats", "PlayerRanks", "PlayerElo"];

        foreach($tables as $table) {
            $statements[] = "{$table}.username = '{$player->getName()}'";
        }

        $mysqlStream->selectTables($tables, $statements);

        $task = new AsyncLoadPlayerData($player, $filePath, $validRanks, $mysqlStream);

        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * @param MineceitPlayer $player
     *
     * Saves the player's data -> used for when logging out.
     */
    public function savePlayerData(MineceitPlayer $player) : void {

        $name = $player->getName();

        $filePath = $this->path . "$name.yml";

        if($player->hasLoadedData()) {

            $task = new AsyncSavePlayerData($player, $filePath);

            $this->server->getAsyncPool()->submitTask($task);
        }
    }


    /**
     * @param MineceitPlayer|string $player
     * @param array $values
     */
    public function updatePlayerData($player, $values = []) : void {

        $name = ($player instanceof MineceitPlayer) ? $player->getName() : strval($player);

        $filePath = $this->path . "$name.yml";

        $stream = new MysqlStream();

        $keys = array_keys($values);

        foreach($keys as $key) {

            $table = "";
            $value = $values[$key];

            switch($key) {
                case 'elo':
                    $table = "PlayerElo";
                    break;
                case 'permissions':
                    $table = "PlayerPermissions";
                    break;
                case 'ranks':
                    $table = "PlayerRanks";
                    break;
            }

            $row = new MysqlRow($table);

            if (is_array($value)) {
                $valueKeys = array_keys($value);
                foreach($valueKeys as $vKey) {
                    $sqlKey = $vKey;
                    switch($vKey) {
                        case MineceitPlayer::PERMISSION_BUILDER_MODE:
                            $sqlKey = 'placeBreak';
                            break;
                        case 'limited-features':
                            $sqlKey = 'limitedFeatures';
                            break;
                    }
                    $sqlValue = $value[$vKey];
                    $row->put($sqlKey, $sqlValue);
                }
            }

            $stream->updateRow($row, ["username = '{$name}'"]);
        }

        $task = new AsyncUpdatePlayerData($name, $filePath, $stream, $values);

        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * Returns the player data folder.
     *
     * @return string
     */
    public function getDataFolder() : string {
        return $this->path;
    }

    /**
     * @param string $locale
     * @return Language
     */
    public function getLanguage(string $locale = Language::ENGLISH_US) {

        return self::$languages->getLanguage($locale);
    }


    /**
     * @param string $locale
     * @return Language|null
     *
     * Determines whether the data has old format.
     */
    public function getLanguageFromOldName(string $locale) {

        return self::$languages->getLanguageFromOldName($locale);
    }

    /**
     * @return Language[]|array
     */
    public function getLanguages() {
        return self::$languages->getLanguages();
    }

    /**
     * @param string $name
     * @param string $locale
     * @return Language|null
     */
    public function getLanguageFromName(string $name, string $locale = "") {
        return self::$languages->getLanguageFromName($name, $locale);
    }

    /**
     * @param string $winner
     * @param string $loser
     * @param int $winnerElo
     * @param int $loserElo
     * @param int $winnerDevOS
     * @param int $loserDevOS
     * @param string $queue
     * @param bool $set
     * @return array|int[]
     */
    public function setElo(string $winner, string $loser, int $winnerElo, int $loserElo, int $winnerDevOS, int $loserDevOS, string $queue, bool $set = true) : array {

        $result = ['winner' => 1000, 'loser' => 1000, 'winner-change' => 0, 'loser-change' => 0];

        $wPlayer = $this->server->getPlayer($winner);
        $lPlayer = $this->server->getPlayer($loser);

        $kFactor = 32;

        $winnerExpectedScore = 1.0 / (1.0 + pow(10, floatval(($loserElo - $winnerElo) / 400)));
        $loserExpectedScore = abs(floatval(1.0 / (1.0 + pow(10, floatval(($winnerElo - $loserElo) / 400)))));

        $newWinnerElo = $winnerElo + intval($kFactor * (1 - $winnerExpectedScore));
        $newLoserElo = $loserElo + intval($kFactor * (0 - $loserExpectedScore));

        $winnerEloChange = $newWinnerElo - $winnerElo;
        $loserEloChange = abs($loserElo - $newLoserElo);

        $winnerDevice = $winnerDevOS;
        $loserDevice = $loserDevOS;

        if ($winnerDevice === MineceitPlayer::WINDOWS_10 and $loserDevice !== MineceitPlayer::WINDOWS_10)
            $loserEloChange = intval($loserEloChange * 0.9);
        else if ($winnerDevice !== MineceitPlayer::WINDOWS_10 and $loserDevice === MineceitPlayer::WINDOWS_10)
            $winnerEloChange = intval($winnerEloChange * 1.1);

        $newWElo = $winnerElo + $winnerEloChange;
        $newLElo = $loserElo - $loserEloChange;

        if ($newLElo < 700) {
            $newLElo = 700;
            $loserEloChange = $loserElo - 700;
        }

        $result['winner'] = $newWElo;
        $result['loser'] = $newLElo;

        $result['winner-change'] = $winnerEloChange;
        $result['loser-change'] = $loserEloChange;

        if($set) {

            if($wPlayer !== null and $wPlayer->isOnline() and $wPlayer instanceof MineceitPlayer)
                $wPlayer->setElo($queue, $newWElo);
            else $this->updatePlayerData($winner, ['elo' => [$queue => $newWElo]]);

            if($lPlayer !== null and $lPlayer->isOnline() and $lPlayer instanceof MineceitPlayer)
                $lPlayer->setElo($queue, $newLElo);
            else $this->updatePlayerData($loser, ['elo' => [$queue => $newLElo]]);
        }

        return $result;
    }

    /**
     * @param MineceitPlayer $player
     * @param Language|null $lang
     * @return array|string[]
     */
    public function listStats(MineceitPlayer $player, Language $lang = null) : array {

        $leaderboards = MineceitCore::getLeaderboards();

        $language = ($lang === null ? $player->getLanguage() : $lang);

        $statsOf = $language->generalMessage(Language::STATS_OF, ["name" => $player->getDisplayName()]);

        $title = TextFormat::GOLD . '   » ' . TextFormat::BOLD . TextFormat::BLUE . $statsOf . TextFormat::RESET . TextFormat::GOLD . ' «';

        $k = $player->getKills();

        $killsRanking = $leaderboards->getRankingOf($player->getName(), 'kills', false) ?? "??";
        $killsRanking = TextFormat::DARK_GRAY . "(" . TextFormat::GRAY . $lang->getMessage(Language::STATS_RANK_LABEL, ['num' => $killsRanking]) . TextFormat::DARK_GRAY . ")";

        $killsStr = $language->scoreboard(Language::FFA_SCOREBOARD_KILLS);
        $deathsStr = $language->scoreboard(Language::FFA_SCOREBOARD_DEATHS);

        $kills = TextFormat::GOLD . '   » ' . TextFormat::GREEN . $killsStr . TextFormat::WHITE . ': ' . $k . " {$killsRanking}" . TextFormat::GOLD . ' «';
        if($language->getLocale() === Language::ARABIC)
            $kills = TextFormat::GOLD . '   » ' . "{$killsRanking} " . TextFormat::WHITE . $k . ' :' . TextFormat::GREEN . $killsStr . TextFormat::GOLD . ' «';

        $d = $player->getDeaths();

        $deathsRanking = $leaderboards->getRankingOf($player->getName(), 'deaths', false) ?? "??";
        $deathsRanking = TextFormat::DARK_GRAY . "(" . TextFormat::GRAY . $lang->getMessage(Language::STATS_RANK_LABEL, ['num' => $deathsRanking]) . TextFormat::DARK_GRAY . ")";


        $deaths = TextFormat::GOLD . '   » ' . TextFormat::RED . $deathsStr . TextFormat::WHITE . ': ' . $d . " {$deathsRanking}" . TextFormat::GOLD . ' «';

        if($language->getLocale() === Language::ARABIC)
            $deaths = TextFormat::GOLD . '   » ' . "{$deathsRanking} " . TextFormat::WHITE . $d . ' :' . TextFormat::RED . $deathsStr . TextFormat::GOLD . ' «';

        $eloFormat = TextFormat::GOLD . '   » ' . TextFormat::AQUA . '{kit}' . TextFormat::WHITE . ': {elo} {rank}' . TextFormat::GOLD . ' «';

        $eloOf = $language->generalMessage(Language::ELO_OF, ["name" => $player->getDisplayName()]);

        $eloTitle = TextFormat::GOLD . '   » ' . TextFormat::BOLD . TextFormat::BLUE . $eloOf . TextFormat::RESET . TextFormat::GOLD . ' «';

        $kitArr = MineceitCore::getKits()->getKits(true);

        $eloStr = '';

        $count = 0;

        $len = count($kitArr) - 1;

        $elo = $player->getElo();

        foreach($kitArr as $kit) {

            $name = (string)$kit;
            $kit = strtolower($kit);

            if(!isset($elo[$kit])) {
                $elo[$kit] = 1000;
            }

            $e = intval($elo[$kit]);
            $line = ($count === $len) ? "" : "\n";
            $kitRanking = $leaderboards->getRankingOf($player->getName(), $kit) ?? "??";
            $kitRanking = TextFormat::DARK_GRAY . "(" . TextFormat::GRAY . $language->getMessage(Language::STATS_RANK_LABEL, ['num' => $kitRanking]) . TextFormat::DARK_GRAY . ")";
            $str = str_replace('{rank}', $kitRanking, str_replace('{kit}', $name, str_replace('{elo}', $e, $eloFormat))) . $line;
            $eloStr .= $str;
            $count++;
        }

        $lineSeparator = TextFormat::GRAY . '---------------------------';

        return ['title' => $title, 'firstSeparator' => $lineSeparator, 'kills' => $kills, 'deaths' => $deaths, 'secondSeparator' => $lineSeparator, 'eloTitle' => $eloTitle, 'thirdSeparator' => $lineSeparator, 'elo' => $eloStr, 'fourthSeparator' => $lineSeparator];
    }

    /**
     * @param MineceitPlayer $player
     * @param Language|null $lang
     * @return string[]|array
     */
    public function listInfo(MineceitPlayer $player, Language $lang = null) : array {

        $info = $player->getInfo($lang);

        $language = ($lang === null ? $player->getLanguage() : $lang);

        $infoOf = $language->generalMessage(Language::INFO_OF, ["name" => $player->getDisplayName()]);

        $title = TextFormat::GOLD . '   » ' . TextFormat::BOLD . TextFormat::BLUE . $infoOf . TextFormat::RESET . TextFormat::GOLD . ' «';

        $lineSeparator = TextFormat::GRAY . '---------------------------';

        $format = TextFormat::GOLD . '   » ' . TextFormat::AQUA . '%key%' . TextFormat::WHITE . ': %value%' . TextFormat::GOLD . ' «';

        $result = ['title' => $title, 'firstSeparator' => $lineSeparator];

        $keys = array_keys($info);

        foreach($keys as $key) {
            $value = $info[$key];
            $message = str_replace('%key%', $key, str_replace('%value%', $value, $format));
            $result[$key] = $message;
        }

        $result['lastSeparator'] = $lineSeparator;

        return $result;
    }


    /**
     * @param bool $string
     * @param MineceitPlayer ...$excluded
     * @return array|MineceitPlayer[]|string[]
     *
     * Gets the staff that are online.
     */
    public function getStaffOnline(bool $string = false, MineceitPlayer...$excluded) {

        $server = Server::getInstance();

        if($string) {
            $excluded = array_map(function (Mineceitplayer $player) {
                return $player->getName();
            }, $excluded);
        }

        $players = [];
        /** @var MineceitPlayer[] $onlinePlayers */
        $onlinePlayers = $server->getOnlinePlayers();
        foreach($onlinePlayers as $player) {
            if($player->hasTrialModPermissions()) {
                $value = $player;
                if($string) {
                    $value = $player->getName();
                }
                $players[] = $value;
            }
        }

        return array_diff($players, $excluded);
    }
}