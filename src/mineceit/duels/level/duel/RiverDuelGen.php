<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-15
 * Time: 18:06
 */

declare(strict_types=1);

namespace mineceit\duels\level\duel;

use mineceit\MineceitUtil;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class RiverDuelGen extends Generator
{

    protected $level;
    protected $random;
    protected $count;


    // 3 chunks x 3 chunks

    /**
     * @param array $settings
     *
     * @throws InvalidGeneratorOptionsException
     */
    public function __construct(array $settings = []) {}

    public function init(ChunkManager $level, Random $rand) : void
    {
        $this->level = $level;
        $this->random = $rand;
        $this->count = 0;
    }

    public function generateChunk(int $chunkX, int $chunkZ): void
    {
        if ($this->level instanceof ChunkManager) {

            $chunk = $this->level->getChunk($chunkX, $chunkZ);
            $chunk->setGenerated();

            if ($chunkX % 20 == 0 && $chunkZ % 20 == 0) {

                $rose = [8, 13];

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($x == 0 or $z == 0) {

                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }

                        } else {

                            $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                            $meta = [5, 5, 13, 13, 0, 0];
                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {
                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            // Generates Hill
                            $doBlocks100 = false; $doBlocks101 = false;
                            if($z == 1 || $z == 2) {
                                $doBlocks100 = $x < 4;
                                $doBlocks101 = $x < 3;
                            } elseif ($z == 3 || $z == 4) {
                                $doBlocks100 = $x < 3;
                                $doBlocks101 = $x < 2;
                            } elseif ($z == 5) {
                                $doBlocks100 = $x == 1;
                            }

                            if($doBlocks100) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, 100, $z, $blocks[$rand], $meta[$rand]);
                            }

                            if($doBlocks101) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, 101, $z, $blocks[$rand], $meta[$rand]);
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                // Sets the rose.
                $chunk->setBlock($rose[0], 100, $rose[1], BlockIds::RED_FLOWER);

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 0) {

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($z == 0) {

                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }

                        } else {

                            $doBlocks = false;

                            if ($z >= 1 && $z <= 3) {
                                $doBlocks = $x >= 5 && $x <= 10;
                            } elseif ($z >= 4 && $z <= 6) {
                                $doBlocks = $x >= 5 && $x <= 11;
                            } elseif ($z >= 7 && $z <= 12) {
                                $doBlocks = $x >= 6 && $x <= 12;
                            } elseif ($z >= 13 && $z < 15) {
                                $doBlocks = $x >= 7 && $x <= 12;
                            } elseif ($z == 15) {
                                $doBlocks = $x >= 7 && $x <= 13;
                            }

                            for($y = 97; $y <= 99; $y++) {

                                if($y % 2 == 1) {

                                    $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                                    $meta = [5, 5, 13, 13, 0, 0];

                                    if ($y == 99 && $doBlocks) {
                                        $blocks = [BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS];
                                        $meta = [3, 3, 9, 9, 11, 11];
                                    }

                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);

                                } else {

                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 2 && $chunkZ % 20 == 0) {

                $rose1 = [6, 4]; $rose2 = [2, 12]; $rose3 = [10, 14];

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($x == 15 or $z == 0) {

                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {

                            $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                            $meta = [5, 5, 13, 13, 0, 0];
                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {
                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            $doBlocks = false;

                            if($z === 1) {
                                $doBlocks = $x >= 12;
                            } elseif($z === 2) {
                                $doBlocks = $x >= 14;
                            }

                            if($doBlocks) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                // Sets the roses
                $chunk->setBlock($rose1[0], 100, $rose1[1], BlockIds::RED_FLOWER);
                $chunk->setBlock($rose2[0], 100, $rose2[1], BlockIds::RED_FLOWER);
                $chunk->setBlock($rose3[0], 100, $rose3[1], BlockIds::RED_FLOWER);

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 2 && $chunkZ % 20 == 1) {

                $rose1 = [3, 6]; $rose2 = [12, 11];

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($x == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {

                            $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                            $meta = [5, 5, 13, 13, 0, 0];
                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {
                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                // Sets the roses
                $chunk->setBlock($rose1[0], 100, $rose1[1], BlockIds::RED_FLOWER);
                $chunk->setBlock($rose2[0], 100, $rose2[1], BlockIds::RED_FLOWER);

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 2 && $chunkZ % 20 == 2) {

                $rose1 = [5, 5]; $rose2 = [6, 12];

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($x == 15 or $z == 15) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {

                            $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                            $meta = [5, 5, 13, 13, 0, 0];
                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {
                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            // Makes the hill.
                            $doBlocks100 = false; $doBlocks101 = false;

                            if($z == 11) {
                                $doBlocks100 = $x == 14;
                            } elseif ($z == 12 || $z == 13) {
                                $doBlocks100 = $x >= 12;
                                $doBlocks101 = $z == 13 && $x == 14;
                            } elseif ($z == 14) {
                                $doBlocks100 = $x >= 11;
                                $doBlocks101 = $x >= 13;
                            }

                            if($doBlocks100) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, 100, $z, $blocks[$rand], $meta[$rand]);
                            }

                            if($doBlocks101) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, 101, $z, $blocks[$rand], $meta[$rand]);
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                $chunk->setBlock($rose1[0], 100, $rose1[1], BlockIds::RED_FLOWER);
                $chunk->setBlock($rose2[0], 100, $rose2[1], BlockIds::RED_FLOWER);

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 0 && $chunkZ % 20 == 1) {

                $rose1 = [13, 3]; $rose2 = [8, 11];

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($x == 0) {

                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {

                            $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                            $meta = [5, 5, 13, 13, 0, 0];
                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {
                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                // Sets the roses
                $chunk->setBlock($rose1[0], 100, $rose1[1], BlockIds::RED_FLOWER);
                $chunk->setBlock($rose2[0], 100, $rose2[1], BlockIds::RED_FLOWER);

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 1) {

                $rose = [15, 14];

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        $doRiverBlocks = false;
                        $doStairBridgeBlocks = false; $doPlankBridgeBlocks = false;
                        $metaData = 0;
                        // Stairs rotation = 0 = east, 1 = west, 2 = south, 3 = north
                        // north = z - 1, south = z + 1, east = x + 1, west = x - 1

                        if ($z >= 0 && $z < 2) {
                            $doRiverBlocks = $x >= 7 && $x <= 13;
                        } elseif ($z == 2) {
                            $doRiverBlocks = $x >= 6 && $x <= 13;
                        } elseif ($z >= 3 && $z <= 4) {
                            $doRiverBlocks = $x >= 6 && $x <= 12;
                        } elseif ($z == 5) {
                            $doRiverBlocks = $x >= 7 && $x <= 12;
                            $doStairBridgeBlocks = $x >= 3 && $x <= 6;
                            if($doStairBridgeBlocks) {
                                $metaData = 3;
                                if($x == 3) {
                                    $metaData = 1;
                                } elseif ($x == 6) {
                                    $metaData = 0;
                                }
                            }
                        } elseif ($z == 6) {
                            $doPlankBridgeBlocks = $x >= 3 && $x <= 6;
                            $doStairBridgeBlocks = $x >= 7 && $x <= 9;
                            $doRiverBlocks = $x >= 10 && $x <= 12;
                            if($doPlankBridgeBlocks) {
                                $metaData = 3; // Sets the wood to jungle.
                            } elseif ($doStairBridgeBlocks) {
                                $metaData = 3;
                                if($x == 9) {
                                    $metaData = 0;
                                }
                            }
                        } elseif ($z == 7) {
                            $doPlankBridgeBlocks = $x >= 3 && $x <= 9;
                            $doStairBridgeBlocks = $x >= 10 && $x <= 14;
                            if($doPlankBridgeBlocks) {
                                if($x >= 3 && $x <= 6) {
                                    $metaData = 0;
                                } else {
                                    $metaData = 3;
                                }
                            } elseif ($doStairBridgeBlocks) {
                                $metaData = 3;
                                if($x == 14) {
                                    $metaData = 0;
                                }
                            }
                        } elseif ($z == 8) {
                            $doPlankBridgeBlocks = $x >= 3 && $x <= 14;
                            if($doPlankBridgeBlocks) {
                                if($x >= 7 && $x <= 9) {
                                    $metaData = 0;
                                } else {
                                    $metaData = 3;
                                }
                            }
                        } elseif ($z == 9) {
                            $doStairBridgeBlocks = $x >= 3 && $x <= 6;
                            $doPlankBridgeBlocks = $x >= 7 && $x <= 14;
                            if ($doPlankBridgeBlocks) {
                                if($x >= 7 && $x <= 9) {
                                    $metaData = 3;
                                } else {
                                    $metaData = 0;
                                }
                            } elseif($doStairBridgeBlocks) {
                                if($x == 3) {
                                    $metaData = 1;
                                } else {
                                    $metaData = 2;
                                }
                            }
                        } elseif ($z == 10) {
                            $doRiverBlocks = $x >= 5 && $x <= 6;
                            $doStairBridgeBlocks = $x >= 7 && $x <= 9;
                            $doPlankBridgeBlocks = $x >= 10 && $x <= 14;
                            if($doStairBridgeBlocks) {
                                if($x == 7) {
                                    $metaData = 1;
                                } else {
                                    $metaData = 2;
                                }
                            } elseif ($doPlankBridgeBlocks) {
                                $metaData = 3;
                            }
                        } elseif ($z == 11) {
                            $doRiverBlocks = $x >= 5 && $x <= 9;
                            $doStairBridgeBlocks = $x >= 10 && $x <= 14;
                            if($doStairBridgeBlocks) {
                                if($x == 10) {
                                    $metaData = 1;
                                } elseif ($x == 14) {
                                    $metaData = 0;
                                } else {
                                    $metaData = 2;
                                }
                            }
                        } elseif ($z == 12 || $z == 13) {
                            $doRiverBlocks = $x >= 5 && $x <= 11;
                        } elseif ($z == 14) {
                            $doRiverBlocks = $x >= 4 && $x <= 11;
                        } elseif ($z == 15) {
                            $doRiverBlocks = $x >= 4 && $x <= 10;
                        }

                        for($y = 97; $y <= 99; $y++) {

                            if($y % 2 == 1) {

                                $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                                $meta = [5, 5, 13, 13, 0, 0];

                                if($y == 99) {
                                    if($doRiverBlocks) {
                                        $blocks = [BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS];
                                        $meta = [3, 3, 9, 9, 11, 11];
                                    } elseif ($doStairBridgeBlocks) {
                                        $blocks = [BlockIds::SPRUCE_STAIRS];
                                        $meta = [$metaData];
                                    } elseif ($doPlankBridgeBlocks) {
                                        $blocks = [BlockIds::PLANKS];
                                        $meta = [$metaData];
                                    }
                                }

                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);

                            } else {
                                $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                            }
                        }

                        $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                    }
                }

                // Sets the rose.
                $chunk->setBlock($rose[0], 100, $rose[1], BlockIds::RED_FLOWER);

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } elseif ($chunkX % 20 == 1 && $chunkZ % 20 == 2) {

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($z == 15) {

                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }

                        } else {

                            if($z == 0 || ($z >= 3 && $z <= 4)) {
                                $doRiverBlocks = $x >= 4 && $x <= 10;
                            } elseif ($z >= 1 && $z <= 2) {
                                $doRiverBlocks = $x >= 3 && $x <= 10;
                            } elseif ($z == 5) {
                                $doRiverBlocks = $x >= 4 && $x <= 11;
                            } elseif ($z >= 6 && $z <= 8) {
                                $doRiverBlocks = $x >= 5 && $x <= 11;
                            } elseif ($z >= 9 && $z <= 12) {
                                $doRiverBlocks = $x >= 5 && $x <= 12;
                            } else {
                                $doRiverBlocks = $x >= 6 && $x <= 12;
                            }

                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {

                                    $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                                    $meta = [5, 5, 13, 13, 0, 0];

                                    if($y == 99 && $doRiverBlocks) {
                                        $blocks = [BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS, BlockIds::STAINED_GLASS];
                                        $meta = [3, 3, 9, 9, 11, 11];
                                    }

                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);

                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
                        }
                    }
                }

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 0 && $chunkZ % 20 == 2) {

                for ($z = 0; $z < 16; ++$z) {

                    for ($x = 0; $x < 16; ++$x) {

                        if ($x == 0 or $z == 15) {

                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }

                        } else {

                            $blocks = [BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::TERRACOTTA, BlockIds::GRASS, BlockIds::GRASS];
                            $meta = [5, 5, 13, 13, 0, 0];
                            for($y = 97; $y <= 99; $y++) {
                                if($y % 2 == 1) {
                                    $rand = mt_rand(0, count($blocks) - 1);
                                    $chunk->setBlock($x, $y, $z, $blocks[$rand], $meta[$rand]);
                                } else {
                                    $chunk->setBlock($x, $y, $z, BlockIds::LAPIS_BLOCK);
                                }
                            }

                            // Does the hills
                            $doBlocks100 = false; $doBlocks101 = false;

                            if($z == 11) {
                                $doBlocks100 = $x <= 1;
                            } elseif ($z == 12) {
                                $doBlocks100 = $x <= 2;
                            } elseif ($z == 13) {
                                $doBlocks100 = $x <= 3;
                                $doBlocks101 = $x <= 1;
                            } elseif ($z == 14) {
                                $doBlocks100 = $x <= 4;
                                $doBlocks101 = $x <= 2;
                            }

                            if($doBlocks100) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, 100, $z, $blocks[$rand], $meta[$rand]);
                            }

                            if($doBlocks101) {
                                $rand = mt_rand(0, count($blocks) - 1);
                                $chunk->setBlock($x, 101, $z, $blocks[$rand], $meta[$rand]);
                            }

                            $chunk->setBlock($x, 96, $z, BlockIds::BEDROCK);
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
        return MineceitUtil::RIVER_DUEL_GEN;
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(0, 100, 0);
    }
}