<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-24
 * Time: 12:30
 */

declare(strict_types=1);

namespace mineceit\player\ranks;

use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\data\ranks\AsyncLoadRanks;
use mineceit\data\ranks\AsyncSaveRanks;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use mineceit\player\particles\ParticleHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class RankHandler
{

    /* @var Rank[]|array */
    private $ranks;

    /* @var Rank|null */
    private $defaultRank;

    /** @var Server */
    private $server;

    /** @var string */
    private $file;

    /** @var MineceitCore */
    private $core;

    /** @var ParticleHandler */
    private static $particlesHandler;

    public function __construct(MineceitCore $core)
    {
        self::$particlesHandler = new ParticleHandler($core);

        $this->ranks = [];
        $this->defaultRank = null;

        $this->server = $core->getServer();
        $this->core = $core;

        $this->file = $core->getDataFolder() . '/ranks.yml';

        $this->initRanks();
    }

    /**
     * @return ParticleHandler
     */
    public static function getParticlesHandler() : ParticleHandler {
        return self::$particlesHandler;
    }

    /**
     * Initializes the ranks so that they can be loaded.
     */
    private function initRanks() : void {

        $task = new AsyncLoadRanks($this->file);

        $this->server->getAsyncPool()->submitTask($task);
    }


    /**
     * Loads the ranks to the server.
     *
     * @param array $data
     */
    public function loadRanks(array $data) : void {

        $ranks = (array)$data['ranks'];

        $defaultRank = (string)$data['default-rank'];

        $keys = array_keys($ranks);

        /** @var Rank[]|array $outputRanks */
        $outputRanks = [];

        foreach($keys as $localName) {
            $value = (array)$ranks[$localName];
            $rank = Rank::parseRank($localName, $value);
            if($rank !== null) {
                $outputRanks[$localName] = $rank;
            }
        }

        $this->ranks = $outputRanks;

        /** @var Rank|null $outputDefaultRank */
        $this->defaultRank = isset($outputRanks[$defaultRank]) ? $outputRanks[$defaultRank] : null;
    }


    /**
     * Saves the ranks to the database.
     */
    private function saveRanks() : void {

        $ranks = [];

        $stream = new MysqlStream();

        // TODO PARTICLES

        $stream->removeRows("RanksData");

        $defaultRank = $this->defaultRank !== null ? $this->defaultRank->getLocalName() : '';

        $id = 1;

        foreach($this->ranks as $rank) {

            $data = $rank->encode();
            $localName = $rank->getLocalName();

            $ranks[$rank->getLocalName()] = $data;

            $row = new MysqlRow("Ranks");
            $row->put("id", $id);
            $row->put("localname", $localName);
            $row->put("name", $data['name']);
            $row->put("format", $data['format']);
            $row->put("permission", $data['permission']);
            $row->put("fly", $data['fly']);
            $row->put("placeBreak", $data['place-break']);
            $row->put("reserveEvent", $data['reserve-event']);
            $row->put("lightningKill", $data['lightning-kill']);
            $row->put("changeTag", $data['tag']);
            $row->put("isdefault", $defaultRank === $localName);

            $stream->insertNUpdate($row);

            /* $stream->insertNUpdate($row);

            $row = new MysqlRow("RankParticles");

            $row->put("id", $id);
            $row->put("localname", $localName);

            foreach($particles as $particle) {
                $local = $particle->getLocalName();
                $row->put($local, in_array($local, $rankParticles));
            }

            $stream->insertNUpdate($row); */

            $id++;
        }

        $task = new AsyncSaveRanks(['default-rank' => $defaultRank, 'ranks' => $ranks], $this->file, $stream);

        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * @param string $name
     * @return Rank|null
     */
    public function getRank(string $name) {
        $result = null;
        if(isset($this->ranks[$name]))
            $result = $this->ranks[$name];
        else {
            foreach($this->ranks as $rank) {
                $rankName = $rank->getName();
                if($rankName === $name) {
                    $result = $rank;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @return Rank|null
     */
    public function getDefaultRank() {
        return $this->defaultRank;
    }

    /**
     * @param string $name
     * @param $format
     * @param bool $fly
     * @param bool $edit
     * @param string $permission
     * @return bool
     */
    public function createRank(string $name, $format = null, bool $fly = false, bool $edit = false, string $permission = Rank::PERMISSION_NONE) : bool {

        $created = false;

        if(strlen($name) === 0) {
            return false;
        }

        $localName = strtolower($name);

        $format = $format ?? TextFormat::DARK_GRAY . "[" . TextFormat::WHITE . $name . TextFormat::DARK_GRAY . ']';

        $rank = new Rank($localName, $name, $format, $permission, $fly, $edit);

        if(!isset($this->ranks[$localName])) {

            $this->ranks[$localName] = $rank;

            if($this->defaultRank === null) {
                $this->defaultRank = $rank;
            }

            $created = true;

            $this->saveRanks();
        }

        return $created;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeRank(string $name) : bool {

        $removed = false;
        $localName = strtolower($name);

        if(isset($this->ranks[$localName])) {

            if($this->defaultRank !== null and $this->defaultRank->getLocalName() === $localName) {
                $this->defaultRank = null;
            }

            unset($this->ranks[$localName]);

            $removed = true;

            $players = $this->server->getOnlinePlayers();

            foreach($players as $player) {
                if($player instanceof MineceitPlayer)
                    $player->removeRank($localName);
            }

            $this->saveRanks();
        }
        return $removed;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return string
     */
    public function formatRanksForChat(MineceitPlayer $player) : string {

        $name = $player->getName();

        $ranks = $player->getRanks();

        $tag = $player->getCustomTag();

        $size = count($ranks);

        $format = TextFormat::WHITE . "$name" . TextFormat::GRAY . ":" . TextFormat::RESET;

        if($size > 0) {

            $rankFormat = '';

            foreach($ranks as $rank) {
                $formatRank = $rank->getFormat();
                $rankFormat .= $formatRank;
            }

            $format = $rankFormat . TextFormat::WHITE . " $name"  . TextFormat::GRAY . ":" . TextFormat::RESET;

        } else {

            $defaultRank = $this->getDefaultRank();

            if($defaultRank !== null)
                $format = $defaultRank->getFormat() . TextFormat::WHITE . " $name" . TextFormat::GRAY . ":" . TextFormat::RESET;
        }

        $perm = $player->getPermission(MineceitPlayer::PERMISSION_TAG);

        return ($perm and strlen($tag) > 0) ? $tag . ' ' . TextFormat::RESET . $format : $format;
    }

    /**
     * @param MineceitPlayer $player
     * @return string
     */
    public function formatRanksForTag(MineceitPlayer $player) : string {

        $ranks = $player->getRanks();

        $name = $player->getDisplayName();

        $size = count($ranks);

        $format = TextFormat::GREEN . $name;

        $tag = $player->getCustomTag();

        $perm = $player->getPermission(MineceitPlayer::PERMISSION_TAG);

        if($size > 0) {

            $rankFormat = '';

            foreach($ranks as $rank) {
                if ($rank !== null) {
                    $formatRank = $rank->getFormat();
                    $rankFormat .= $formatRank;
                }
            }

            $format = $rankFormat . TextFormat::GREEN . $name;

        } else {

            $defaultRank = $this->getDefaultRank();

            if($defaultRank !== null)
                $format = $defaultRank->getFormat() . TextFormat::GREEN . $name;
        }

        return ($perm) ? $tag . ' ' . TextFormat::RESET . $format : $format;
    }

    /**
     * @param bool $asArray
     * @return string|array|string[]
     */
    public function listRanks(bool $asArray = true) {

        if($asArray) {
            $ranks = [];
            foreach($this->ranks as $rank)
                $ranks[] = $rank->getName();
            return $ranks;
        }

        $size = count($this->ranks);

        if($size <= 0)
            return 'None';

        $result = '';

        $commaLen = $size - 1;
        $count = 0;

        foreach($this->ranks as $rank) {
            $comma = ($count === $commaLen) ? '' : ', ';
            $result .= $rank->getName() . $comma;
            $count++;
        }

        return $result;
    }

    /**
     * Gets the valid ranks. Used in async tasks to update a deleted rank.
     *
     * @return array|string[]
     */
    public function getValidRanks() : array {
        $result = [];
        foreach($this->ranks as $rank)
            $result[] = $rank->getLocalName();
        return $result;
    }
}