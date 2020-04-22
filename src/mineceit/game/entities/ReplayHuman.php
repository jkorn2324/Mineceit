<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-26
 * Time: 01:49
 */

declare(strict_types=1);

namespace mineceit\game\entities;

use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Human;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class ReplayHuman extends Human
{

    /* @var Vector3
     * The target position where you want the player to go.
     */
    private $targetPosition = null;

    /* @var bool
     * Changes the speed of the player to determine when they are sprinting.
     */
    private $sprinting = false;

    /* @var Vector3
     * The current block being broken.
     */
    private $currentBlockBroken = null;

    /* @var bool
     * Determines if the player should stop breaking.
     */
    private $stopBreaking = false;

    /* @var FishingHook */
    private $fishing = null;

    /* @var bool */
    private $paused = false;

    /* @var int */
    private $startAction = -1;

    public function onUpdate(int $currentTick): bool
    {

        $update = parent::onUpdate($currentTick);

        if(!$this->isAlive() or $this->paused) return false;

        if($this->targetPosition !== null) {

            $x = $this->targetPosition->x - $this->x;
            $z = $this->targetPosition->z - $this->z;
            $y = $this->targetPosition->y - $this->y;

            $speed = 4.0;

            $diff = abs($x) + abs($z);

            if($this->sprinting) $speed = 5.0;

            $this->motion->y = $speed * 0.15 * $y;

            if ($x ** 2 + $z ** 2 < 0.7) {
                $this->motion->x = 0;
                $this->motion->z = 0;
            }else{
                $this->motion->x = $speed * 0.15 * ($x / $diff);
                $this->motion->z = $speed * 0.15 * ($z / $diff);
            }
        }

        if($this->isOnFire()) {
            $this->setHealth($this->getMaxHealth());
        }

        return $update;
    }

    /**
     * @param Vector3 $target
     *
     * Sets the target position the player needs to go to.
     */
    public function setTargetPosition(Vector3 $target) : void {
        $this->targetPosition = $target;
    }

    /**
     * @param bool $value
     *
     * Sets the player as sprinting.
     */
    public function setSprinting(bool $value = true): void
    {
        $this->sprinting = $value;
        parent::setSprinting($value);
    }

    /**
     * @param Vector3|null $block
     * Sets the human as breaking a block -> for level events.
     */
    public function setBreaking(Vector3 $block = null) : void {

        if($block === null and $this->currentBlockBroken !== null) {
            $this->stopBreaking = true;
            return;
        }

        $this->currentBlockBroken = $block;
    }

    /**
     * @param bool $paused
     *
     * Turns the human on pause.
     */
    public function setPaused(bool $paused) : void {
        $this->paused = $paused;
    }

    protected function doOnFireTick(int $tickDiff = 1): bool
    {
        return ($this->paused) ? false : parent::doOnFireTick($tickDiff);
    }

    /**
     * @return Vector3|null
     * When player is breaking a block.
     */
    public function getBlockBroken() {
        return $this->currentBlockBroken;
    }

    /*
     * Has the player start fishing.
     */
    public function startFishing() : void {

        if(!$this->isFishing()) {
            $pos = $this->getPosition();
            $tag = Entity::createBaseNBT($pos->add(0.0, $this->getEyeHeight(), 0.0), $this->getDirectionVector(), floatval($this->yaw), floatval($this->pitch));
            $rod = Entity::createEntity('FishingHook', $this->getLevel(), $tag, $this);

            if ($rod !== null) {
                $x = -sin(deg2rad($this->yaw)) * cos(deg2rad($this->pitch));
                $y = -sin(deg2rad($this->pitch));
                $z = cos(deg2rad($this->yaw)) * cos(deg2rad($this->pitch));
                $rod->setMotion(new Vector3($x, $y, $z));
            }

            if (!is_null($rod) and $rod instanceof FishingHook) {
                $ev = new ProjectileLaunchEvent($rod);
                $ev->call();
                if ($ev->isCancelled()) {
                    $rod->flagForDespawn();
                } else {
                    $rod->spawnToAll();
                    $this->fishing = $rod;
                    $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
                }
            }
        }
    }

    /**
     * @param bool $click
     * @param bool $killEntity
     */
    public function stopFishing(bool $click = true, bool $killEntity = true) : void
    {

        if ($this->isFishing() and $this->fishing instanceof FishingHook) {
            $rod = $this->fishing;
            if ($click === true) {
                $rod->reelLine();
            } elseif ($rod !== null) {
                if (!$rod->isClosed() and $killEntity === true) {
                    $rod->kill();
                    $rod->close();
                }
            }
        }

        $this->fishing = null;
    }

    /**
     * @return bool
     */
    public function isFishing() : bool {
        return $this->fishing !== null;
    }

    /**
     * Returns whether the player is currently using an item (right-click and hold).
     * @return bool
     */
    public function isUsingItem() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_ACTION) and $this->startAction > -1;
    }

    /**
     * @param bool $value
     */
    public function setUsingItem(bool $value){
        $this->startAction = $value ? $this->server->getTick() : -1;
        $this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
    }

    /**
     * @return int
     */
    public function getItemUseDuration() : int{
        return $this->startAction === -1 ? -1 : ($this->server->getTick() - $this->startAction);
    }


    /**
     * @param float $force
     *
     * Releases the bow.
     */
    public function setReleaseBow(float $force) : void {

        $nbt = Entity::createBaseNBT(
            $this->add(0, $this->getEyeHeight(), 0),
            $this->getDirectionVector(),
            ($this->yaw > 180 ? 360 : 0) - $this->yaw,
            -$this->pitch
        );

        $nbt->setShort("Fire",$this->isOnFire() ? 45 * 60 : 0);

        $entity = Entity::createEntity("ReplayArrow", $this->getLevel(), $nbt, $this, $force >= 1);

        if($entity instanceof Projectile) {

            $entity->setMotion($entity->getMotion()->multiply($force));

            $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW);

            $entity->spawnToAll();
        }
    }

    // Overrides original movement function. Hack for fixing 1.14.3 movements.
    protected function broadcastMovement(bool $teleport = false): void
    {
        $pk = new MoveActorAbsolutePacket();
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this);

        //this looks very odd but is correct as of 1.5.0.7
        //for arrows this is actually x/y/z rotation
        //for mobs x and z are used for pitch and yaw, and y is used for headyaw
        $pk->xRot = $this->pitch;
        $pk->yRot = $this->yaw;
        $pk->zRot = $this->yaw;

        if($teleport){
            $pk->flags |= MoveActorAbsolutePacket::FLAG_TELEPORT;
        }

        /** @var MineceitPlayer[] $viewers */
        $viewers = $this->getViewers();

        // Broadcasts the packet to 1.14.2 & below players.
        MineceitUtil::broadcastDataPacket($this, $pk, function(MineceitPlayer $player) {
            return strpos($player->getVersion(), "1.14.3") === false;
        }, $viewers);

        $viewers = array_filter($viewers, function(MineceitPlayer $viewer) {
            return strpos($viewer->getVersion(), "1.14.3") !== false;
        });

        // For those who are 1.14.3 & above, broadcasts position to them.
        $this->sendPosition($this->asVector3(), $this->yaw, $this->pitch, MovePlayerPacket::MODE_NORMAL, $viewers);
    }

    /**
     * @param Vector3 $pos
     * @param float|null $yaw
     * @param float|null $pitch
     * @param int $mode
     * @param array|null $targets
     *
     * Sends the position to the viewers. -> Hack for 1.14.3 movement bugs.
     */
    public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null){

        $yaw = $yaw ?? $this->yaw;
        $pitch = $pitch ?? $this->pitch;

        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->getOffsetPosition($pos);
        $pk->pitch = $pitch;
        $pk->headYaw = $yaw;
        $pk->yaw = $yaw;
        $pk->mode = $mode;

        if($targets !== null){
            $this->server->broadcastPacket($targets, $pk);
        }
    }
}