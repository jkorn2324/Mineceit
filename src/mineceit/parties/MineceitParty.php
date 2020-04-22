<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-15
 * Time: 18:20
 */

declare(strict_types=1);

namespace mineceit\parties;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class MineceitParty
{

    public const MAX_PLAYERS = 30;

    /* @var int */
    private $maxPlayers;

    /* @var MineceitPlayer[]|array */
    private $players;

    /* @var MineceitPlayer */
    private $owner;

    /* @var string */
    private $name;

    /* @var bool */
    private $open;

    /* @var string[]|array */
    private $blacklisted;

    public function __construct(MineceitPlayer $owner, string $name, int $maxPlayers, bool $open = true)
    {
        $this->owner = $owner;
        $this->name = $name;
        $this->maxPlayers = $maxPlayers;
        $local = strtolower($owner->getName());
        $this->players = [$local => $owner];
        $this->open = $open;
        $this->blacklisted = [];
    }

    /**
     * @param MineceitPlayer $player
     */
    public function addPlayer(MineceitPlayer $player) : void {

        $name = $player->getName();

        $local = strtolower($name);

        if(!isset($this->players[$local])) {

            $this->players[$local] = $player;
            $itemHandler = MineceitCore::getItemHandler();
            $itemHandler->spawnPartyItems($player);

            $duelHandler = MineceitCore::getDuelHandler();
            if($duelHandler->isInQueue($player))
                $duelHandler->removeFromQueue($player, false);

            // TODO MESSAGE THAT A PLAYER HAS JOINED.
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param string $reason
     * @param bool $blacklist
     */
    public function removePlayer(MineceitPlayer $player, string $reason = '', bool $blacklist = false) : void {

        $name = $player->getName();

        $kicked = $reason !== '';

        $local = strtolower($name);

        if(isset($this->players[$local])) {

            $itemHandler = MineceitCore::getItemHandler();

            if($this->isOwner($player)) {

                $partyManager = MineceitCore::getPartyManager();

                $partyManager->endParty($this);

                // TODO IF IN AN EVENT, DO END THE EVENT

                foreach($this->players as $p) {
                    if($p->isOnline()) {
                        $inHub = $p->isInHub();
                        if(!$inHub) $p->reset(false, true);
                        $itemHandler->spawnHubItems($p, $inHub);
                    }
                }

                // TODO BROADCAST MESSAGE THAT PARTY HAS ENDED

                return;
            }

            unset($this->players[$local]);

            $inHub = $player->isInHub();

            if(!$inHub)
                $player->reset(false, true);

            $itemHandler->spawnHubItems($player, $inHub);

            if($kicked and $blacklist)
                $this->blacklisted[] = $player->getName();

            // TODO BROADCAST MESSAGE THAT PLAYER LEFT OR IS KICKED WITH REASON
        }
    }

    /**
     * @param MineceitPlayer|string $player
     * @return bool
     */
    public function isPlayer($player) : bool {
        $local = $player instanceof MineceitPlayer ? strtolower($player->getName()) : strtolower($player);
        if(isset($this->players[$local])) {
            return true;
        }

        foreach($this->players as $player) {
            $displayName = strtolower($player->getDisplayName());
            if($displayName === $local) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param MineceitPlayer $player
     * @return bool
     */
    public function isOwner(MineceitPlayer $player) : bool {
        return $this->owner->equalsPlayer($player);
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @param bool $int
     * @return array|int|MineceitPlayer[]
     */
    public function getPlayers(bool $int = false) {
        return ($int) ? count($this->players) : $this->players;
    }

    /**
     * @return int
     */
    public function getMaxPlayers() : int {
        return $this->maxPlayers;
    }

    /**
     * @return bool
     */
    public function isOpen() : bool {
        return $this->open;
    }

    /**
     * @param bool $open
     */
    public function setOpen(bool $open = true) : void {
        $this->open = $open;
    }

    /**
     * @param int $players
     */
    public function setMaxPlayers(int $players) : void {
        $this->maxPlayers = $players;
    }

    /**
     * @return string
     */
    public function getLocalName() : string {
        $lower = strtolower($this->owner->getName()) . ':' . $this->getName();
        return $lower;
    }

    /**
     * @param MineceitPlayer $player
     */
    public function promoteToOwner(MineceitPlayer $player) : void {

        $oldLocal = $this->getLocalName();

        $oldOwner = $this->owner;

        $this->owner = $player;

        // TODO BROADCAST MESSAGE
        $partyManager = MineceitCore::getPartyManager();

        $newLocal = $this->getLocalName();

        $itemHandler = MineceitCore::getItemHandler();

        $itemHandler->spawnPartyItems($oldOwner);
        $itemHandler->spawnPartyItems($this->owner);

        $partyManager->swapLocal($oldLocal, $newLocal);
    }

    /**
     * @return MineceitPlayer
     */
    public function getOwner() : MineceitPlayer {
        return $this->owner;
    }

    /**
     * @param string $name
     * @return MineceitPlayer|null
     */
    public function getPlayer(string $name) {
        $local = strtolower($name);
        if(isset($this->players[$local])) {
            return $this->players[$local];
        }

        foreach($this->players as $player) {
            $displayName = strtolower($player->getDisplayName());
            if($displayName === $name) {
                return $player;
            }
        }
        return null;
    }


    /**
     * @param MineceitPlayer|string $player
     * @return bool
     */
    public function isBlackListed($player) : bool {
        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;
        return in_array($name, $this->blacklisted);
    }

    /**
     * @param MineceitPlayer|string $player
     */
    public function addToBlacklist($player) : void {
        $name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
        $this->blacklisted[] = $name;
        // TODO BROADCAST MESSAGE
    }

    /**
     * @param MineceitPlayer|string $player
     */
    public function removeFromBlacklist($player) : void {
        $name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
        if(in_array($name, $this->blacklisted)) {
            $index = array_search($name, $this->blacklisted);
            unset($this->blacklisted[$index]);
            $this->blacklisted = array_values($this->blacklisted);
            // TODO BROADCAST MESSAGE
        }
    }

    /**
     * @return array|string[]
     */
    public function getBlacklisted() : array {
        return $this->blacklisted;
    }

    /**
     * @return string
     */
    public function getPrefix() : string {
        return TextFormat::BOLD . TextFormat::DARK_GRAY . '[' . TextFormat::GREEN . $this->name . TextFormat::DARK_GRAY . ']' . TextFormat::RESET;
    }
}