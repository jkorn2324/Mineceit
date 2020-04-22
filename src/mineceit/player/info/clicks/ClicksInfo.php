<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-09
 * Time: 14:49
 */

declare(strict_types=1);

namespace mineceit\player\info\clicks;


use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Position;
use pocketmine\Server;

class ClicksInfo
{


    /** @var array */
    private $cps;

    /** @var MineceitPlayer */
    private $player;

    public function __construct(MineceitPlayer $player)
    {
        $this->cps = [];
        $this->player = $player;
    }


    /**
     *
     * @param bool $clickedBlock
     *
     * Adds a click.
     */
    public function addClick(bool $clickedBlock): void
    {
        $this->cps[strval(microtime(true) * 1000)] = $clickedBlock;
    }

    /**
     * @return int
     *
     * Gets the clicks per second.
     */
    public function getCps(): int
    {
        return count($this->cps);
    }


    /**
     * @return bool
     *
     * Updates the clicks per second.
     */
    public function updateCPS(): bool
    {
        $currentTime = microtime(true) * 1000;

        $lastCps = $this->getCps();

        $cps = $this->cps;

        foreach ($cps as $time => $bool) {
            $floatTime = floatval($time);
            if (($currentTime - $floatTime) >= 1000) {
                unset($cps[$time]);
            }
        }

        $this->cps = $cps;

        $currentCps = $this->getCps();

        return $lastCps !== $currentCps;
    }


    /**
     * @return MineceitPlayer
     *
     * Gets the player.
     */
    public function getPlayer()
    {
        return $this->player;
    }
}