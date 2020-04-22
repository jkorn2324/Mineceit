<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-25
 * Time: 17:27
 */

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\data;


use mineceit\player\MineceitPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class PlayerReplayData extends ReplayData
{

    /* @var array|float[]
     * For determining when the player was damaged;
     */
    private $damagedTimes;

    /* @var array|int[]
     * For determining when the player did an animation;
     */
    private $animationTimes;

    /* @var array|Item[]
     * For determining when the player has an item in their hand;
     */
    private $itemTimes;

    /* @var array|int[]
     * For determining the player's cps.
     */
    private $cpsTimes;

    /* @var array
     * For determining the player's direction/rotation.
     */
    private $rotationTimes;

    /* @var string
     * The name of the player we are tracking.
     */
    private $name;

    /* @var array|string[]
     * The name tag of the player we are tracking.
     */
    private $nameTagTimes;

    /* @var Vector3
     * The start position of the player.
     */
    private $startPosition;

    /* @var int
     * The player's time of death.
     */
    private $deathTime;

    /* @var array|float[]
     * The start rotation of the player.
     */
    private $startRotation;

    /* @var array|Item[]
     * The start inventory of the player.
     */
    private $startInventory;

    /* @var array|Item[]
     * The armor inventory of the player.
     */
    private $startArmor;

    /* @var string
     * The start name tag of the player.
     */
    private $startTag;

    /* @var array|bool[]
     * Times when player jumped.
     */
    private $jumpTimes;

    /* @var array|ProjectileItem[]
     * Times when player throws an item.
     */
    private $throwTimes;

    /* @var array|bool[]
     * Times when player has drank a potion or eaten food.
     */
    private $consumeTimes;

    /* @var array|EffectInstance
     * Times when player has an effect.
     */
    private $effectTimes;

    /* @var array
     * Times when the player changes armor.
     */
    private $armorTimes;

    /* @var array|bool[]
     * Times when the player is sprinting.
     */
    private $sprintTimes;

    /* @var array|Vector3[]
     * Current Positions of the player at a given time.
     */
    private $positionTimes;

    /* @var array|bool[]
     * Determines when the player starts & stops fishing.
     */
    private $fishTimes;

    /* @var Skin
     * In case skinTag breaks.
     */
    private $skin;

    /* @var array|bool[]
     * Determines when the player is on fire.
     */
    private $fireTimes;

    /* @var array|float[]
     * Determines when the player is using a bow.
     */
    private $bowTimes;

    /* @var array
     * Determines when the player drops an item.
     */
    private $dropTimes;


    /* @var array
     * Determines when the player is sneaking.
     */
    private $sneakTimes;

    public function __construct(MineceitPlayer $player)
    {
        $this->skin = $player->getSkin();
        $this->name = $player->getName();
        $this->nameTagTimes = [];
        $this->animationTimes = [];
        $this->itemTimes = [];
        $this->damagedTimes = [];
        $this->rotationTimes = [];
        $this->jumpTimes = [];
        $this->cpsTimes = [];
        $this->throwTimes = [];
        $this->consumeTimes = [];
        $this->effectTimes = [];
        $this->armorTimes = [];
        $this->sprintTimes = [];
        $this->positionTimes = [];
        $this->fishTimes = [];
        $this->fireTimes = [];
        $this->bowTimes = [];
        $this->sneakTimes = [];
        $this->dropTimes = [];
        $this->startPosition = $player->asVector3();
        $this->startRotation = ['yaw' => $player->getYaw(), 'pitch' => $player->getPitch()];
        $this->deathTime = -1;
        $this->startInventory = $player->getInventory()->getContents(true);
        $this->startArmor = $player->getArmorInventory()->getContents(true);
        $this->startTag = $player->getNameTag();
    }

    /**
     * @param int $tick
     * @param bool $sneak
     */
    public function setSneakingAt(int $tick, bool $sneak) : void {
        $this->sneakTimes[$tick] = $sneak;
    }

    /**
     * @param int $tick
     * @param float $force
     *
     * Sets the time when the player uses a bow.
     */
    public function setReleaseBowAt(int $tick, float $force) : void {
        $this->bowTimes[$tick] = $force;
    }

    /**
     * @param int $tick
     */
    public function setJumpAt(int $tick) : void {
        $this->jumpTimes[$tick] = true;
    }

    /**
     * @param int $tick
     * @param float $damage
     *
     * Sets the damage of the player at a given tick.
     */
    public function setDamagedAt(int $tick, float $damage) : void {
        $this->damagedTimes[$tick] = $damage;
    }

    /**
     * @param int $tick
     * @param Item $item
     * @param Vector3 $motion
     *
     * Sets the tick when the player drops an item.
     */
    public function setDropAt(int $tick, Item $item, Vector3 $motion) : void {
        $this->dropTimes[$tick] = ['item' => $item, 'motion' => $motion];
    }


    /**
     * @param int $tick
     * @param Item $item
     *
     * Sets the tick when the player picks up an item.
     */
    public function setPickupAt(int $tick, Item $item) : void {
        $droppedItem = null;
        $currentTick = $tick;
        while($droppedItem === null and $currentTick >= 0) {
            if(isset($this->dropTimes[$currentTick], $this->dropTimes[$currentTick]['item'])) {
                $matchedItem = $this->dropTimes[$currentTick]['item'];
                if($item->equals($matchedItem)) {
                    $droppedItem = $this->dropTimes[$currentTick];
                    break;
                }
            }
            $currentTick--;
        }

        if($droppedItem !== null) {
            $droppedItem['pickup'] = $tick;
            $this->dropTimes[$currentTick] = $droppedItem;
        }
    }

    /**
     * @param int $tick
     * @param string $tag
     *
     * Sets the name tag of the player at a given tick.
     */
    public function setNameTagAt(int $tick, string $tag) : void {

        $lastIndex = count($this->nameTagTimes) - 1;
        if ($lastIndex < 0) {
            $this->nameTagTimes[$tick] = $tag;
            return;
        }

        $lastKey = array_keys($this->nameTagTimes)[$lastIndex];
        $lastValue = $this->nameTagTimes[$lastKey];
        if ($lastValue !== $tag)
            $this->nameTagTimes[$tick] = $tag;
    }

    /**
     * @param int $tick
     * @param Item $item
     *
     * Sets the item in the player's hand at a given tick.
     */
    public function setItemAt(int $tick, Item $item) : void {

        $lastIndex = count($this->itemTimes) - 1;
        if ($lastIndex < 0) {
            $this->itemTimes[$tick] = $item;
            return;
        }

        $lastKey = array_keys($this->itemTimes)[$lastIndex];
        $lastValue = $this->itemTimes[$lastKey];
        if ($item->getId() !== $lastValue->getId() or ($item->getId() === $lastValue->getId() and $item->getDamage() !== $lastValue->getDamage()))
            $this->itemTimes[$tick] = $item;
    }

    /**
     * @param int $tick
     * @param int $animation -> uses constants from AnimatePacket
     *
     * Sets the animation type of the player at a given tick.
     */
    public function setAnimationAt(int $tick, int $animation) : void {
        $this->animationTimes[$tick] = $animation;
    }


    /**
     * @param int $tick
     * @param bool|int $variable
     *
     * Sets the player on fire at a given tick.
     */
    public function setFireAt(int $tick, $variable) : void {
        $this->fireTimes[$tick] = $variable;
    }

    /**
     * @param int $tick
     * @param int $cps
     *
     * Sets the cps of the player at a given tick.
     */
    public function setCpsAt(int $tick, int $cps) : void {

        $lastIndex = count($this->cpsTimes) - 1;
        if ($lastIndex < 0) {
            $this->cpsTimes[$tick] = $cps;
            return;
        }

        $lastKey = array_keys($this->cpsTimes)[$lastIndex];
        $lastValue = $this->cpsTimes[$lastKey];
        if ($lastValue !== $cps)
            $this->cpsTimes[$tick] = $cps;
    }


    /**
     * @param int $tick
     * @param array $rotation
     *
     * Sets the rotation of the player at a given tick.
     */
    public function setRotationAt(int $tick, $rotation) : void {
        $lastIndex = count($this->rotationTimes) - 1;
        if ($lastIndex < 0) {
            $this->rotationTimes[$tick] = $rotation;
            return;
        }

        $lastKey = array_keys($this->rotationTimes)[$lastIndex];
        $lastValue = $this->rotationTimes[$lastKey];
        $lastYaw = $lastValue['yaw'];
        $lastPitch = $lastValue['pitch'];
        $yaw = $rotation['yaw'];
        $pitch = $rotation['pitch'];
        if ($lastYaw !== $yaw and $lastPitch !== $pitch)
            $this->rotationTimes[$tick] = $rotation;
    }

    /**
     * @param int $tick
     *
     * Sets the player's time of death.
     */
    public function setDeathTime(int $tick) : void {
        $this->deathTime = $tick;
    }

    /**
     * @param int $tick
     * @param ProjectileItem $item
     *
     * Sets when the player threw a projectile.
     */
    public function setThrowAt(int $tick, ProjectileItem $item) : void {
        $this->throwTimes[$tick] = $item;
    }

    /**
     * @param int $tick
     * @param bool $drink -> determines when a player drank or eat
     *
     * Sets when the player ate something.
     */
    public function setConsumeAt(int $tick, bool $drink = false) : void {
        $this->consumeTimes[$tick] = $drink;
    }

    /**
     * @param int $tick
     * @param EffectInstance $effect
     *
     * Gives an effect for 1 tick.
     */
    public function setEffectAt(int $tick, EffectInstance $effect) : void {
        $duration = $effect->getDuration();
        $length = $tick + $duration;
        $startTick = $tick;
        $type = $effect->getType();
        while($startTick <= $length) {
            if($this->deathTime !== -1 and $startTick >= $this->deathTime)
                return;

            if(!isset($this->effectTimes[$startTick]))
                $this->effectTimes[$startTick][$effect->getId()] = new EffectInstance($type, 1);
            else {
                $effects = $this->effectTimes[$startTick];
                if(!isset($effects[$effect->getId()])) {
                    $effects[$effect->getId()] = new EffectInstance($type, 1);
                    $this->effectTimes[$startTick] = $effects;
                }
            }
            $startTick++;
        }
    }

    /**
     * @param int $tick
     * @param array $armor
     *
     * Updates the armor at a given time.
     */
    public function updateArmor(int $tick, array $armor = []) : void {
        $length = count($this->armorTimes);
        if($length <= 0) {
            $this->armorTimes[$tick] = $armor;
            return;
        }
        $length = $length - 1;
        $keys = array_keys($this->armorTimes);
        $lastKey = $keys[$length];
        $lastArmorUpdate = $this->armorTimes[$lastKey];
        $keys = ['chest', 'helmet', 'pants', 'boots'];

        foreach($keys as $key) {
            if(!isset($lastArmorUpdate[$key]) and isset($armor[$key])){
                if(!isset($this->armorTimes[$tick]))
                    $this->armorTimes[$tick] = [$key => $armor[$key]];
                else $this->armorTimes[$tick][$key] = $armor[$key];
            } else if (isset($lastArmorUpdate[$key]) and isset($armor[$key])) {
                /* @var Item $lastArmor */
                $lastArmor = $lastArmorUpdate[$key];
                /* @var Item $testArmor */
                $testArmor = $armor[$key];
                if (!$lastArmor->equals($testArmor)) {
                    if (!isset($this->armorTimes[$tick]))
                        $this->armorTimes[$tick] = [$key => $testArmor];
                    else
                        $this->armorTimes[$tick][$key] = $testArmor;
                }
            }
        }
    }


    /**
     * @param int $tick
     * @param Vector3 $vec3
     */
    public function setPositionAt(int $tick, Vector3 $vec3) : void {
        $this->positionTimes[$tick] = $vec3;
    }

    /**
     * @param int $tick
     * @param bool $sprinting
     *
     * Sets the human to sprint at a given time.
     */
    public function setSprintingAt(int $tick, bool $sprinting) : void {
        $this->sprintTimes[$tick] = $sprinting;
    }


    /**
     * @param int $tick
     * @param bool $fishing
     *
     * Sets the human to fish.
     */
    public function setFishingAt(int $tick, bool $fishing) : void {
        $this->fishTimes[$tick] = $fishing;
    }


    /**
     * @return int
     */
    public function getDeathTime() : int {
        return $this->deathTime;
    }

    /**
     * @return bool
     */
    public function didDie() : bool {
        return $this->deathTime > 0;
    }

    /**
     * @param int $tick
     * @return array
     *
     * Gets all the attributes at a certain tick.
     */
    public function getAttributesAt(int $tick) : array {

        if($tick === $this->deathTime)
            return ['death' => true];

        $result = [];

        if(isset($this->throwTimes[$tick]))
            $result['thrown'] = $this->throwTimes[$tick];
        if(isset($this->jumpTimes[$tick]))
            $result['jump'] = $this->jumpTimes[$tick];
        if(isset($this->damagedTimes[$tick]))
            $result['damaged'] = $this->damagedTimes[$tick];
        if(isset($this->nameTagTimes[$tick]))
            $result['nameTag'] = $this->nameTagTimes[$tick];
        if(isset($this->itemTimes[$tick]))
            $result['item'] = $this->itemTimes[$tick];
        if(isset($this->animationTimes[$tick]))
            $result['animation'] = $this->animationTimes[$tick];
        if(isset($this->cpsTimes[$tick]))
            $result['cps'] = $this->cpsTimes[$tick];
        if(isset($this->rotationTimes[$tick]))
            $result['rotation'] = $this->rotationTimes[$tick];
        if(isset($this->effectTimes[$tick]))
            $result['effects'] = $this->effectTimes[$tick];
        if(isset($this->consumeTimes[$tick]))
            $result['consumed'] = $this->consumeTimes[$tick];
        if(isset($this->armorTimes[$tick]))
            $result['armor'] = $this->armorTimes[$tick];
        if(isset($this->sprintTimes[$tick]))
            $result['sprinting'] = $this->sprintTimes[$tick];
        if(isset($this->positionTimes[$tick]))
            $result['position'] = $this->positionTimes[$tick];
        if(isset($this->fishTimes[$tick]))
            $result['fishing'] = $this->fishTimes[$tick];
        if(isset($this->fireTimes[$tick]))
            $result['fire'] = $this->fireTimes[$tick];
        if(isset($this->bowTimes[$tick]))
            $result['bow'] = $this->bowTimes[$tick];
        if(isset($this->dropTimes[$tick]))
            $result['drop'] = $this->dropTimes[$tick];
        if(isset($this->sneakTimes[$tick]))
            $result['sneak'] = $this->sneakTimes[$tick];

        return $result;
    }


    /**
     * @param int $tick
     * @param string $attribute
     * @return array|int|float|string|bool|Item|Vector3|null
     *
     * Gets the last attribute update at the given tick.
     */
    public function getLastAttributeUpdate(int $tick, string $attribute) {

        $searchedArray = [];

        switch($attribute) {

            case 'thrown':
                $searchedArray = $this->throwTimes;
                break;
            case 'jump':
                $searchedArray = $this->jumpTimes;
                break;
            case 'damaged':
                $searchedArray = $this->damagedTimes;
                break;
            case 'nameTag':
                $searchedArray = $this->nameTagTimes;
                break;
            case 'item':
                $searchedArray = $this->itemTimes;
                break;
            case 'animation':
                $searchedArray = $this->animationTimes;
                break;
            case 'cps':
                $searchedArray = $this->cpsTimes;
                break;
            case 'rotation':
                $searchedArray = $this->rotationTimes;
                break;
            case 'effects':
                $searchedArray = $this->effectTimes;
                break;
            case 'consumed':
                $searchedArray = $this->consumeTimes;
                break;
            case 'armor':
                $searchedArray = $this->armorTimes;
                break;
            case 'sprinting':
                $searchedArray = $this->sprintTimes;
                break;
            case 'position':
                $searchedArray = $this->positionTimes;
                break;
            case 'fire':
                $searchedArray = $this->fireTimes;
                break;
            case 'bow':
                $searchedArray = $this->bowTimes;
                break;
            case 'sneak':
                $searchedArray = $this->sneakTimes;
                break;
        }

        $lastTick = $tick;

        while(!isset($searchedArray[$lastTick]) and $lastTick >= 0)
            $lastTick--;

        return !isset($searchedArray[$lastTick]) ? null : $searchedArray[$lastTick];
    }

    /**
     * @return array|float[]
     */
    public function getStartRotation() : array {
        return $this->startRotation;
    }

    /**
     * @return Vector3
     */
    public function getStartPosition() : Vector3 {
        return $this->startPosition;
    }

    /**
     * @return array|Item[]
     */
    public function getStartInventory() : array {
        return $this->startInventory;
    }

    /**
     * @return array|Item[]
     */
    public function getArmorInventory() : array {
        return $this->startArmor;
    }

    /**
     * @return string
     */
    public function getStartTag() : string {
        return $this->startTag;
    }

    /**
     * @return Skin
     */
    public function getSkin() {
        return $this->skin;
    }
}