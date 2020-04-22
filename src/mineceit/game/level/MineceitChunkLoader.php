<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-29
 * Time: 18:52
 */

declare(strict_types=1);

namespace mineceit\game\level;

use pocketmine\block\Block;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class MineceitChunkLoader implements ChunkLoader
{

    private $position;

    private $chunkX;

    private $chunkZ;

    private $level;

    private $id = 0;

    private $callable;

    public function __construct(Level $level, int $chunkX, int $chunkZ, callable $callable) {

        $this->position = Position::fromObject(new Vector3($chunkX << 4, 0, $chunkZ << 4), $level);
        $this->chunkX = $chunkX;
        $this->chunkZ = $chunkZ;
        $this->level = $level;
        $this->id = Level::generateChunkLoaderId($this);
        $this->callable = $callable;
    }

    /**
     * Returns the ChunkLoader id.
     * Call Level::generateChunkLoaderId($this) to generate and save it
     *
     * @return int
     */
    public function getLoaderId(): int {
        return $this->id;
    }

    /**
     * Returns if the chunk loader is currently active
     *
     * @return bool
     */
    public function isLoaderActive(): bool
    {
        return true;
    }

    /**
     * @return Position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return float
     */
    public function getX()
    {
        return $this->chunkX;
    }

    /**
     * @return float
     */
    public function getZ()
    {
        return $this->chunkZ;
    }

    /**
     * @return Level
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * This method will be called when a Chunk is replaced by a new one
     *
     * @param Chunk $chunk
     */
    public function onChunkChanged(Chunk $chunk) {}

    /**
     * This method will be called when a registered chunk is loaded
     *
     * @param Chunk $chunk
     */
    public function onChunkLoaded(Chunk $chunk)
    {
        if(!$chunk->isPopulated()) {
            $this->level->populateChunk(intval($this->getX()), intval($this->getZ()));
            return;
        }

        $this->onChunkComplete();
    }

    private function onChunkComplete() : void {
        $this->level->unregisterChunkLoader($this, intval($this->getX()), intval($this->getZ()));
        ($this->callable)();
    }

    /**
     * This method will be called when a registered chunk is unloaded
     *
     * @param Chunk $chunk
     */
    public function onChunkUnloaded(Chunk $chunk) {}

    /**
     * This method will be called when a registered chunk is populated
     * Usually it'll be sent with another call to onChunkChanged()
     *
     * @param Chunk $chunk
     */
    public function onChunkPopulated(Chunk $chunk)
    {
        $this->onChunkComplete();
    }

    /**
     * This method will be called when a block changes in a registered chunk
     *
     * @param Block|Vector3 $block
     */
    public function onBlockChanged(Vector3 $block) {}
}