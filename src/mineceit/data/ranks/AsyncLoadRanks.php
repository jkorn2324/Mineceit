<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-21
 * Time: 18:32
 */

declare(strict_types=1);

namespace mineceit\data\ranks;

use mineceit\data\mysql\MysqlStream;
use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadRanks extends AsyncTask
{

    /** @var bool */
    private $isMysql = MineceitCore::MYSQL_ENABLED;

    /** @var string */
    private $file;

    /** @var array */
    private $stream;

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

    public function __construct(string $file)
    {
        $this->file = $file;

        $stream = new MysqlStream();
        $stream->selectTables(["RanksData"]);

        $this->host = $stream->host;

        $this->username = $stream->username;

        $this->password = $stream->password;

        $this->port = $stream->port;

        $this->database = $stream->database;

        $this->stream = $stream->getStream();
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $data = ['default-rank' => '', 'ranks' => []];

        if($this->isMysql) {

            $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

            if ($mysql->connect_error) {
                var_dump("Unable to connect");
                // TODO
                return;
            }

            $stream = (array)$this->stream;

            foreach($stream as $query) {

                $querySuccess = $mysql->query($query);

                if($querySuccess === TRUE or $querySuccess instanceof \mysqli_result) {

                    $fetch = $querySuccess->fetch_all();

                    $length = count($fetch);

                    $index = 0;

                    while($index < $length) {

                        $fetchedData = $fetch[$index];
                        $localName = $fetchedData[1];
                        $name = $fetchedData[2];
                        $format = $fetchedData[3];
                        $permission = $fetchedData[4];
                        $fly = (bool)$fetchedData[5];
                        $placeBreak = (bool)$fetchedData[6];
                        $reserveEvent = (bool)$fetchedData[7];
                        $lightningKill = (bool)$fetchedData[8];
                        $changeTag = (bool)$fetchedData[9];
                        $isDefault = (bool)$fetchedData[10];

                        $data['ranks'][$localName] = [
                            'name' => $name,
                            'format' => $format,
                            'permission' => $permission,
                            'fly' => $fly,
                            'place-break' => $placeBreak,
                            'reserve-event' => $reserveEvent,
                            'lightning-kill' => $lightningKill,
                            'tag' => $changeTag
                        ];

                        if($isDefault) {
                            $data['default-rank'] = $localName;
                        }

                        $index++;
                    }

                } else {
                    var_dump("FAILED [LOAD RANKS DATA]: " . $mysql->error);
                }
            }

            $mysql->close();

        } else {

            if(!file_exists($this->file)) {
                $file = fopen($this->file, 'wb');
                fclose($file);

            } else {

                $parsed = yaml_parse_file($this->file, 0);
                $keys = array_keys($data);

                foreach($keys as $key) {
                    $value = $data[$key];
                    if(!isset($parsed[$key])) {
                        $parsed[$key] = $value;
                    } else {
                        switch($key) {
                            case 'ranks':
                                $ranks = (array)$parsed[$key];
                                $rankKeys = array_keys($ranks);
                                foreach($rankKeys as $localName) {
                                    $value = (array)$ranks[$localName];
                                    $ranks[$localName] = $value;
                                }
                                $parsed[$key] = $ranks;
                                break;
                        }
                    }
                }

                $data = $parsed;
            }

            yaml_emit_file($this->file, $data);
        }

        $this->setResult($data);
    }


    /**
     * @param Server $server
     */
    public function onCompletion(Server $server)
    {
        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled()) {

            $rankHandler = MineceitCore::getRankHandler();

            $rankHandler->loadRanks($result);
        }
    }
}