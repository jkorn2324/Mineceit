<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-21
 * Time: 19:02
 */

declare(strict_types=1);

namespace mineceit\data\ranks;

use mineceit\data\mysql\MysqlStream;
use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;

class AsyncSaveRanks extends AsyncTask
{

    /** @var array */
    private $ranks;

    /** @var string */
    private $file;

    /** @var bool */
    private $isMysql = MineceitCore::MYSQL_ENABLED;

    /** @var string -> The ip of the db */
    private $host;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var int */
    private $port;

    /** @var string */
    private $database;

    /** @var array */
    private $queryStream;

    public function __construct(array $ranks, string $file, MysqlStream $stream)
    {
        $this->ranks = $ranks;

        $this->file = $file;

        $this->queryStream = $stream->getStream();

        $this->host = $stream->host;

        $this->username = $stream->username;

        $this->password = $stream->password;

        $this->port = $stream->port;

        $this->database = $stream->database;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $rankInfo = (array)$this->ranks;

        $keys = array_keys($rankInfo);

        if(!$this->isMysql) {

            $parsed = yaml_parse_file($this->file, 0);

            foreach($keys as $key) {
                if(isset($parsed[$key])) {
                    $data = $rankInfo[$key];
                    switch ($key) {
                        case 'ranks':
                            $data = (array)$rankInfo[$key];
                            $ranksKeys = array_keys($data);
                            foreach($ranksKeys as $localName) {
                                $value = (array)$data[$localName];
                                $data[$localName] = $value;
                            }
                            break;
                    }
                    $parsed[$key] = $data;
                }
            }

            yaml_emit_file($this->file, $parsed);

        } else {

            $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

            if ($mysql->connect_error) {
                var_dump("Unable to connect");
                // TODO
                return;
            }

            $stream = (array)$this->queryStream;

            foreach($stream as $query) {

                $querySuccess = $mysql->query($query);

                if($querySuccess === FALSE) {
                    var_dump("FAILED [SAVE RANKS DATA]: " . $query . "\n" . $mysql->error);
                }
            }

            $mysql->close();
        }
    }
}