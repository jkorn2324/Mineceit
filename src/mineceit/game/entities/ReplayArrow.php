<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-30
 * Time: 16:33
 */

declare(strict_types=1);

namespace mineceit\game\entities;

use pocketmine\entity\projectile\Arrow;

class ReplayArrow extends Arrow
{

    /* @var bool
     * Determines whether the arrow is paused.
     */
    private $paused = false;

    public function onUpdate(int $currentTick): bool
    {
        if($this->closed or $this->paused)
            return false;

        return parent::onUpdate($currentTick);
    }

    /**
     * @param bool $paused
     */
    public function setPaused(bool $paused) : void {
        $this->paused = $paused;
    }
}