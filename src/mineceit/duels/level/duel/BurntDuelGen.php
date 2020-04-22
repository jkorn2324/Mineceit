<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-17
 * Time: 01:09
 */

namespace mineceit\duels\level\duel;


use mineceit\MineceitUtil;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class BurntDuelGen extends Generator
{
    protected $level;
    protected $random;
    protected $count;

    const BASE_BLOCKS = [BlockIds::DIRT, BlockIds::DIRT, BlockIds::MYCELIUM, BlockIds::PODZOL];
    const BASE_META = [0, 1, 0, 0];

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

                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        if ($x == 0 or $z == 0) {
                            for ($y = 99; $y < 110; ++$y) {
                                $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
                            }
                        } else {

                            $buildWood = false; $buildLadders = false; $buildRails = false;
                            $ladderMeta = 0;

                            $yValues = [];
                            if($z == 5 || $z == 7) {
                                $buildWood = $x >= 5 && $x <= 7;
                                $yValues = [100];
                            } elseif ($z == 6) {
                                $buildWood = $x >= 4 && $x <= 8;
                                $yValues = [100];
                                if($x >= 5 && $x <= 7) {
                                    $yValues = [100, 101];
                                }
                            }

                            if($z == 4 || $z == 8) {
                                $buildLadders = $x == 5 || $x == 7;
                                if($z == 4) {
                                    $ladderMeta = 2;
                                } elseif ($z == 8) {
                                    $ladderMeta = 3;
                                }
                            }

                            $railsY = 101;
                            if($z >= 5 && $z <= 7) {
                                $buildRails = $x == 5 || $x == 7;
                                if($z == 6) {
                                    $railsY = 102;
                                }
                            }

                            if($buildWood) {
                                foreach ($yValues as $y) {
                                    $chunk->setBlock($x, $y, $z, BlockIds::WOOD, 5);
                                }
                            }

                            if($buildLadders) {
                                $chunk->setBlock($x, 100, $z, BlockIds::LADDER, $ladderMeta);
                            }

                            if($buildRails) {
                                $meta = 0;
                                if($z == 7) {
                                    $meta = 4;
                                } elseif ($z == 5) {
                                    $meta = 5;
                                }
                                $chunk->setBlock($x, $railsY, $z, BlockIds::RAIL, $meta);
                            }

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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

                            $buildSlab = false;
                            $buildPlank = false;
                            $height = 0;

                            if($z == 1) {
                                $buildSlab = $x >= 10;
                                if($x == 11) {
                                    $height = 1;
                                } elseif ($x == 12) {
                                    $height = 3;
                                } elseif ($x >= 13) {
                                    $height = 8;
                                }
                            } elseif ($z == 2) {
                                $buildPlank = $x == 11 || $x == 13;
                                $buildSlab = $x >= 9 && !$buildPlank;
                                if($x == 12) {
                                    $height = 2;
                                } elseif ($x == 13) {
                                    $height = 6;
                                } elseif ($x == 14) {
                                    $height = 8;
                                }
                            } elseif ($z == 3) {
                                $buildPlank = $x >= 13;
                                $buildSlab = $x >= 10 && !$buildPlank;
                                if($x == 12) {
                                    $height = 1;
                                } elseif ($x == 13) {
                                    $height = 2;
                                } elseif ($x == 14) {
                                    $height = 4;
                                }
                            } elseif ($z == 4) {
                                $buildPlank = $x == 13;
                                $buildSlab = $x == 10 || ($x >= 12 && !$buildPlank);
                                if($x == 14) {
                                    $height = 2;
                                }
                            } elseif ($z == 5) {
                                $buildSlab = $x == 9 || ($x >= 13);
                            } elseif ($z == 6) {
                                $buildSlab = $x == 14;
                            }

                            if($buildSlab || $buildPlank) {
                                for($y = 100; $y < 100 + $height; $y++) {
                                    $chunk->setBlock($x, $y, $z, BlockIds::WOOD, 1);
                                }
                                if($buildSlab) {
                                    $chunk->setBlock($x, $y, $z, BlockIds::WOODEN_SLAB, 5);
                                } elseif ($buildPlank) {
                                    $chunk->setBlock($x, $y, $z, BlockIds::WOODEN_PLANKS, 5);
                                }
                            }

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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

                            // TODO TEST THIS && FINISH LOG IN OTHER CHUNK

                            $buildWood = false; $buildLadders = false; $buildRails = false;
                            $ladderMeta = 0;
                            $yValues = [100];

                            if($z <= 3) {
                                $buildWood = $x >= 8 && $x <= 10;
                                if($x == 9) {
                                    $yValues = [100, 101];
                                }
                            }

                            if($z == 1) {
                                $buildLadders = $x == 7 || $x == 11;
                                if($x == 7) {
                                    $ladderMeta = 1;
                                }
                            }

                            if($buildWood) {
                                foreach ($yValues as $y) {
                                    $chunk->setBlock($x, $y, $z, BlockIds::WOOD, 1);
                                }
                            }

                            $railsY = 101;
                            if($z == 1) {
                                $buildRails = $x >= 8 && $x <= 10;
                                if($z == 9) {
                                    $railsY = 102;
                                }
                            }

                            if($buildLadders) {
                                $chunk->setBlock($x, 100, $z, BlockIds::LADDER, $ladderMeta);
                            }

                            if($buildLadders) {
                                $meta = 1;
                                if($x == 8) {
                                    $meta = 2;
                                } elseif ($x == 10) {
                                    $meta = 3;
                                }

                                $chunk->setBlock($x, $railsY, $z, BlockIds::RAIL, $meta);
                            }

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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
                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
                            $chunk->setBlock($x, 110, $z, BlockIds::INVISIBLE_BEDROCK);
                        }
                    }
                }

                $chunk->setX($chunkX);
                $chunk->setZ($chunkZ);

            } else if ($chunkX % 20 == 1 && $chunkZ % 20 == 1) {

                for ($x = 0; $x < 16; ++$x) {
                    for ($z = 0; $z < 16; ++$z) {
                        $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                        $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                        $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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

                            // TODO ADD ANOTHER PILLAR

                            $rand = mt_rand(0, count(self::BASE_BLOCKS) - 1);
                            $chunk->setBlock($x, 99, $z, self::BASE_BLOCKS[$rand], self::BASE_META[$rand]);
                            $chunk->setBlock($x, 98, $z, BlockIds::BEDROCK);
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
        return MineceitUtil::BURNT_DUEL_GEN;
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(0, 100, 0);
    }
}