<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-18
 * Time: 12:46
 */

declare(strict_types=1);

namespace mineceit\arenas;

use mineceit\kits\AbstractKit;
use mineceit\kits\Kits;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;

class FFAArena extends Arena
{

    /* @var Vector3 */
    protected $center;

    /* @var AbstractKit */
    protected $kit;

    /* @var Level|null */
    protected $level;

    /** @var string */
    private $name;

    /** @var bool */
    private $open;

    /** @var Vector3 */
    private $spawn;

    /** @var int */
    private $size = 15;

    /**
     * FFAArena constructor.
     * @param string $name
     * @param Vector3 $center
     * @param Vector3 $spawn
     * @param Level|string $level
     * @param AbstractKit|string $kit
     */
    public function __construct(string $name, $center, $spawn, $level, $kit = Kits::FIST)
    {
        $kits = MineceitCore::getKits();
        $this->name = $name;
        $this->kit = ($kit instanceof AbstractKit) ? $kit : $kits->getKit($kit);
        $this->center = $center;
        $this->level = ($level instanceof Level) ? $level : Server::getInstance()->getLevelByName($level);
        $this->open = true;
        $this->spawn = $spawn;
    }

    /**
     * @return bool
     *
     * Determines if an arena is open or not.
     */
    public function isOpen() : bool {
        return $this->open;
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
    public function teleportPlayer(MineceitPlayer $player, $value = true) : void {

        if($this->kit !== null) {
            $this->kit->giveTo($player, false);
        }

        if($this->level !== null) {

            $pos = MineceitUtil::toPosition($this->spawn, $this->level);
            $player->teleport($pos);

            $language = $player->getLanguage();

            $message = $language->arenaMessage(Language::ENTER_ARENA, $this);

            if($value) {
                $player->sendMessage($message);
            }
        }
    }


    /**
     * @param MineceitPlayer $player
     * @return bool
     *
     * Determines whether the player is in the protection.
     */
    public function isWithinProtection(MineceitPlayer $player) : bool {

        $maxX = $this->spawn->x + $this->size;
        $minX = $this->spawn->x - $this->size;
        $maxY = 255;
        $minY = $this->spawn->y - 3;
        if($minY <= 0) {
            $minY = 0;
        }
        $maxZ = $this->spawn->z + $this->size;
        $minZ = $this->spawn->z - $this->size;

        $position = $player->asVector3();

        $withinX = MineceitUtil::isWithinBounds($position->x, $maxX, $minX);
        $withinY = MineceitUtil::isWithinBounds($position->y, $maxY, $minY);
        $withinZ = MineceitUtil::isWithinBounds($position->z, $maxZ, $minZ);

        return $withinX and $withinY and $withinZ;
    }


    /**
     * @param MineceitPlayer $player
     *
     * Sets the spawn of the arena.
     */
    public function setSpawn(MineceitPlayer $player) : void {
        $this->spawn = $player->asVector3();
    }


    /**
     * @return array
     */
    public function getData() : array {

        $kit = $this->getKit();
        $kitStr = ($kit !== null) ? $kit->getName() : null;
        $posArr = MineceitUtil::posToArray($this->center);
        $spawnArr = MineceitUtil::posToArray($this->spawn);
        $level = ($this->level !== null) ? $this->level->getName() : null;
        return [
            'kit' => $kitStr,
            'center' => $posArr,
            'spawn' => $spawnArr,
            'level' => $level,
            'type' => self::TYPE_FFA
        ];
    }

    /**
     * @return string
     */
    public function getTexture() {
        $texture = '';
        if($this->kit !== null)
            $texture = $this->kit->getTexture();
        return $texture;
    }
}