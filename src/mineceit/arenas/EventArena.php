<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-03
 * Time: 13:52
 */

declare(strict_types=1);

namespace mineceit\arenas;


use mineceit\kits\AbstractKit;
use mineceit\kits\Kits;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;

class EventArena extends Arena
{

    const P1 = 'p1';
    const P2 = 'p2';

    /** @var Vector3 */
    protected $spectatorsSpawn;

    /** @var Vector3 */
    protected $center;

    /** @var Vector3 */
    protected $p1Spawn, $p2Spawn;

    /** @var Level|null */
    protected $level;

    /** @var string */
    private $name;

    /** @var AbstractKit */
    private $kit;

    /**
     * EventArena constructor.
     * @param string $name
     * @param Vector3 $center
     * @param Level|string $level
     * @param AbstractKit|string $kit
     * @param Vector3|null $p1Spawn
     * @param Vector3|null $p2Spawn
     * @param Vector3|null $specSpawn
     */
    public function __construct(string $name, $center, $level, $kit = Kits::SUMO, $p1Spawn = null, $p2Spawn = null, $specSpawn = null)
    {
        $kits = MineceitCore::getKits();
        $this->name = $name;
        $this->kit = $kit instanceof AbstractKit ? $kit : $kits->getKit($kit);
        $this->center = $center;
        $this->spectatorsSpawn = $specSpawn ?? $center;
        $this->p1Spawn = $p1Spawn ?? $center;
        $this->p2Spawn = $p2Spawn ?? $center;
        $this->level = $level instanceof Level ? $level : Server::getInstance()->getLevelByName($level);
    }

    /**
     * @return Level|null
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return Vector3
     */
    public function getCenter() : Vector3 {
        return $this->center;
    }

    /**
     * @return AbstractKit|null
     */
    public function getKit() {
        return $this->kit;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @param MineceitPlayer $player
     * @param $value
     */
    public function teleportPlayer(MineceitPlayer $player, $value = false): void
    {

        $spawn = $this->spectatorsSpawn;

        $getKit = false;

        if($value === self::P1) {
            $spawn = $this->p1Spawn;
            $getKit = true;
        }

        if($value === self::P2) {
            $spawn = $this->p2Spawn;
            $getKit = true;
        }

        if(!$getKit) {
            $player->clearKit();
            $itemManager = MineceitCore::getItemHandler();
            $itemManager->spawnEventItems($player);
        } elseif ($this->kit !== null) {
            $this->kit->giveTo($player, false);
        }

        if($this->level !== null) {
            $pos = MineceitUtil::toPosition($spawn, $this->level);
            $player->teleport($pos);
        }
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $kit = $this->getKit();
        $kit = $kit !== null ? $kit->getName() : null;
        $center = MineceitUtil::posToArray($this->center);
        $specSpawn = MineceitUtil::posToArray($this->spectatorsSpawn);
        $p1Spawn = MineceitUtil::posToArray($this->p1Spawn);
        $p2Spawn = MineceitUtil::posToArray($this->p2Spawn);
        $level = ($this->level !== null) ? $this->level->getName() : null;

        return [
            'level' => $level,
            'center' => $center,
            'spawn' => $specSpawn,
            'kit' => $kit,
            'p1' => $p1Spawn,
            'p2' => $p2Spawn,
            'type' => self::TYPE_EVENT
        ];
    }

    /**
     * @param Vector3 $pos
     */
    public function setP1SpawnPos(Vector3 $pos) : void {
        $this->p1Spawn = $pos;
    }


    /**
     * @param Vector3 $pos
     */
    public function setP2SpawnPos(Vector3 $pos) : void {
        $this->p2Spawn = $pos;
    }

    /**
     * @param Vector3 $pos
     */
    public function setSpawn(Vector3 $pos) : void {
        $this->spectatorsSpawn = $pos;
    }

    /**
     * @return string
     *
     * Gets the texture used by the kit.
     */
    public function getTexture() : string {
        $texture = '';
        if($this->kit !== null) {
            $texture = $this->kit->getTexture();
        }
        return $texture;
    }
}