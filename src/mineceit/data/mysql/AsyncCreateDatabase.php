<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-25
 * Time: 12:00
 */

declare(strict_types=1);

namespace mineceit\data\mysql;


use mineceit\MineceitCore;
use mineceit\player\ranks\Rank;
use mineceit\player\ranks\RankHandler;
use pocketmine\scheduler\AsyncTask;

class AsyncCreateDatabase extends AsyncTask
{
    /** @var array */
    private $stream;

    /** @var string */
    private $username;

    /** @var string */
    private $host;

    /** @var string */
    private $password;

    /** @var int */
    private $port;

    /** @var string */
    private $database;

    public function __construct($validKits = [])
    {
        $mysqlStream = new MysqlStream();

        $this->username = $mysqlStream->username;
        $this->host = $mysqlStream->host;
        $this->password = $mysqlStream->password;
        $this->port = $mysqlStream->port;
        $this->database = $mysqlStream->database;

        $playerSettings = new MysqlTable("PlayerSettings");
        $playerSettings->putId(); // 0
        $playerSettings->putString("username"); // 1
        // $playerInfo->putString("discordName");
        $playerSettings->putString("language"); // 2
        $playerSettings->putBoolean("muted", false); // 3
        $playerSettings->putBoolean("scoreboardEnabled", true); // 4
        $playerSettings->putBoolean("placeBreak", false); // 5
        $playerSettings->putBoolean("peOnly", false); // 6
        $playerSettings->putBoolean("particles", false); // 7
        $playerSettings->putString("tag", 60); // 8
        $playerSettings->putBoolean("translate", false); // 9
        $playerSettings->putBoolean("swishSound", true); // 10
        $playerSettings->putBoolean("changeTag", false); // 11
        $playerSettings->putBoolean("lightningEnabled", true); // 12

        $playerStats = new MysqlTable("PlayerStats");
        $playerStats->putId(); // 13
        $playerStats->putString("username"); // 14
        $playerStats->putInt("kills", 0); // 15
        $playerStats->putInt("deaths", 0); // 16

        $playerRanks = new MysqlTable("PlayerRanks");
        $playerRanks->putId(); // 17
        $playerRanks->putString("username"); // 18
        $playerRanks->putString("rank1"); // 19
        $playerRanks->putString("rank2"); // 20
        $playerRanks->putString("rank3"); // 21

        $elo = new MysqlTable("PlayerElo");
        $elo->putId(); // 22
        $elo->putString("username"); // 23
        foreach($validKits as $kit) {
            $elo->putInt($kit, 1000);
        }

        $ranks = new MysqlTable("RanksData");
        $ranks->putId();
        $ranks->putString("localname"); //0
        $ranks->putString("name"); //1
        $ranks->putString("format"); //2
        $ranks->putString("permission", 60, Rank::PERMISSION_NONE); //3
        $ranks->putBoolean("fly", false); //4
        $ranks->putBoolean("placeBreak", false); //5
        $ranks->putBoolean("reserveEvent", false); //6
        $ranks->putBoolean("lightningPerm", false); //7
        $ranks->putBoolean("changeTag", false); //8
        $ranks->putBoolean("isdefault", false); //9

        $mysqlStream->createTable($playerSettings);
        $mysqlStream->createTable($playerStats);
        $mysqlStream->createTable($playerRanks);
        $mysqlStream->createTable($elo);
        $mysqlStream->createTable($ranks);

        $this->stream = $mysqlStream->getStream();
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

        if ($mysql->connect_error) {
            var_dump("Unable to connect to db [CREATE DATABASE]");
            // TODO
            return;
        }


        $stream = (array)$this->stream;

        foreach($stream as $query) {

            $querySuccess = $mysql->query($query);

            if($querySuccess === FALSE) {
                var_dump("Failed [CREATE DATABASE]: $query\n$mysql->error");
            }
        }

        $mysql->close();
    }
}