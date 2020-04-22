<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-18
 * Time: 15:27
 */

declare(strict_types=1);

namespace mineceit\parties\events\types;


use mineceit\kits\AbstractKit;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\PartyEvent;
use mineceit\parties\events\types\match\TournamentMatch;
use mineceit\parties\MineceitParty;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;

class PartyTournament extends PartyEvent
{

    /* @var MineceitPlayer[]|array */
    private $players;

    /* @var string[]|array */
    private $eliminated;

    /* @var MineceitParty */
    private $party;

    /*
     * PARTY TOURNAMENT STRUCTURE:
     *
     * BEFORE:
     *  - Players will be teleported to the outside of a tournament arena.
     *  - Delay will be 10 seconds before it starts.
     *
     * DURING:
     *  - Two players will match up at random.
     *  - Fight until one player loses.
     *  - Once they lose, they advance.
     *  - Two players out of who is left will match up at random and complete the same cycle.
     *  - Does this until there is a winner.
     *
     * END:
     *  - Everyone will be teleported back to hub.
     */

    /* @var string */
    private $queue;

    /* @var AbstractKit */
    private $kit;

    /* @var Level */
    private $level;

    /* @var TournamentMatch */
    private $currentMatch;

    /* @var int */
    private $currentTick;

    /* @var int */
    private $secondsAfterLastMatch;

    /* @var bool */
    private $ended;

    /** @var bool */
    private $started;

    /* @var MineceitPlayer|null */
    private $winner;

    public function __construct(MineceitParty $party, Level $level, string $queue)
    {

        parent::__construct(self::EVENT_TOURNAMENT);

        $this->players = [];

        $this->currentMatch = null;

        $this->ended = false;
        $this->started = false;

        $this->secondsAfterLastMatch = 0;

        $this->winner = null;

        $this->queue = $queue;
        $this->kit = MineceitCore::getKits()->getKit($queue);
        $this->party = $party;

        $this->level = $level;

        $this->currentTick = 0;
    }

    /**
     * @param MineceitPlayer $player
     */
    private function setPlayer(MineceitPlayer $player) : void {
        $local = strtolower($player->getName());
        $this->players[$local] = $player;
        // TODO TELEPORT TO SPAWN POSITION OF THE LEVEL
    }

    /**
     * Updates the party event each tick.
     */
    public function update(): void
    {

        $this->currentTick++;

        if(!$this->started) {

            //  TODO UPDATE

        } elseif (!$this->ended) {

            if ($this->currentMatch === null) {

                $playersLeft = $this->getPlayersLeft();

                $size = count($playersLeft);

                $keys = array_keys($playersLeft);

                if($this->currentTick % 20 === 0)
                    $this->secondsAfterLastMatch++;

                if ($size === 1) {

                    $index = $keys[0];

                    $this->winner = $playersLeft[$index];

                    // TODO END

                    $this->ended = true;

                } else if ($this->currentTick === MineceitUtil::secondsToTicks(10) or $this->secondsAfterLastMatch === 5) {

                    $max = count($keys) - 1;

                    $index1 = mt_rand(0, $max);

                    $index2 = mt_rand(0, $max);
                    while ($index1 === $index2)
                        $index2 = mt_rand(0, $max);

                    $player1 = $playersLeft[$keys[$index1]];

                    $player2 = $playersLeft[$keys[$index2]];

                    $this->currentMatch = new TournamentMatch($player1, $player2, $this);
                }

            } else {

                $this->currentMatch->update();

                if($this->currentMatch->canClose()) {
                    $this->currentMatch = null;
                    $this->secondsAfterLastMatch = 0;
                }
            }

        } else {

            // TODO

        }
    }

    /**
     * @return array|MineceitPlayer[]
     */
    private function getPlayersLeft() {

        $result = $this->players;

        foreach($this->eliminated as $eliminated) {
            if(isset($result[$eliminated]))
                unset($result[$eliminated]);
        }

        return $result;
    }

    /**
     * @return TournamentMatch|null
     */
    public function getCurrentMatch() {
        return $this->currentMatch;
    }

    /**
     * @param MineceitPlayer|string $player
     */
    public function setEliminated($player) : void {
        $name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
        $local = strtolower($name);
        $this->eliminated[] = $local;
    }

    /**
     * @param MineceitPlayer $player
     */
    public function removeFromEvent(MineceitPlayer $player) : void {

        $name = $player->getName();
        $local = strtolower($name);

        if($this->party->isOwner($player)) {
            // TODO END THE TOURNAMENT & THE PARTY
            return;
        }

        if($this->currentMatch !== null and $this->currentMatch->isPlayer($name)) {
            // TODO END THE MATCH AND SET AS CLOSED
        }

        if(isset($this->players[$local]))
            unset($this->players[$local]);
    }
}