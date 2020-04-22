<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-19
 * Time: 19:36
 */

declare(strict_types=1);

namespace mineceit\data\players;


use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;

class AsyncSavePlayerData extends AsyncTask
{

    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var bool */
    private $isMysql = MineceitCore::MYSQL_ENABLED;

    /** @var array */
    private $yamlInfo;

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

    /**
     * AsyncSavePlayerData constructor.
     * @param MineceitPlayer $player
     * @param string $path
     */
    public function __construct(MineceitPlayer $player, string $path)
    {
        $this->name = $player->getName();
        $this->path = $path;

        $this->yamlInfo = [
            'kills' => $player->getKills(), // Stats
            'deaths' => $player->getDeaths(), // Stats
            'scoreboards-enabled' => $player->isScoreboardEnabled(), // Settings
            'pe-only' => $player->isPeOnly(), // Settings
            'place-break' => $player->isBuilderModeEnabled(), // Settings
            // 'permissions' => $player->getPermissions(),
            'muted' => $player->isMuted(), // Settings
            'language' => $player->getLanguage()->getLocale(), // Settings
            'particles' => $player->isParticlesEnabled(), // Settings
            'tag' => $player->getCustomTag(), // Settings
            'elo' => $player->getElo(), // Elo
            'ranks' => $player->getRanks(true), // Ranks
            'translate' => $player->doesTranslateMessages(), // Settings
            'swish-sound' => $player->isSwishEnabled(),
            'lastTimePlayed' => time(),
            'change-tag' => $player->canChangeTag(),
            'limited-features' => $player->getLimitedFeaturesTime(),
            'lightning-enabled' => $player->lightningEnabled()
        ];

        $stream = MineceitUtil::getMysqlStream($player, true);

        $this->host = $stream->host;

        $this->username = $stream->username;

        $this->password = $stream->password;

        $this->database = $stream->database;

        $this->port = $stream->port;

        $this->mysqlStream = $stream->getStream();
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $info = (array)$this->yamlInfo;

        $keys = array_keys($info);

        if (!$this->isMysql) {

            $parsed = yaml_parse_file($this->path, 0);

            foreach($keys as $key) {

                $dataInfo = $info[$key];
                switch($key) {
                    case 'ranks':
                    // case 'permissions':
                    case 'elo':
                        $dataInfo = (array)$info[$key];
                        break;
                }
                $parsed[$key] = $dataInfo;
            }

            yaml_emit_file($this->path, $parsed);

        } else {

            $stream = (array)$this->mysqlStream;

            $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

            if ($mysql->connect_error) {
                var_dump("Unable to connect");
                // TODO
                return;
            }

            foreach($stream as $query) {

                $querySuccess = $mysql->query($query);

                if($querySuccess === FALSE) {
                    var_dump("FAILED [SAVE PLAYER]: $query\n{$mysql->error}");
                }
            }

            $mysql->close();
        }
    }
}