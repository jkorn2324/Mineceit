<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-30
 * Time: 15:26
 */

declare(strict_types=1);

namespace mineceit\game\entities;

use pocketmine\network\mcpe\protocol\TakeItemActorPacket;

class ReplayItemEntity extends MineceitItemEntity
{

    /* @var bool
     * Determined when the item is paused.
     */
    private $paused = false;

    /* @var int
     * Determined when the item is picked up.
     */
    private $pickupTick = -1;

    /* @var ReplayHuman $human
     * The human that will pick up the item.
     */
    private $humanPickup = null;

    /* @var int
     * Determined when the item was first dropped.
     */
    private $spawnTick = -1;

    public function onUpdate(int $currentTick): bool
    {
        if($this->closed or $this->paused) return false;

        return parent::onUpdate($currentTick);
    }

    /**
     * @param bool $paused
     */
    public function setPaused(bool $paused) : void {
        $this->paused = $paused;
    }

    /**
     * @param ReplayHuman $human
     */
    private function pickupItem(ReplayHuman $human) : void {

        /*if($this->getPickupDelay() !== 0){
            return;
        }*/

        $pk = new TakeItemActorPacket();
        $pk->eid = $human->getId();
        $pk->target = $this->getId();
        $this->server->broadcastPacket($this->getViewers(), $pk);

        $this->flagForDespawn();
    }


    /**
     * @param int $replayTick
     */
    public function updatePickup(int $replayTick) : void {
        if($this->pickupTick > 0 and $replayTick >= $this->pickupTick and $this->humanPickup !== null) {
            $this->pickupItem($this->humanPickup);
        }
    }


    /**
     * @param int $time
     */
    public function setPickupTick(int $time) : void {
        $this->pickupTick = $time;
    }

    /**
     * @param ReplayHuman $human
     */
    public function setHumanPickup(ReplayHuman $human) : void {
        $name = $human->getName();
        $this->humanPickup = $human;
    }

    /**
     * @param int $replayTick
     * @return bool
     */
    public function shouldDespawn(int $replayTick) : bool {
        $diff = $replayTick - $this->pickupTick;
        return ($this->spawnTick > 0 and $replayTick < $this->spawnTick) or ($this->pickupTick > 0 and $diff >= 0);
    }

    /**
     * @param int $tick
     */
    public function setDroppedTick(int $tick) : void {
        $this->spawnTick = $tick;
    }
}