<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-15
 * Time: 18:18
 */

declare(strict_types=1);

namespace mineceit\parties;


use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\PartyEventManager;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PartyManager
{

    /* @var MineceitParty[]|array */
    private $parties;

    /* @var Server */
    private $server;

    /* @var MineceitCore */
    private $core;

    /* @var PartyEventManager */
    private $eventManager;

    public function __construct(MineceitCore $core)
    {
        $this->parties = [];
        $this->eventManager = new PartyEventManager($core);
        $this->server = $core->getServer();
        $this->core = $core;
    }

    /**
     * @param MineceitPlayer $owner
     * @param string $name
     * @param int $maxPlayers
     * @param bool $open
     */
    public function createParty(MineceitPlayer $owner, string $name, int $maxPlayers, bool $open = true) : void {

        $name = trim($name);

        $ownerName = $owner->getName();

        $local = strtolower($ownerName) . ":$name";

        if(!isset($this->parties[$local])) {

            $this->parties[$local] = new MineceitParty($owner, $name, $maxPlayers, $open);

            $lang = $owner->getLanguage();

            $owner->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'You have successfully created a new party.');

            $itemHandler = MineceitCore::getItemHandler();

            $itemHandler->spawnPartyItems($owner);
            // TODO SEND MESSAGE THAT OWNER CREATED A NEW PARTY
        }
    }

    /**
     * @param string $name
     * @return MineceitParty|null
     */
    public function getPartyFromName(string $name) {

        $name = strtolower($name);

        $keys = array_keys($this->parties);

        $result = null;

        foreach($keys as $key) {
            $partyName = strtolower(explode(':', $key)[1]);
            if($partyName === $name) {
                $result = $this->parties[$key];
                break;
            }
        }

        return $result;
    }

    /**
     * @param MineceitPlayer $player
     * @return MineceitParty|null
     */
    public function getPartyFromPlayer(MineceitPlayer $player) {

        $result = null;

        foreach($this->parties as $party) {
            if($party->isPlayer($player)) {
                $result = $party;
                break;
            }
        }

        return $result;
    }

    /**
     * @return array|MineceitParty[]
     */
    public function getParties() : array {
        return $this->parties;
    }

    /**
     * @return PartyEventManager
     */
    public function getEventManager() : PartyEventManager {
        return $this->eventManager;
    }

    /**
     * @param MineceitParty $party
     */
    public function endParty(MineceitParty $party) : void
    {
        $local = $party->getLocalName();

        if(isset($this->parties[$local]))
            unset($this->parties[$local]);
    }

    /**
     * @param string $oldLocal
     * @param string $newLocal
     *
     * Only used for promoting a new owner.
     */
    public function swapLocal(string $oldLocal, string $newLocal)
    {
        if(isset($this->parties[$oldLocal])) {
            $party = $this->parties[$oldLocal];
            unset($this->parties[$oldLocal]);
            $this->parties[$newLocal] = $party;
        }
    }
}