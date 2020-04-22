<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-24
 * Time: 15:02
 */

declare(strict_types=1);

namespace mineceit\parties\level;


use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class SumoTourneyGenerator extends Generator
{


    /*
     * Center = (x = 0, y = 100, z = 0)
     */

    protected $level;
    protected $random;
    protected $count;

    /**
     * @param array $settings
     *
     * @throws InvalidGeneratorOptionsException
     */
    public function __construct(array $settings = []) {}

    public function init(ChunkManager $level, Random $random): void
    {
        $this->level = $level;
        $this->random = $random;
        $this->count = 0;
    }

    public function generateChunk(int $chunkX, int $chunkZ): void
    {
        if($this->level instanceof ChunkManager) {

            $chunk = $this->level->getChunk($chunkX, $chunkZ);
            $chunk->setGenerated();

            $length = 11;

            // $outsideEdgeDifference = 10;

            if ($chunkX % 20 == 0 && $chunkZ % 20 == 0) {

                for ($x = 0; $x < $length; ++$x) {

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

                        $putRing = $z === $start or $z === $end or $x === 0 or $x === $length - 1;

                        if(!$putRing) {

                            $chunk->setBlock($x, 99, $z, BlockIds::TERRACOTTA, 14);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);

                        } else {

                            $chunk->setBlock($x, 99, $z, BlockIds::TERRACOTTA, 6);
                            $chunk->setBlock($x, 98, $z, BlockIds::TERRACOTTA, 14);
                            $chunk->setBlock($x, 97, $z, BlockIds::TERRACOTTA, 14);
                        }
                    }
                }

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } elseif ($chunkX % 20 == 1 && $chunkZ % 20 == 0) {

                // TODO
            }
        }
    }

    public function populateChunk(int $chunkX, int $chunkZ): void {}

    public function getSettings(): array
    {
        return [];
    }

    public function getName(): string
    {
        return 'sumo_tournament';
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(0, 100, 0);
    }
}