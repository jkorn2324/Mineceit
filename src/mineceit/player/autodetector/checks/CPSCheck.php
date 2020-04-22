<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-28
 * Time: 23:37
 */

declare(strict_types=1);

namespace mineceit\player\autodetector\checks;

use mineceit\MineceitCore;
use mineceit\player\autodetector\tasks\CheckCPSTask;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class CPSCheck extends AbstractCheck
{

    /** @var int */
    private $clicks;

    /** @var MineceitPlayer */
    private $player;

    /** @var MineceitCore */
    private $core;

    /** @var int */
    private $lastClicks;

    /** @var int */
    private $consistency;

    public function __construct(MineceitPlayer $player)
    {
        parent::__construct();
        $this->clicks = 0;
        $this->lastClicks = -1;
        $this->consistency = 0;
        $this->player = $player;
        $this->core = MineceitCore::getInstance();
    }


    /**
     * Adds the click.
     */
    public function addClick() {

        if($this->clicks === 0) {
            $task = new CheckCPSTask($this);
            $this->core->getScheduler()->scheduleDelayedTask($task, 20);
        }
        $this->clicks++;
    }

    /**
     * @return MineceitPlayer|null
     */
    public function getPlayer() {
        return $this->player;
    }


    /**
     * Resets the clicks.
     */
    public function resetClicks() : void {
        $this->clicks = 0;
    }


    /**
     * @return int
     *
     * Gets the number of clicks.
     */
    public function getClicks() : int {
        return $this->clicks;
    }


    /**
     * Sends an alert.
     */
    public function sendAlert(): void
    {
    }
}