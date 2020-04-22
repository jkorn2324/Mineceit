<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-17
 * Time: 17:29
 */

declare(strict_types=1);

namespace mineceit\parties\events;


use mineceit\player\MineceitPlayer;

abstract class PartyEvent
{

    public const EVENT_TOURNAMENT = 'event.tournament';

    public const EVENT_PARTY_VS_PARTY = 'event.party-vs-party';

    public const EVENT_DUEL = 'event.duel';

    /* @var string */
    protected $eventType;

    public function __construct(string $type)
    {
        $this->eventType = $type;
    }

    /**
     * @return string
     * Specifies which party event it is.
     */
    public function getEventType() : string {
        return $this->eventType;
    }

    /**
     * Updates the party event each tick.
     */
    abstract public function update() : void;

    /**
     * @param MineceitPlayer $player
     */
    abstract public function removeFromEvent(MineceitPlayer $player) : void;
}