<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-03
 * Time: 20:16
 */

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;

use mineceit\game\leaderboard\Leaderboards;
use mineceit\MineceitCore;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

abstract class LeaderboardHologram
{

    /* @var Vector3 */
    protected $vec3;

    /* @var Level */
    protected $level;

    /* @var string[]|array */
    protected $leaderboardKeys;

    /* @var FloatingTextParticle|null */
    protected $floatingText;

    /* @var int */
    protected $currentKey;

    /** @var Leaderboards */
    protected $leaderboards;

    public function __construct(Vector3 $vec3, Level $level, Leaderboards $leaderboards = null)
    {
        $this->level = $level;
        $this->vec3 = $vec3;
        $this->leaderboards = $leaderboards ?? MineceitCore::getLeaderboards();
        $this->leaderboardKeys = [];
        $this->floatingText = null;
        $this->currentKey = 0;
    }

    /**
     *
     * @param bool $updateKey
     *
     * Places the hologram down into the world.
     */
    abstract protected function placeFloatingHologram(bool $updateKey = true) : void;


    /**
     * Updates the floating hologram.
     */
    public function updateHologram() : void {
        $this->placeFloatingHologram();
    }

    /**
     * @param Vector3 $position
     * @param Level $level
     *
     * Moves the hologram from one spot to another.
     */
    public function moveHologram(Vector3 $position, Level $level) : void {

        // TODO TEST

        $originalLevel = $this->level;

        if($level === null) {
            return;
        }

        $this->level = $level;
        $this->vec3 = $position;

        if($this->floatingText !== null) {
            $this->floatingText->setInvisible(true);
            $pkts = $this->floatingText->encode();
            if(!is_array($pkts)) {
                $pkts = [$pkts];
            }
            if(count($pkts) > 0) {
                foreach($pkts as $pkt) {
                    $originalLevel->broadcastPacketToViewers($this->vec3, $pkt);
                }
            }
        }

        $this->placeFloatingHologram(false);
    }
}