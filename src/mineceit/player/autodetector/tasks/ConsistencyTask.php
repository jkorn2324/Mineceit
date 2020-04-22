<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-29
 * Time: 15:02
 */

declare(strict_types=1);

namespace mineceit\player\autodetector\tasks;


use mineceit\player\autodetector\checks\ConsistencyCheck;
use pocketmine\scheduler\Task;

class ConsistencyTask extends Task
{

    /** @var ConsistencyCheck */
    private $check;

    public function __construct(ConsistencyCheck $check)
    {
        $this->check = $check;
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
        $player = $this->check->getPlayer();

        if($player !== null and $player->isOnline()) {
            $this->check->resetClicks();
        }
    }
}