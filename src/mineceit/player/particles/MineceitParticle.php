<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-19
 * Time: 16:05
 */

declare(strict_types=1);

namespace mineceit\player\particles;


use pocketmine\level\particle\Particle;
use pocketmine\math\Vector3;

class MineceitParticle
{

    /** @var Particle */
    private $class;

    /** @var string */
    private $name;

    /** @var string */
    private $local;


    public function __construct(string $local, string $name, string $particleClass)
    {
        $this->local = $local;
        $this->name = $name;
        $this->class = $particleClass;
    }

    /**
     *
     * @param Vector3 $pos
     *
     * @return Particle|null
     *
     * The particle.
     */
    public function getParticle(Vector3 $pos) {
        var_dump($this->class);
        return null;
    }


    /**
     * @return string
     *
     * The localized name of the particle.
     */
    public function getLocalName() : string {
        return $this->local;
    }


    /**
     * @return string
     *
     * Gets the name of the particle.
     */
    public function getName() : string {
        return $this->name;
    }
}