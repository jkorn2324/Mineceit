<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-28
 * Time: 23:34
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

class ClassicSpleefGen extends Generator
{

    protected $level;
    protected $random;
    protected $count;

    /**
     * @param array $settings
     *
     * @throws InvalidGeneratorOptionsException
     */
    public function __construct(array $settings = [])
    {}

    public function init(ChunkManager $level, Random $rand): void
    {
        $this->level = $level;
        $this->random = $rand;
        $this->count = 0;
    }

    public function generateChunk(int $chunkX, int $chunkZ): void
    {

        $block = BlockIds::SNOW_BLOCK;

        if ($this->level instanceof ChunkManager) {

            $chunk = $this->level->getChunk($chunkX, $chunkZ);
            $chunk->setGenerated();

            if ($chunkX % 20 == 0 && $chunkZ % 20 == 0) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 0 or $z == 0) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 0) {

                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($z == 0) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 2 && $chunkZ % 20 == 0) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 15 or $z == 0) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            } else if ($chunkX % 20 == 2 && $chunkZ % 20 == 1) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            } else if ($chunkX % 20 == 2 && $chunkZ % 20 == 2) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 15 or $z == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            } else if ($chunkX % 20 == 0 && $chunkZ % 20 == 1) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 0) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 1) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        $chunk->setBlock($x, 99, $z, $block);
                        // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                        $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 2) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($z == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 0 && $chunkZ % 20 == 2) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 0 or $z == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);
            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 2) {
                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($z == 15 and $x == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {
                            $chunk->setBlock($x, 99, $z, $block);
                            // $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }
                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            }
        }
    }


    public function populateChunk(int $chunkX, int $chunkZ): void
    {
    }

    public function getSettings(): array
    {
        return [];
    }

    public function getName(): string
    {
        return Kits::SPLEEF;
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(0, 100, 0);
    }
}