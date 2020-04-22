<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-15
 * Time: 22:48
 */

declare(strict_types=1);

namespace mineceit\player\particles;

//TODO
use mineceit\MineceitCore;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\SmokeParticle;

class ParticleHandler
{

    public const FLAME = "flame";
    public const BUBBLES = "bubble";
    public const PORTAL = "portal";
    public const SMOKE = "smoke";
    public const CRIT = "critical";

    private $particles;

    /** @var MineceitCore */
    private $core;

    /*
     * RANKS WITH PARTICLES:
     *
     * Famous
     * Famous+
     * VIP
     * VIP+
     * YouTube
     */

    public function __construct(MineceitCore $core)
    {

        $this->core = $core;

        $this->particles = [

            self::FLAME => new MineceitParticle(self::FLAME, "Flame", FlameParticle::class),
            self::BUBBLES => new MineceitParticle(self::BUBBLES, "Bubbles", BubbleParticle::class),
            self::PORTAL => new MineceitParticle(self::PORTAL, "Portal", PortalParticle::class),
            self::SMOKE => new MineceitParticle(self::SMOKE, "Smoke", SmokeParticle::class),
            self::CRIT => new MineceitParticle(self::CRIT, "Critical", CriticalParticle::class)

        ];
    }

    /**
     * @param string $local
     * @return MineceitParticle|null
     */
    public function getParticle(string $local) {
        return $this->particles[$local] ?? null;
    }

    /**
     * @return array|MineceitParticle[]
     */
    public function getParticles() : array {
        return $this->particles;
    }
}