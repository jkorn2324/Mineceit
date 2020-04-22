<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 18:31
 */

declare(strict_types=1);

namespace mineceit\kits;


use mineceit\arenas\FFAArena;
use mineceit\kits\types\BuildUHC;
use mineceit\kits\types\Combo;
use mineceit\kits\types\Fist;
use mineceit\kits\types\Gapple;
use mineceit\kits\types\NoDebuff;
use mineceit\kits\types\Spleef;
use mineceit\kits\types\Sumo;
use mineceit\MineceitCore;
use pocketmine\utils\Config;

class Kits
{

    public const GAPPLE = 'gapple';

    public const SUMO = 'sumo';

    public const FIST = 'fist';

    public const NODEBUFF = 'nodebuff';

    public const COMBO = 'combo';

    public const BUILDUHC = 'builduhc';

    public const SPLEEF = 'spleef';

    /* @var string */
    private $path;

    /* @var Config */
    private $config;

    /* @var AbstractKit[]|array */
    private $kits = [];

    public function __construct(MineceitCore $core)
    {
        $this->path = $core->getDataFolder() . 'kit-knockback.yml';

        $this->initConfig();
    }

    /**
     * Initializes the config file.
     */
    private function initConfig() : void {

        $this->config = new Config($this->path, Config::YAML, []);

        $this->kits = [
            self::COMBO => new Combo(),
            self::GAPPLE => new Gapple(),
            self::FIST => new Fist(),
            self::NODEBUFF => new NoDebuff(),
            self::SUMO => new Sumo(),
            self::BUILDUHC => new BuildUHC(),
            self::SPLEEF => new Spleef()
        ];

        foreach($this->kits as $key => $kit) {
            assert($kit instanceof AbstractKit);
            if(!$this->config->exists($key)) {
                $this->config->set($key, $kit->export());
                $this->config->save();
            } else {
                $kitData = $this->config->get($key);
                if(isset($kitData['xkb'], $kitData['ykb'], $kitData['speed'])) {
                    $xKb = floatval($kitData['xkb']);
                    $yKb = floatval($kitData['ykb']);
                    $speed = intval($kitData['speed']);
                    $kit = $kit->setYKB($yKb)->setXKB($xKb)->setSpeed($speed);
                    $this->kits[$key] = $kit;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return AbstractKit|null
     */
    public function getKit(string $name) {

        if(isset($this->kits[$name])) {
            return $this->kits[$name];
        } else {
            foreach($this->kits as $kit) {
                if($name === $kit->getName()) {
                    return $kit;
                }
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isKit(string $name) : bool {
        $kit = $this->getKit($name);
        return $kit !== null;
    }

    /**
     * @param bool $asString
     * @return array|string[]|AbstractKit[]
     */
    public function getKits(bool $asString = false) {
        $result = [];
        foreach($this->kits as $kit) {
            $name = $kit->getName();
            if($asString === true)
                $result[] = $name;
            else $result[] = $kit;
        }
        return $result;
    }


    /**
     * @return array|string[]
     */
    public function getKitsLocal() {
        return array_keys($this->kits);
    }
}