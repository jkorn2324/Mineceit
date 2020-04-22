<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-19
 * Time: 15:14
 */

declare(strict_types=1);

namespace mineceit\parties\events\types\match\data;


use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;

class MineceitTeam
{

    /* @var MineceitPlayer[]|array */
    private $players;

    /* @var bool */
    private $eliminated;

    /* @var string */
    private $teamColor;

    /**
     * MineceitTeam constructor.
     * @param array|string[] $excludedColors
     */
    public function __construct($excludedColors = [])
    {
        $this->players = [];
        $this->eliminated = false;
        $this->teamColor = MineceitUtil::randomColor($excludedColors);
    }

    /**
     * @param MineceitPlayer $player
     */
    public function addToTeam(MineceitPlayer $player) : void {
        $local = strtolower($player->getName());
        $this->players[$local] = $player;
    }

    /**
     * Sets the team as eliminated
     */
    public function setEliminated() : void {
        $this->eliminated = true;
    }

    /**
     * @return string
     */
    public function getTeamColor() : string {
        return $this->teamColor;
    }

    /**
     * @return array|MineceitPlayer[]
     */
    public function getPlayers() : array {
        return $this->players;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return bool
     */
    public function isInTeam($player) : bool {
        $name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
        return isset($this->players[strtolower($name)]);
    }

    /**
     * @param MineceitPlayer|string $player
     * @return void
     */
    public function removeFromTeam($player) : void {
        $name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
        $local = strtolower($name);
        if(isset($this->players[$local]))
            unset($this->players[$local]);
    }
}