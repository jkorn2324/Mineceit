<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-19
 * Time: 22:47
 */

declare(strict_types=1);

namespace mineceit\data\players;


use mineceit\data\mysql\MysqlStream;
use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;

class AsyncUpdatePlayerData extends AsyncTask
{

    /** @var bool */
    private $isMysql = MineceitCore::MYSQL_ENABLED;

    /** @var array */
    private $data;

    /** @var string */
    private $name;

    /** @var string */
    private $path;

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

    public function __construct(string $name, string $path, MysqlStream $stream, array $values = [])
    {
        $this->name = $name;
        $this->path = $path;
        $this->data = $values;

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
        if (!$this->isMysql) {

            if(!file_exists($this->path)) return;

            $info = (array)$this->data;

            $parsed = yaml_parse_file($this->path, 0);

            $keys = array_keys($info);

            foreach($keys as $key) {

                if (isset($parsed[$key], $info[$key])) {

                    $infoValue = $info[$key];

                    switch($key) {
                        case 'permissions':
                        case 'elo':
                            $infoValue = (array)$info[$key];
                            break;
                    }

                    if(is_array($infoValue)) {
                        $parsedValue = $parsed[$key];
                        $parsedKeys = array_keys($parsedValue);
                        foreach($parsedKeys as $pKey) {
                            if(isset($parsedValue[$pKey]) and !isset($infoValue[$pKey]))
                                $infoValue[$pKey] = $parsedValue[$pKey];
                        }
                    }
                    $parsed[$key] = $infoValue;
                }
            }

            yaml_emit_file($this->path, $parsed);

        } else {

            $stream = (array)$this->queryStream;

            $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

            if ($mysql->connect_error) {
                var_dump("Unable to connect");
                // TODO
                return;
            }

            foreach($stream as $query) {

                $querySuccess = $mysql->query($query);

                if($querySuccess === FALSE) {
                    var_dump("FAILED [UPDATE PLAYER DATA]: $query\n{$mysql->error}");
                }
            }

            $mysql->close();
        }
    }
}