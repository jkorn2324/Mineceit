<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-01
 * Time: 18:06
 */

declare(strict_types=1);

namespace mineceit\game\leaderboard\tasks;


use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\data\mysql\MysqlTable;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class EloLeaderboardsTask extends AsyncTask
{

    /* @var string */
    private $directory;

    /* @var array|string[] */
    private $eloKits;

    /* @var array */
    private $leaderboardResult;

    /** @var bool */
    private $isMysql = MineceitCore::MYSQL_ENABLED;

    /** @var string */
    private $username;

    /** @var string -> The ip of the db */
    private $host;

    /** @var string */
    private $password;

    /** @var int */
    private $port;

    /** @var string */
    private $database;

    /** @var array */
    private $mysqlStream;

    /** @var array */
    private $onlinePlayers;

    public function __construct(string $dir, array $eloKits)
    {
        $this->directory = $dir;
        $this->eloKits = $eloKits;
        $this->leaderboardResult = [];

        $stream = new MysqlStream();
        $rows = [];
        foreach($eloKits as $kit) {
            $stream->selectTablesInOrder(["PlayerElo"], [$kit => true, "username" => false]);
            $rows[$kit] = true;
        }

        $rows["username"] = false;

        // Global elo.
        $stream->selectAverageRows("PlayerElo", $rows, true);

        $onlinePlayers = [];

        $players = Server::getInstance()->getOnlinePlayers();

        foreach($players as $player) {
            if($player instanceof MineceitPlayer) {
                $global = $player->getElo('global');
                if ($global !== null) {
                    $onlinePlayers[$player->getName()] = ['elo' => $player->getElo(), 'global' => $global];
                }
            }
        }

        $this->onlinePlayers = $onlinePlayers;

        $this->username = $stream->username;

        $this->database = $stream->database;

        $this->password = $stream->password;

        $this->port = $stream->port;

        $this->host = $stream->host;

        $this->mysqlStream = $stream->getStream();
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $elo = [];

        foreach($this->eloKits as $kit) {
            $kit = strtolower($kit);
            $elo[$kit] = [];
        }

        $elo['global'] = [];

        $players = (array)$this->onlinePlayers;

        if(!$this->isMysql) {

            if (is_dir($this->directory)) {
                $files = scandir($this->directory);

                foreach ($files as $file) {

                    if (strpos($file, '.yml') !== false) {

                        $name = str_replace('.yml', '', $file);

                        $file = $this->directory . '/' . $file;

                        $data = yaml_parse_file($file, 0);

                        if(isset($players[$name])) {
                            $data = $players[$name];
                        }

                        if (isset($data['elo'])) {

                            $eloData = $data['elo'];

                            foreach ($this->eloKits as $kit) {

                                $kit = strtolower($kit);
                                $eloFromData = 1000;

                                if(isset($eloData[$kit])) {
                                    $eloFromData = $eloData[$kit];
                                }

                                $elo[$kit][$name] = $eloFromData;
                                if (!isset($elo['global'][$name]))
                                    $elo['global'][$name] = $eloFromData;
                                else {
                                    $prevElo = $elo['global'][$name];
                                    $elo['global'][$name] = $prevElo + $eloFromData;
                                }
                            }
                        }
                    }
                }
            }

            $keys = array_keys($elo);

            $numKits = count($this->eloKits);

            foreach ($keys as $key) {
                $leaderboard = $elo[$key];
                if ($key === 'global') {
                    $names = array_keys($leaderboard);
                    foreach ($names as $name) {
                        $eloVal = $leaderboard[$name];
                        $leaderboard[$name] = (int)($eloVal / $numKits);
                    }
                }
                arsort($leaderboard);
                $elo[$key] = $leaderboard;
            }
        } else {

            $stream = (array)$this->mysqlStream;

            $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

            if ($mysql->connect_error) {
                var_dump("Unable to connect");
                // TODO
                return;
            }

            $index = 0;

            $kits = array_keys($elo);

            foreach($stream as $query) {

                $kit = $kits[$index];
                $querySuccess = $mysql->query($query);

                if($querySuccess instanceof \mysqli_result) {
                    $result = $querySuccess->fetch_all();
                    $length = count($result);
                    $count = 0;
                    $leaderboardSet = [];
                    $eloIndex = 0;
                    $nameIndex = 1;

                    while ($count < $length) {

                        $set = $result[$count];
                        $playerElo = intval($set[$eloIndex]);
                        $playerName = strval($set[$nameIndex]);

                        if(isset($players[$playerName])) {

                            $data = $players[$playerName];

                            if($kit === 'global') {
                                $playerElo = intval($data[$kit]);
                            } else {
                                $playerElo = intval($data['elo'][$kit]);
                            }
                        }

                        $leaderboardSet[$playerName] = $playerElo;
                        $count++;
                    }
                    arsort($leaderboardSet);
                    $elo[$kit] = $leaderboardSet;
                }
                $index++;
            }

            $mysql->close();
        }

        $this->setResult($elo);
    }

    public function onCompletion(Server $server)
    {
        $core = $server->getPluginManager()->getPlugin('Mineceit');

        if($core instanceof MineceitCore and $core->isEnabled()) {

            $leaderboards = MineceitCore::getLeaderboards();

            $result = $this->getResult();

            if($result !== null) {

                $leaderboards->setEloLeaderboards($result);
            }
        }
    }
}