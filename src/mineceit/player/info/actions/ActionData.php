<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-20
 * Time: 16:26
 */

declare(strict_types=1);

namespace mineceit\player\info\actions;


use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class ActionData
{

    /** @var int */
    private $currentAction;
    /** @var float|int */
    private $currentActionTime;
    /** @var Position|null */
    private $currentActionPos;

    /** @var int */
    private $lastAction;
    /** @var float|int */
    private $lastActionTime;
    /** @var Position|null */
    private $lastActionPos;

    public function __construct()
    {
        $this->currentActionPos = null;
        $this->currentAction = -1;
        $this->currentActionTime = -1;

        $this->lastActionPos = null;
        $this->lastAction = -1;
        $this->lastActionTime;
    }


    /**
     * @param int $action
     * @param Position|null $pos
     *
     * Sets the action.
     */
    public function setAction(int $action, Position $pos = null) : void {

        $currentTime = microtime(true) * 1000;

        if($this->currentAction === -1) {

            $this->currentAction = $action;
            $this->currentActionTime = $currentTime;
            $this->lastAction = PlayerActionPacket::ACTION_ABORT_BREAK;
            $this->lastActionTime = $currentTime;

            $this->currentActionPos = $pos;
            $this->lastActionPos = $pos;

        } else {

            $this->lastAction = $this->currentAction;
            $this->lastActionPos = $this->currentActionPos;
            $this->lastActionTime = $this->currentActionTime;

            $this->currentAction = $action;
            $this->currentActionTime = $currentTime;
            $this->currentActionPos = $pos;
        }
    }


    /**
     * @return bool
     *
     * Tests if the player did click block or if he was breaking it.
     */
    public function didClickBlock() : bool {

        // TODO TEST

        if($this->lastAction === PlayerActionPacket::ACTION_ABORT_BREAK && $this->currentAction === PlayerActionPacket::ACTION_START_BREAK) {

            $differenceTime = $this->currentActionTime - $this->lastActionTime;
            $differenceVec3 = null;

            if($this->lastActionPos !== null && $this->currentActionPos !== null) {

                if($this->lastActionPos->getLevel() !== null && $this->currentActionPos->getLevel() !== null) {
                    if($this->lastActionPos->getLevel()->getName() !== $this->currentActionPos->getLevel()->getName()) {
                        return false;
                    }
                }

                $differenceVec3 = $this->currentActionPos->subtract($this->lastActionPos)->abs();
            }

            if($differenceTime > 5) {

                if($differenceVec3 instanceof Vector3) {
                    return $differenceVec3->x <= 1 && $differenceVec3->z <= 1;
                }

                return true;
            }
        }

        return false;
    }
}