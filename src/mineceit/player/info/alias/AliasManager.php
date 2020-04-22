<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-16
 * Time: 23:24
 */

declare(strict_types=1);

namespace mineceit\player\info\alias;


use mineceit\MineceitCore;
use mineceit\player\info\alias\tasks\AsyncLoadAliases;
use mineceit\player\info\alias\tasks\AsyncSaveAliases;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class AliasManager
{

    // CID IS NOT USED HERE ANY MORE.

    /** @var array */
    private $ipAliases;

    /** @var array */
    private $uuidAliases;
    /** @var array */
    private $fuuidAliases; // The uuid aliases that came from the file.

    /** @var array */
    private $cidAliases;

    /** @var string */
    private $dir;

    /** @var MineceitCore */
    private $core;

    /** @var string */
    private $uuidFile, $cidFile;

    /** @var Server */
    private $server;

    public function __construct(MineceitCore $core)
    {
        $this->core = $core;
        $this->dir = $core->getDataFolder() . 'aliases/';
        $this->ipAliases = [];
        $this->cidAliases = [];
        $this->uuidAliases = [];
        $this->fuuidAliases = [];

        $this->server = $core->getServer();

        $this->init();
    }


    /**
     * Initializes everything & loads the aliases.
     */
    private function init() : void {

        if(!is_dir($this->dir)) {
            mkdir($this->dir);
        }

        $this->uuidFile = $this->dir . 'uuid-aliases.yml';
        $this->cidFile = $this->dir . 'cid-aliases.yml';

        $task = new AsyncLoadAliases($this->cidFile, $this->uuidFile);
        $this->server->getAsyncPool()->submitTask($task);
    }


    /**
     * Saves the aliases to the files.
     */
    public function save() : void {

        $task = new AsyncSaveAliases($this->uuidFile, $this->fuuidAliases);

        $this->server->getAsyncPool()->submitTask($task);
    }


    /**
     * @param string $player
     * @param array $ips
     * @param array $uuids
     * @param bool $update
     *
     * Sets all of the aliases.
     */
    public function setAliases(string $player, array $ips, array $uuids, bool $update = true) : void {

        $this->ipAliases[$player] = $ips;
        $this->uuidAliases[$player] = $uuids;

        $playerManager = MineceitCore::getPlayerHandler();

        if($update) {
            foreach ($ips as $ign) {
                if (($p = $this->server->getPlayer($ign)) !== null and $p instanceof MineceitPlayer) {
                    $pName = $p->getName();
                    if ($pName !== $player) {
                        $playerManager->updateAliases($p, false);
                    }
                }
            }
        }
    }

    /**
     * @param array $uuid
     *
     * Loads the aliases.
     */
    public function loadAliases(array $uuid) : void {
        $this->fuuidAliases = $uuid;
    }

    /**
     * @param MineceitPlayer $player
     * @return array
     *
     * Gets the ip aliases of the player.
     */
    public function getAliases(MineceitPlayer $player) : array {

        $name = $player->getName();

        $data = [];

        if(isset($this->ipAliases[$name])) {
            $data = $this->ipAliases[$name];
        }

        if(isset($this->uuidAliases[$name])) {
            $data = array_unique(array_merge($data, $this->uuidAliases[$name]));
        }

        if(count($data) > 0) {
            return $data;
        }

        return [$name];
    }

    /**
     * @param MineceitPlayer $player
     * @return array
     *
     * Used to collect data of a player.
     */
    public function collectData(MineceitPlayer $player) : array {

        $name = $player->getName();
        $uuid = $player->getUniqueId()->toString();

        $data = [];

        if(isset($this->fuuidAliases[$uuid])) {
            $data = $this->fuuidAliases[$uuid];
        }

        $searched = array_flip($data);

        if(!isset($searched[$name])) {
            $data[] = $name;
        }

        $this->fuuidAliases[$uuid] = $data;

        return ['uuid' => $uuid, 'alias-uuid' => $this->fuuidAliases];
    }
}