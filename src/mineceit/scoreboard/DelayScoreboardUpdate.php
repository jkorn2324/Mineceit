<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-23
 * Time: 17:27
 */

declare(strict_types=1);

namespace mineceit\scoreboard;


use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\Task;

class DelayScoreboardUpdate extends Task
{

    /* @var MineceitPlayer */
    private $player;

    /* @var string */
    private $type;

    public function __construct(string $type, MineceitPlayer $player = null)
    {
        $this->player = $player;
        $this->type = $type;
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
        ScoreboardUtil::updateSpawnScoreboard($this->type, $this->player);
    }
}