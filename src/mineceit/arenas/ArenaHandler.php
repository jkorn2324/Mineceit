<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-23
 * Time: 15:39
 */

declare(strict_types=1);

namespace mineceit\arenas;


use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;
use pocketmine\utils\Config;

class ArenaHandler
{

    /* @var array|Arena[] */
    private $arenas;

    /* @var Config */
    private $config;

    /* @var Server */
    private $server;

    public function __construct(MineceitCore $core)
    {
        $this->arenas = [];
        $this->server = $core->getServer();
        $this->initConfig($core->getDataFolder());
    }

    /**
     * @param string $dataFolder
     */
    private function initConfig(string $dataFolder) : void {

        $file = $dataFolder . '/arenas.yml';

        $this->config = new Config($file, Config::YAML, []);

        if(!file_exists($file)) {
            $this->config->save();
        } else {

            $keys = $this->config->getAll(true);

            foreach($keys as $arenaName) {
                $arena = Arena::parseArena($arenaName, $this->config->get((string)$arenaName));
                if($arena !== null) {
                    $this->arenas[$arenaName] = $arena;
                }
            }
        }
    }

    /**
     * @param string $name
     * @param string $kit
     * @param MineceitPlayer $player
     * @param bool $eventArena
     * @return bool
     */
    public function createArena(string $name, string $kit, MineceitPlayer $player, bool $eventArena = false) : bool {

        $level = $player->getLevel();
        $pos = $player->asVector3();

        if($eventArena) {

            $arena = new EventArena($name, $pos, $level, $kit);
            $events = MineceitCore::getEventManager();
            $events->createEvent($arena);

        } else {

            $arena = new FFAArena($name, $pos, $pos, $level, $kit);
        }

        if(!isset($this->arenas[$name]) and !$this->config->exists($name)) {
            $this->arenas[$name] = $arena;
            $this->config->set($name, $arena->getData());
            $this->config->save();
            return true;
        }

        return false;
    }


    /**
     * @param Arena $arena
     * @return bool
     *
     * Edits the arena.
     */
    public function editArena(Arena $arena) : bool {

        $name = $arena->getName();

        if(isset($this->arenas[$name])) {
            $this->arenas[$name] = $arena;
            $this->config->set($name, $arena->getData());
            $this->config->save();
            return true;
        }

        return false;
    }


    /**
     * @param string $name
     * @return bool
     */
    public function deleteArena(string $name) : bool {

        if(isset($this->arenas[$name]) && $this->config->exists($name)) {

            $this->config->remove($name);
            /** @var Arena $arena */
            $arena = $this->arenas[$name];

            unset($this->arenas[$name]);
            $this->config->save();

            if($arena instanceof Arena) {
                $events = MineceitCore::getEventManager();
                $events->removeEventFromArena($arena->getName());
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return Arena|null
     */
    public function getArena(string $name) {
        return isset($this->arenas[$name]) ? $this->arenas[$name] : null;
    }

    /**
     * @param bool $string
     * @return array|string[]|FFAArena[]
     */
    public function getFFAArenas(bool $string = false) {
        $result = [];
        foreach($this->arenas as $arena) {
            if($arena instanceof FFAArena) {
                $result[] = $string ? $arena->getName() : $arena;
            }
        }
        return $result;
    }


    /**
     * @param bool $string
     * @return array|string[]|EventArena[]
     */
    public function getEventArenas(bool $string = false) {

        $result = [];
        foreach($this->arenas as $arena) {
            if($arena instanceof EventArena) {
                $result[] = $string ? $arena->getName() : $arena;
            }
        }
        return $result;
    }

    /**
     * @param FFAArena|string $arena
     * @return int
     */
    public function getPlayersInArena($arena) : int {

        $name = ($arena instanceof FFAArena) ? $arena->getName() : $arena;

        $players = $this->server->getOnlinePlayers();

        $count = 0;

        foreach($players as $player) {
            if($player instanceof MineceitPlayer && $player->isInArena()) {
                if($player->getArena()->getName() === $name)
                    $count++;
            }
        }
        return $count;
    }
}