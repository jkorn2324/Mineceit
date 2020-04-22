<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-08
 * Time: 01:23
 */

declare(strict_types=1);

namespace mineceit\player\tasks;


use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\Task;

class OnDeathTask extends Task
{

    /** @var MineceitPlayer $player */
    private $player;

    public function __construct(MineceitPlayer $player)
    {
        $this->player = $player;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick)
    {

        if($this->player !== null and $this->player->isOnline()) {
            $this->player->respawn();
        }
    }
}