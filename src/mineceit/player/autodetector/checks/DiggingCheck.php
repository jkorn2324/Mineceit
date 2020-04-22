<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-29
 * Time: 00:43
 */

declare(strict_types=1);

namespace mineceit\player\autodetector\checks;


use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class DiggingCheck extends AbstractCheck
{

    /** @var MineceitPlayer */
    private $player;

    /** @var int */
    private $lastTime;

    /** @var Position */
    private $lastLocation;

    /** @var MineceitCore */
    private $core;

    public function __construct(MineceitPlayer $player)
    {
        parent::__construct();
        $this->player = $player;
        $this->lastLocation = null;
        $this->lastTime = 0;
        $this->core = MineceitCore::getInstance();
    }

    /**
     * @param Position $block
     *
     * Checks the blocks.
     */
    public function checkBlocks(Position $block) : void {

        if($this->lastLocation !== null and $this->lastLocation->equals($block)) {
            $millis = round(microtime(true) * 1000);
            $abs = abs($millis - $this->lastTime);
            if($abs <= 150) {
                $this->increaseViolation();
            }
        }

        $this->lastTime = round(microtime(true) * 1000);
        $this->lastLocation = $block;
    }

    /**
     * Sends an alert.
     */
    public function sendAlert(): void {}
}