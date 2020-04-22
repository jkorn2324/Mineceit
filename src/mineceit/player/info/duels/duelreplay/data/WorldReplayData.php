<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-25
 * Time: 17:52
 */

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\data;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class WorldReplayData extends ReplayData
{

    const TYPE_DUEL = "type_duel";
    const TYPE_SUMO = "type_sumo";
    const TYPE_SPLEEF = "type_spleef";

    /* @var array
     * For determining when a block was updated -> eg. break, place, etc.
     */
    private $blockTimes;

    /* @var string
     * For determining the world type -> uses the generator class.
     */
    private $worldType;

    /* @var bool
     * Determines if the duel was ranked.
     */
    private $ranked;

    /** @var string */
    private $generatorClass;

    public function __construct(string $worldType, bool $ranked, string $generatorClass)
    {
        $this->worldType = $worldType;
        $this->blockTimes = [];
        $this->ranked = $ranked;
        $this->generatorClass = $generatorClass;
    }

    /**
     * @param int $tick
     * @param Block|int $block
     * @param Vector3 $pos
     * @param $meta
     *
     * Saves when a block is placed and broken at a given tick based on position.
     */
    public function setBlockAt(int $tick, $block, Vector3 $pos = null, int $meta = 0) : void {

        $blockData = null;

        if($block instanceof Block)
            $blockData = new BlockData($block);
        elseif (is_int($block) and $pos !== null)
            $blockData = new BlockData($block, $meta, $pos);

        $blockTime = $this->blockTimes[$tick];

        if($blockData !== null) {

            $strPos = null;

            if($pos !== null) {
                $x = (int)$pos->x;
                $y = (int)$pos->y;
                $z = (int)$pos->z;
                $strPos = "$x:$y:$z";
            } else {
                $x = (int)$block->x;
                $y = (int)$block->y;
                $z = (int)$block->z;
                $strPos = "$x:$y:$z";
            }

            $id = 0;
            $meta = 0;
            if($pos !== null and $pos instanceof Block) {
                $id = $pos->getId();
                $meta = $pos->getDamage();
            }

            $blockTime[$strPos] = $blockData;

            $this->blockTimes[$tick] = $blockTime;

            $lastTick = $tick - 1;

            while($lastTick >= 0) {

                if(!isset($this->blockTimes[$lastTick]))
                    $this->blockTimes[$lastTick] = [];

                if(!isset($this->blockTimes[$lastTick][$strPos])) {
                    $this->blockTimes[$lastTick][$strPos] = new BlockData($id, $meta, $blockData->getPosition());
                } else {
                    return;
                }

                $lastTick--;
            }
        }
    }

    /**
     * @param int $tick
     * @param Level|null $level
     *
     * Updates the blocks.
     */
    public function update(int $tick, ?Level $level) : void {

        $lastTick = $tick - 1;

        if($lastTick >= 0 and isset($this->blockTimes[$lastTick]))
            $this->blockTimes[$tick] = $this->blockTimes[$lastTick];
        else $this->blockTimes[$tick] = [];

        if($level !== null and isset($this->blockTimes[$lastTick])) {
            $currentBlockTimes = $this->blockTimes[$tick];
            $lastBlockTimes = $this->blockTimes[$lastTick];
            $lastKeys = array_keys($lastBlockTimes);
            foreach ($lastKeys as $lastKey) {
                /* @var BlockData $lastBlockData */
                $lastBlockData = $lastBlockTimes[$lastKey];
                $lastBlock = $lastBlockData->getBlock();
                $pos = $lastBlockData->getPosition();
                $block = $level->getBlock($pos);
                if($lastBlock->getId() !== $block->getId() or ($lastBlock->getId() === $block->getId() and $lastBlock->getDamage() !== $block->getDamage()))
                    $currentBlockTimes[$lastKey] = new BlockData($block);
            }
            $this->blockTimes[$tick] = $currentBlockTimes;
        }
    }

    /**
     * @param int $tick
     * @return array
     *
     * Gets the editions that occur within the world at a particular tick.
     */
    public function getAttributesAt(int $tick) : array {

        $result = [];

        if(isset($this->blockTimes[$tick]))
            $result['blocks'] = $this->blockTimes[$tick];

        return $result;
    }

    /**
     * @return string
     */
    public function getWorldType() : string {
        return $this->worldType;
    }


    /**
     * @return string
     *
     * Gets the generator class from its world type.
     */
    public function getGeneratorClass() : string {
        return $this->generatorClass;
    }

    /**
     * @return bool
     */
    public function isRanked() : bool {
        return $this->ranked;
    }
}