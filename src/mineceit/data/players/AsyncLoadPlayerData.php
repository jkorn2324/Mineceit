<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-19
 * Time: 19:36
 */

declare(strict_types=1);

namespace mineceit\data\players;


use mineceit\data\mysql\MysqlStream;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadPlayerData extends AsyncTask
{

    /** @var string */
    private $path;

    /** @var string */
    private $playerName;

    /** @var bool */
    private $isMysql = MineceitCore::MYSQL_ENABLED;

    /* @var bool */
    private $op;

    /** @var string[]|array */
    private $validRanks;

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
    private $queryStream;

    /**
     * AsyncCreatePlayerData constructor.
     * @param MineceitPlayer $player;
     * @param string $path
     * @param array $validRanks
     * @param MysqlStream $stream
     */
    public function __construct(MineceitPlayer $player, string $path, array $validRanks, MysqlStream $stream)
    {

        $this->playerName = $player->getName();

        $this->op = $player->isOp();

        $this->path = $path;

        $this->validRanks = $validRanks;

        $this->queryStream = $stream->getStream();

        $this->username = $stream->username;

        $this->host = $stream->host;

        $this->database = $stream->database;

        $this->port = $stream->port;

        $this->password = $stream->password;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $languageForm = false;

        $validRanks = (array)$this->validRanks;

        $playerData = [
            'kills' => 0,
            'deaths' => 0,
            'language' => Language::ENGLISH_US,
            'muted' => false,
            'ranks' => [],
            'scoreboards-enabled' => true,
            'place-break' => false,
            'pe-only' => false,
            'particles' => false,
            'elo' => [
                'combo' => 1000,
                'fist' => 1000,
                'gapple' => 1000,
                'nodebuff' => 1000,
                'sumo' => 1000,
                'builduhc' => 1000
            ],
            'tag' => '',
            'translate' => false,
            'swish-sound' => true,
            'limited-features' => 0,
            'lastTimePlayed' => -1,
            'change-tag' => false,
            'lightning-enabled' => false
        ];

        if (!$this->isMysql) {

            $data = $this->loadFromYaml($playerData, $validRanks);

            $languageForm = (bool)$data['language'];
            $playerData = (array)$data['playerData'];

        } else {

            $load = false;

            $mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

            if ($mysql->connect_error) {
                var_dump("Unable to connect");
                // TODO
                return;
            }

            $stream = (array)$this->queryStream;

            $parsedData = ['elo' => [], 'ranks' => []];

            foreach($stream as $query) {

                $querySuccess = $mysql->query($query);

                if ($querySuccess === TRUE or $querySuccess instanceof \mysqli_result) {

                    if($querySuccess instanceof \mysqli_result) {

                        $fetch = $querySuccess->fetch_all();

                        $load = true;

                        $fetch = $fetch[0];

                        $length = count($fetch);

                        $count = 0;

                        while($count < $length) {

                            $value = $fetch[$count];
                            switch($count) {
                                case 2:
                                    $parsedData['language'] = $value;
                                    break;
                                case 3:
                                    $parsedData['muted'] = boolval($value);
                                    break;
                                case 4:
                                    $parsedData['scoreboards-enabled'] = boolval($value);
                                    break;
                                case 5:
                                    $parsedData['place-break'] = boolval($value);
                                    break;
                                case 6:
                                    $parsedData['pe-only'] = boolval($value);
                                    break;
                                case 7:
                                    $parsedData['particles'] = boolval($value);
                                    break;
                                case 8:
                                    $parsedData['tag'] = strval($value);
                                    break;
                                case 9:
                                    $parsedData['translate'] = boolval($value);
                                    break;
                                case 10:
                                    $parsedData['swish-sound'] = boolval($value);
                                    break;
                                case 11:
                                    $parsedData['change-tag'] = boolval($value);
                                    break;
                                case 12:
                                    $parsedData['lightning-enabled'] = boolval($value);
                                    break;
                                case 15:
                                    $parsedData['kills'] = intval($value);
                                    break;
                                case 16:
                                    $parsedData['deaths'] = intval($value);
                                    break;
                                case 19:
                                case 20:
                                case 21:
                                    if ($value !== null) {
                                        $parsedData['ranks'][] = strval($value);
                                    }
                                    break;
                                case 23:
                                    $parsedData['elo']['combo'] = intval($value);
                                    break;
                                case 24:
                                    $parsedData['elo']['gapple'] = intval($value);
                                    break;
                                case 25:
                                    $parsedData['elo']['fist'] = intval($value);
                                    break;
                                case 26:
                                    $parsedData['elo']['nodebuff'] = intval($value);
                                    break;
                                case 27:
                                    $parsedData['elo']['sumo'] = intval($value);
                                    break;
                                case 28:
                                    $parsedData['elo']['builduhc'] = intval($value);
                                    break;
                                case 29:
                                    $parsedData['elo']['spleef'] = intval($value);
                                    break;
                            }
                            $count++;
                        }
                    }
                } else {
                    var_dump("FAILED [LOAD PLAYER DATA]: " . $mysql->error);
                }
            }

            $mysql->close();

            $keys = array_keys($playerData);
            $parsed = $parsedData;

            if($load) {

                foreach($keys as $key) {
                    $value = $playerData[$key];
                    if(!isset($parsed[$key])) {
                        $parsed[$key] = $value;
                    } else {
                        switch($key) {
                            case 'ranks':
                                $parsedRanks = $parsed['ranks'];
                                $parsedRankKeys = array_keys($parsedRanks);
                                foreach($parsedRankKeys as $rankKey) {
                                    $rank = $parsedRanks[$rankKey];
                                    if(!in_array($rank, $validRanks)) {
                                        unset($parsedRanks[$rankKey]);
                                    }
                                }
                                $parsed['ranks'] = $parsedRanks;
                                break;

                        }
                    }
                }

                $playerData = $parsed;
            }
        }

        $this->setResult(['data' => $playerData, 'showLang' => $languageForm, 'player' => $this->playerName]);
    }


    /**
     * @param array $playerData
     * @param array $validRanks
     * @return array
     *
     * Function that loads the data from the yaml file.
     */
    private function loadFromYaml(array $playerData, array $validRanks) : array {

        $languageForm = false;

        if (!file_exists($this->path)) {

            $file = fopen($this->path, 'wb');
            fclose($file);
            $languageForm = true;

        } else {

            $keys = array_keys($playerData);
            $parsed = yaml_parse_file($this->path, 0);

            foreach($keys as $key) {
                $value = $playerData[$key];
                if(!isset($parsed[$key])) {
                    $parsed[$key] = $value;
                } else {
                    switch($key) {
                        /* case 'particles':
                            if(!is_bool($parsed['particles']))
                                $parsed['particles'] = $value;
                            break; */
                        case 'ranks':
                            $parsedRanks = $parsed['ranks'];
                            $parsedRankKeys = array_keys($parsedRanks);
                            foreach($parsedRankKeys as $rankKey) {
                                $rank = $parsedRanks[$rankKey];
                                if(!in_array($rank, $validRanks)) {
                                    unset($parsedRanks[$rankKey]);
                                }
                            }
                            $parsed['ranks'] = $parsedRanks;
                            break;

                    }
                }
            }

            if(isset($parsedPerms['permissions'])) {
                $parsedPerms = $parsed['permissions'];
                if(isset($parsedPerms['tag'])) {
                    $parsed['change-tag'] = (bool)$parsedPerms['tag'];
                }
            }

            $playerData = $parsed;
        }

        yaml_emit_file($this->path, $playerData);

        return ['language' => $languageForm, 'playerData' => $playerData];
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server)
    {

        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled() and $result !== null) {

            $playerName = (string)$result['player'];
            $showLang = (bool)$result['showLang'];
            $data = (array)$result['data'];

            $player = $server->getPlayer($playerName);

            if($player !== null and $player->isOnline() and $player instanceof MineceitPlayer) {

                if($showLang) {
                    $locale = $player->getLocale();
                    $playerHandler = MineceitCore::getPlayerHandler();
                    if($playerHandler->getLanguage($locale) === null) {
                        $locale = $playerHandler->getLanguage()->getLocale();
                    }
                    $form = FormUtil::getLanguageForm($locale);
                    $player->sendFormWindow($form, ["locale" => $locale]);
                }

                $player->loadData($data);
            }
        }
    }
}