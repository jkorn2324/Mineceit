<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-22
 * Time: 21:06
 */

declare(strict_types=1);

namespace mineceit\player\info\skins;


use pocketmine\network\mcpe\protocol\types\SkinData;

class SkinInfo
{
    private const MIN_TRANSPARENCY_VALUE = 40;

    private const PARTS_THAT_DONT_COUNT = [
        "hat",
        "rightPants",
        "leftPants",
        "leftSleeve",
        "rightSleeve",
        "jacket"
    ];

    /** @var string */
    private $skinData;

    /** @var array */
    private $cubes;

    /** @var int */
    private $width;

    /** @var int */
    private $height;

    public function __construct(SkinData $data)
    {
        $this->width = $data->getSkinImage()->getWidth();
        $this->height = $data->getSkinImage()->getHeight();

        $this->skinData = $data->getSkinImage()->getData();

        $this->cubes = [];

        $decoded = json_decode($data->getGeometryData(), true);

        if(isset($decoded["minecraft:geometry"], $decoded["minecraft:geometry"][2]["bones"])) {

            $geometry = $decoded["minecraft:geometry"];
            $bones = $geometry[2]["bones"];

            foreach($bones as $value) {

                if(isset($value["name"], $value["cubes"])) {

                    $data = $value["cubes"][0];

                    if(isset($data['size'], $data['uv'])) {

                        $cube = [];

                        $cube["x"] = $data['size'][0];
                        $cube["y"] = $data['size'][1];
                        $cube["z"] = $data['size'][2];
                        $cube['uvX'] = $data['uv'][0];
                        $cube['uvY'] = $data['uv'][1];

                        $this->cubes[$value["name"]] = $cube;
                    }
                }
            }
        }
    }


    /**
     * @return float
     *
     * Gets the scale used for retrieving the bounds.
     */
    private function getScale() : float {

        if($this->width === 128 and $this->height === 128) {
            return 2.0;
        }

        return 1.0;
    }


    /**
     * @return array
     *
     * Gets the skin's bounds.
     */
    private function getSkinBounds() {

        $bounds = [];

        $scale = $this->getScale();

        foreach($this->cubes as $name => $cube) {

            if(in_array($name, self::PARTS_THAT_DONT_COUNT)) {
                continue;
            }

            $x = (int)($scale * $cube['x']);
            $y = (int)($scale * $cube['y']);
            $z = (int)($scale * $cube['z']);

            $uvX = (int)($scale * $cube['uvX']);
            $uvY = (int)($scale * $cube['uvY']);

            $bounds[] = ['min' => ['x' => $uvX + $z, 'y' => $uvY], 'max' => ['x' => $uvX + $z + (2 * $x) - 1, 'y' => $uvY + $z - 1]];
            $bounds[] = ['min' => ['x' => $uvX, 'y' => $uvY + $z], 'max' => ['x' => $uvX + (2 * ($z + $x)) - 1, 'y' => $uvY + $z + $y - 1]];
        }

        return $bounds;
    }


    /**
     * @return bool
     *
     * Determines whether the skins are valid. -> invisible or not
     */
    public function isValidSkin() : bool {

        $transparentPixels = $totalPixels = 0;

        $bounds = $this->getSkinBounds();

        foreach($bounds as $boundKey => $bound) {

            $maxY = $bound['max']['y'];
            $maxX = $bound['max']['x'];
            $minY = $bound['min']['y'];
            $minX = $bound['min']['x'];

            if($maxX > $this->width  || $maxY > $this->height) {
                continue;
            }

            for($y = $minY; $y <= $maxY; $y++) {
                for($x = $minX; $x <= $maxX; $x++) {
                    $key = (($this->width * $y) + $x) * 4;
                    $a = ord($this->skinData[$key + 3]);
                    if($a < 127) {
                        ++$transparentPixels;
                    }
                    ++$totalPixels;
                }
            }
        }

        $percentage = (int)(round($transparentPixels * 100 / max(1, $totalPixels)));

        return $percentage < self::MIN_TRANSPARENCY_VALUE;
    }
}