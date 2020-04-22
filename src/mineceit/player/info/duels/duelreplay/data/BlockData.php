<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-25
 * Time: 17:55
 */

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\data;


use pocketmine\block\Block;
use pocketmine\math\Vector3;

class BlockData
{

    /* @ar int
     * The id of the block.
     */
    private $id;

    /* @var int
     * The metadata of the block.
     */
    private $meta;

    /* @var Vector3
     * The position of the block.
     */
    private $position;


    /**
     * BlockData constructor.
     * @param Block|int $block
     * @param int $meta
     * @param Vector3 $pos
     */
    public function __construct($block, int $meta = 0, Vector3 $pos = null)
    {
        if($block instanceof Block) {
            $this->position = $block->asVector3();
            $this->meta = $block->getDamage();
            $this->id = $block->getId();
        } elseif (is_int($block) and $pos !== null) {
            $this->id = $block;
            $this->meta = $meta;
            $this->position = $pos;
        }
    }

    /**
     * @return Block
     */
    public function getBlock() : Block {
        return Block::get($this->id, $this->meta);
    }

    /**
     * @return Vector3
     */
    public function getPosition() : Vector3 {
        return $this->position;
    }


}