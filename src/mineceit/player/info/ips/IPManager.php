<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-10
 * Time: 15:44
 */

declare(strict_types=1);

namespace mineceit\player\info\ips;


use mineceit\MineceitCore;
use mineceit\player\info\alias\tasks\AsyncSearchAliases;
use mineceit\player\info\ips\tasks\AsyncCheckIP;
use mineceit\player\info\ips\tasks\AsyncCollectInfo;
use mineceit\player\info\ips\tasks\AsyncLoadIPs;
use mineceit\player\info\ips\tasks\AsyncSaveIPs;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;
use pocketmine\utils\Timezone;

class IPManager
{

    /** @var array */
    private $safeIps;

    /** @var MineceitCore */
    private $core;

    /** @var Server */
    private $server;

    /** @var string */
    private $ipsDir;
    /** @var string */
    private $aliasDir;

    /** @var string */
    private $safeIPsFile;
    /** @var string */
    private $tzFile;
    /** @var string */
    private $aliasFile;

    /** @var array */
    private $timezoneIPs;

    /** @var array */
    private $aliasLocIps;

    public function __construct(MineceitCore $core, string $playersFolder)
    {
        $this->safeIps = [];
        $this->server = $core->getServer();
        $this->core = $core;
        $this->timezoneIPs = [];
        $this->aliasLocIps = [];

        $this->ipsDir = $core->getDataFolder() . 'ips/';
        $this->aliasDir = $core->getDataFolder() . 'aliases/';

        $this->initFile();
    }

    private function initFile() : void {

        if(!is_dir($this->ipsDir)) {
            mkdir($this->ipsDir);
        }

        $this->safeIPsFile = $this->ipsDir . 'safe-ips.txt';
        $this->tzFile = $this->ipsDir . 'Timezones.csv';

        $this->aliasFile = $this->aliasDir . 'ip-aliases.yml';

        $task = new AsyncLoadIPs($this->safeIPsFile, $this->aliasFile, $this->tzFile);

        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * @param string $ip
     * @return string
     *
     * Generates the host address.
     */
    private function getHostAddressOf(string $ip) : string {
        $exploded = array_chunk(explode(".", $ip), 3);
        $hostIP = implode(".", $exploded[0]);
        return $hostIP;
    }

    /**
     * @param $safeIps
     * @param $aliasLocIps
     * @param $tzIps
     *
     * Loads the ips.
     */
    public function loadIps($safeIps, $aliasLocIps, $tzIps) : void {

        $this->safeIps = (array)$safeIps;
        $this->aliasLocIps = (array)$aliasLocIps;
        $this->timezoneIPs = (array)$tzIps;
    }


    /**
     * Saves ips to the files.
     */
    public function save() : void {

        $task = new AsyncSaveIPs($this->safeIPsFile, $this->safeIps, $this->tzFile, $this->timezoneIPs, $this->aliasFile, $this->aliasLocIps);

        $this->server->getAsyncPool()->submitTask($task);
    }

    /**
     * @param MineceitPlayer $player
     *
     * @return void
     *
     * Checks the player's ip whether it is safe or not.
     */
    public function checkIPSafe(MineceitPlayer $player) : void {

        $ip = $player->getAddress();
        $name = $player->getName();

        $searched = array_flip($this->safeIps);

        if(!isset($searched[$ip])) {
            MineceitCore::getPlayerHandler()->updateAliases($player);
            return;
        }

        $task = new AsyncCheckIP($name, $ip);
        $this->server->getAsyncPool()->submitTask($task);
    }


    /**
     * @param MineceitPlayer $player
     * @param bool $saveIP
     * @return array
     *
     * Gets the information of the player.
     */
    public function collectInfo(MineceitPlayer $player, bool $saveIP = true) : array {

        $ip = $player->getAddress();
        $name = $player->getName();

        $searched = array_flip($this->safeIps);
        // Saves the ip to the safe ips.
        if(!isset($searched[$ip]) and $saveIP) {
            $this->safeIps[] = $ip;
        }

        // Saves them to the aliases.
        $locIP = $this->getHostAddressOf($ip);
        $data = [];
        if(isset($this->aliasLocIps[$locIP])) {
            $data = (array)$this->aliasLocIps[$locIP];
        }

        $searched = array_flip($data);
        if(!isset($searched[$name])) {
            $data[] = $name;
        }

        $this->aliasLocIps[$locIP] = $data;

        if(!isset($this->timezoneIPs[$ip])) {
            $task = new AsyncCollectInfo($name, $ip);
            $this->server->getAsyncPool()->submitTask($task);
        }

        return ['loc-ip' => $locIP, 'alias-ips' => $this->aliasLocIps];
    }

    /**
     * @param MineceitPlayer $player
     * @return string
     *
     * Gets the time zone of a player.
     */
    public function getTimeZone(MineceitPlayer $player) : string {
        $ip = $player->getAddress();
        if(isset($this->timezoneIPs[$ip])) {
            return $this->timezoneIPs[$ip]['tz'];
        }
        return Timezone::get();
    }

    /**
     * @param MineceitPlayer $player
     * @return bool
     *
     * Determines if the player is on 24 hour time.
     */
    public function is24HourTime(MineceitPlayer $player) : bool {

        $ip = $player->getAddress();
        if(isset($this->timezoneIPs[$ip])) {
            return $this->timezoneIPs[$ip]['24-hr'];
        }

        return false;
    }

    /**
     * @param string $ip
     * @param string $timeZone
     * @param bool $is24Hour
     *
     * Sets the timezone of the player.
     */
    public function setTimeZone(string $ip, string $timeZone, bool $is24Hour) : void {
        $this->timezoneIPs[$ip] = ['tz' => $timeZone, '24-hr' => $is24Hour];
    }
}