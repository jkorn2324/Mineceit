<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-03
 * Time: 13:46
 */

declare(strict_types=1);

namespace mineceit\events;

use mineceit\arenas\EventArena;
use mineceit\kits\Kits;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class EventManager
{

    const EVENT_TYPES = [
        Kits::SUMO => MineceitEvent::TYPE_SUMO,
        Kits::GAPPLE => MineceitEvent::TYPE_GAPPLE,
        Kits::NODEBUFF => MineceitEvent::TYPE_NODEBUFF
    ];

    /** @var MineceitEvent[]|array */
    private $events;

    /** @var MineceitCore */
    private $core;

    /** @var Server */
    private $server;

    public function __construct(MineceitCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();
        $this->events = [];
        $this->initEvents();
    }


    /**
     * Initializes the events to the event handler.
     */
    private function initEvents() : void {

        $arenas = MineceitCore::getArenas()->getEventArenas();

        foreach($arenas as $arena) {
            $this->createEvent($arena);
        }
    }

    /**
     * @param EventArena $arena
     *
     * Creates a new event from an arena.
     */
    public function createEvent(EventArena $arena) : void {

        $type = $arena->getKit()->getLocalizedName();

        if(isset(self::EVENT_TYPES[$type])) {
            $type = self::EVENT_TYPES[$type];
            $this->events[] = new MineceitEvent($type, $arena);
        }
    }

    /**
     * @param string $name
     *
     * Removes an event based on the arena name.
     */
    public function removeEventFromArena(string $name) : void {

        foreach($this->events as $key => $event) {
            if($event->getArena()->getName() === $name) {
                unset($this->events[$key]);
                return;
            }
        }
    }

    /**
     * @return array|MineceitEvent[]
     *
     * Gets the events.
     */
    public function getEvents() {
        return $this->events;
    }

    /**
     * @param int $index
     * @return MineceitEvent|null
     *
     * Gets the event based on the name.
     */
    public function getEventFromIndex(int $index) {

        if(isset($this->events[$index])) {
            return $this->events[$index];
        }

        return null;
    }

    /**
     * @param MineceitPlayer $player
     * @return MineceitEvent|null
     */
    public function getEventFromPlayer(MineceitPlayer $player) {

        foreach($this->events as $event) {
            if($event->isPlayer($player)) {
                return $event;
            }
        }

        return null;
    }
}