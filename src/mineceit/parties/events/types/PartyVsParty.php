<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-17
 * Time: 18:49
 */

declare(strict_types=1);

namespace mineceit\parties\events\types;


use mineceit\parties\events\PartyEvent;
use mineceit\player\MineceitPlayer;

class PartyVsParty extends PartyEvent
{

    private $currentTick;

    public function __construct()
    {
        parent::__construct(self::EVENT_PARTY_VS_PARTY);
        $this->currentTick = 0;
    }

    /**
     * Updates the party event each tick.
     */
    public function update(): void
    {
        // TODO: Implement update() method.
    }

    /**
     * @param MineceitPlayer $player
     */
    public function removeFromEvent(MineceitPlayer $player): void
    {
        // TODO: Implement removeFromEvent() method.
    }
}