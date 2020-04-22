<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-01
 * Time: 16:52
 */

declare(strict_types=1);

namespace mineceit\duels\level\classic;


use mineceit\kits\Kits;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class ClassicSumoGen extends Generator
{

    protected $level;
    protected $random;
    protected $count;

    /**
     * @param array $settings
     *
     * @throws InvalidGeneratorOptionsException
     */
    public function __construct(array $settings = []) {}

    public function init(ChunkManager $level, Random $rand): void
    {
        $this->level = $level;
        $this->random = $rand;
        $this->count = 0;
    }

    public function generateChunk(int $chunkX, int $chunkZ) : void
    {
        if ($this->level instanceof ChunkManager) {

            $chunk = $this->level->getChunk($chunkX, $chunkZ);
            $chunk->setGenerated();

            if ($chunkX % 20 == 0 && $chunkZ % 20 == 0) {

                for ($x = 0; $x < 11; ++$x) {

                    $start = 0;
                    $end = 11;

                    if($x === 0 or $x === 10) {
                        $start = 3;
                        $end = 8;
                    } elseif ($x === 1 or $x === 9) {
                        $start = 2;
                        $end = 9;
                    } elseif ($x === 2 or $x === 8) {
                        $start = 1;
                        $end = 10;
                    }

                    for ($z = $start; $z < $end; ++$z) {

                        $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA];
                        $meta = [5, 5, 5, 13, 13];
                        $rand = mt_rand(0, count($blocks) - 1);
                        $chunk->setBlock($x, 99, $z, $blocks[$rand], $meta[$rand]);
                        $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                    }
                }

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            }
        }
    }

    public function populateChunk(int $chunkX, int $chunkZ) : void {}

    public function getSettings(): array
    {
        return [];
    }

    public function getName(): string
    {
        return Kits::SUMO;
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(0, 100, 0);
    }
}