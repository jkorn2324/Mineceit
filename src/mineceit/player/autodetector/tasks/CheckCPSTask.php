<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-28
 * Time: 23:42
 */

declare(strict_types=1);

namespace mineceit\player\autodetector\tasks;


use mineceit\MineceitCore;
use mineceit\player\autodetector\checks\AbstractCheck;
use mineceit\player\autodetector\checks\CPSCheck;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\Task;

class CheckCPSTask extends Task
{

    /** @var CPSCheck */
    private $check;

    public function __construct(CPSCheck $check)
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
            $clicks = $this->check->getClicks();
            if($clicks > AbstractCheck::MAX_CLICK_SPEED) {
                if(!MineceitCore::AUTOCLICK_DETECTOR_ENABLED) {
                    $this->check->resetClicks();
                    return;
                }
                $this->check->increaseViolation();
            }
            $this->check->resetClicks();
        }
    }
}