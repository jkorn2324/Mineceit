<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-01
 * Time: 18:02
 */

declare(strict_types=1);

namespace mineceit\game\leaderboard;


use mineceit\game\leaderboard\holograms\EloHologram;
use mineceit\game\leaderboard\holograms\StatsHologram;
use mineceit\game\leaderboard\tasks\EloLeaderboardsTask;
use mineceit\game\leaderboard\tasks\StatsLeaderboardsTask;
use mineceit\kits\AbstractKit;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;

class Leaderboards
{

    /* @var array */
    private $eloLeaderboards;

    /** @var array */
    private $statsLeaderboards;

    /* @var Server */
    private $server;

    /* @var string */
    private $dataFolder;

    /* @var EloHologram|null */
    private $eloLeaderboardHologram;

    /** @var StatsHologram|null */
    private $statsLeaderboardHologram;

    /* @var Config */
    private $leaderboardConfig;

    /**
     * Leaderboards constructor.
     * @param MineceitCore $core
     */
    public function __construct(MineceitCore $core)
    {
        $this->eloLeaderboards = [];
        $this->statsLeaderboards = [];
        $this->server = $core->getServer();
        $this->dataFolder = $core->getDataFolder();
        $this->eloLeaderboardHologram = null;
        $this->statsLeaderboardHologram = null;
        $this->initConfig();
    }

    private function initConfig() : void {

        $keys = ['elo', 'stats'];

        $arr = [];

        foreach($keys as $key) {

            $arr[strval($key)] = [
                'x' => NULL,
                'y' => NULL,
                'z' => NULL,
                'level' => NULL
            ];
        }

        $this->leaderboardConfig = new Config($this->dataFolder . '/leaderboard-hologram.yml', Config::YAML);

        if(!$this->leaderboardConfig->exists('data')) {

            $this->leaderboardConfig->set('data', $arr);
            $this->leaderboardConfig->save();

        } else {

            $data = $this->leaderboardConfig->get('data');

            $loaded = $this->loadData($data);

            if($loaded !== null) {

                /** @var Level $level */
                $level = $loaded['level'];

                $this->eloLeaderboardHologram = new EloHologram(
                    new Vector3($loaded['x'], $loaded['y'], $loaded['z']),
                    $level,
                    false,
                    $this
                );

            } else {

                if(isset($data['stats'])) {

                    $statsLoaded = $this->loadData($data['stats']);

                    if($statsLoaded !== null) {

                        $this->statsLeaderboardHologram = new StatsHologram(
                            new Vector3($statsLoaded['x'], $statsLoaded['y'], $statsLoaded['z']),
                            $statsLoaded['level'],
                            false,
                            $this
                        );
                    }
                }

                if(isset($data['elo'])) {

                    $eloLoaded = $this->loadData($data['elo']);

                    if($eloLoaded !== null) {

                        $this->eloLeaderboardHologram = new EloHologram(
                            new Vector3($eloLoaded['x'], $eloLoaded['y'], $eloLoaded['z']),
                            $eloLoaded['level'],
                            false,
                            $this
                        );
                    }

                }
            }
        }
    }

    /**
     * @param $data
     * @return array|null
     */
    private function loadData($data) {

        $result = null;

        if(isset($data['x'], $data['y'], $data['z'], $data['level'])) {

            $x = $data['x'];
            $y = $data['y'];
            $z = $data['z'];
            $levelName = $data['level'];

            if(is_int($x) and is_int($y) and is_int($z) and is_string($levelName) and ($theLevel = $this->server->getLevelByName($levelName)) !== null and $theLevel instanceof Level) {

                $result = [
                    'x' => $x,
                    'y' => $y,
                    'z' => $z,
                    'level' => $theLevel
                ];
            }
        }

        return $result;
    }


    public function reloadEloLeaderboards() : void {

        $dir = $this->dataFolder . 'player';

        $task = new EloLeaderboardsTask($dir, MineceitCore::getKits()->getKitsLocal());

        $this->server->getAsyncPool()->submitTask($task);
    }

    public function reloadStatsLeaderboards() : void {

        $dir = $this->dataFolder . 'player';

        $task = new StatsLeaderboardsTask($dir, ['kills', 'deaths']);

        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * @param array $eloLeaderboards
     */
    public function setEloLeaderboards(array $eloLeaderboards) : void
    {
        $this->eloLeaderboards = $eloLeaderboards;

        if($this->eloLeaderboardHologram instanceof EloHologram) {
            $this->eloLeaderboardHologram->updateHologram();
        }
    }

    /**
     * @param array $statsLeaderboards
     */
    public function setStatsLeaderboards(array $statsLeaderboards) : void {

        $this->statsLeaderboards = $statsLeaderboards;

        if($this->statsLeaderboardHologram instanceof StatsHologram) {
            $this->statsLeaderboardHologram->updateHologram();
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $elo
     */
    public function setLeaderboardHologram(MineceitPlayer $player, bool $elo = true) : void {

        $vec3 = $player->asVector3();
        $level = $player->getLevel();

        if($elo) {
            $key = 'elo';
            if($this->eloLeaderboardHologram !== null) {
                $this->eloLeaderboardHologram->moveHologram($vec3, $level);
            } else {
                $this->eloLeaderboardHologram = new EloHologram($vec3, $level, true, $this);
            }
        } else {
            $key = 'stats';
            if($this->statsLeaderboardHologram !== null) {
                $this->statsLeaderboardHologram->moveHologram($vec3, $level);
            } else {
                $this->statsLeaderboardHologram = new StatsHologram($vec3, $level, true, $this);
            }
        }

        $data = $this->leaderboardConfig->get('data');

        if(isset($data['x'], $data['y'], $data['z'], $data['level'])) {
            unset($data['x'], $data['y'], $data['z'], $data['level']);
        }

        $data[$key] = [
            'x' => (int)$vec3->x,
            'y' => (int)$vec3->y,
            'z' => (int)$vec3->z,
            'level' => $level->getName()
        ];

        $this->leaderboardConfig->setAll(['data' => $data]);
        $this->leaderboardConfig->save();
    }

    /**
     * @param AbstractKit|string $queue
     * @return array|int[]
     */
    public function getEloLeaderboardOf($queue = 'global') : array {
        $result = [];
        $queue = $queue instanceof AbstractKit ? $queue->getLocalizedName() : $queue;

        if(isset($this->eloLeaderboards[$queue])) {
            $result = $this->eloLeaderboards[$queue];
        }
        return $result;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getStatsLeaderboardOf(string $key) : array {

        $result = [];

        if(isset($this->statsLeaderboards[$key])) {
            $result = $this->statsLeaderboards[$key];
        }

        return $result;
    }

    /**
     *
     * @param bool $elo
     *
     * @return array|string[]
     */
    public function getLeaderboardKeys(bool $elo = true) : array {

        $result = ['kills', 'deaths', 'kdr'];

        if($elo) {
            $result = MineceitCore::getKits()->getKitsLocal();
            $result[] = 'global';
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getEloLeaderboards() : array {
        return $this->eloLeaderboards;
    }

    /**
     * @return array
     */
    public function getStatsLeaderboards() : array {
        return $this->statsLeaderboards;
    }

    /**
     * @param string $player
     * @param string $key
     * @param bool $elo
     * @return null|int
     */
    public function getRankingOf(string $player, string $key, bool $elo = true) {

        $list = $this->eloLeaderboards;

        if(!$elo) {
            $list = $this->statsLeaderboards;
        }

        if(isset($list[$key][$player])) {
           $leaderboardSet = $list[$key];
           $searched = array_keys($leaderboardSet);
           $result = array_search($player, $searched);
           if(is_int($result)) {
               return $result + 1;
           }
        }

        return null;
    }
}