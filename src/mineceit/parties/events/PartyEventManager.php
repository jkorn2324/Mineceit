<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-15
 * Time: 18:41
 */

declare(strict_types=1);

namespace mineceit\parties\events;


use mineceit\MineceitCore;
use mineceit\parties\MineceitParty;
use pocketmine\Server;

class PartyEventManager
{


    /* @var PartyEvent[]|array */
    private $partyEvents;

    /* @var Server */
    private $server;

    /* @var MineceitCore */
    private $core;

    public function __construct(MineceitCore $core)
    {
        $this->partyEvents = [];
        $this->core = $core;
        $this->server = $core->getServer();
    }

    /**
     * @param MineceitParty $party
     * @param string $type
     */
    public function startPartyEvent(MineceitParty $party, string $type) : void {
        // TODO
    }

    /**
     * @param MineceitParty $party
     * @return PartyEvent|null
     */
    public function getPartyEvent(MineceitParty $party) {

        $local = $party->getLocalName();

        $event = null;

        if(isset($this->partyEvents[$local]))
            $event = $this->partyEvents[$local];

        return $event;
    }


    /**
     * Updates the party events.
     */
    public function updateEvents() : void {

        foreach($this->partyEvents as $event) {
            $event->update();
        }
    }
}