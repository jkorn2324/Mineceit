<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-18
 * Time: 15:29
 */

declare(strict_types=1);

namespace mineceit\parties\events\types;


use mineceit\kits\AbstractKit;
use mineceit\MineceitCore;
use mineceit\parties\events\PartyEvent;
use mineceit\parties\events\types\match\data\MineceitTeam;
use mineceit\parties\MineceitParty;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;

class PartyDuel extends PartyEvent
{

    /* @var int */
    private $currentTick;

    /* @var int */
    private $playersPerTeam;

    /* @var MineceitParty */
    private $party;

    /* @var MineceitPlayer[]|MineceitTeam[] */
    private $participants;

    /* @var string */
    private $queue;

    /* @var AbstractKit */
    private $kit;

    /* @var int */
    private $countdownSeconds;

    /* @var int */
    private $durationSeconds;

    /* @var bool */
    private $ended;

    /* @var bool */
    private $started;

    /* @var Level */
    private $level;

    public function __construct(MineceitParty $party, string $queue, int $playersPerTeam, Level $level)
    {
        parent::__construct(self::EVENT_DUEL);
        $this->currentTick = 0;
        $this->playersPerTeam = $playersPerTeam;
        $this->party = $party;
        $this->participants = [];

        $this->countdownSeconds = 10;

        $this->level = $level;

        $this->durationSeconds = 0;

        $this->started = false;
        $this->ended = false;

        $this->queue = $queue;

        $this->kit = MineceitCore::getKits()->getKit($queue);

        $players = $party->getPlayers();

        $count = 0;

        $size = count($players) - 1;

        if ($playersPerTeam > 1) {

            $team = new MineceitTeam();

            foreach($players as $p) {

                if($count % $this->playersPerTeam === 0 or $count === $size) {

                    $this->participants[] = $team;

                    $colors = [];

                    foreach ($this->participants as $participant) {
                        if ($participant instanceof MineceitTeam)
                            $colors[] = $participant->getTeamColor();
                    }

                    if($count < $size) $team = new MineceitTeam($colors);
                }

                $team->addToTeam($p);

                $count++;
            }

        } else $this->participants = $players;
    }

    private function setInDuel() : void {
        // TODO
    }

    /**
     * Updates the party event each tick.
     */
    public function update(): void
    {
        $this->currentTick++;
        // TODO
    }

    /**
     * @param MineceitPlayer $player
     */
    public function removeFromEvent(MineceitPlayer $player): void
    {
        $name = $player->getName();

        $local = strtolower($name);

        if(isset($this->participants[$local]) and $this->participants[$local] instanceof MineceitPlayer) {
            unset($this->participants[$local]);
            return;
        }

        foreach($this->participants as $participant) {
            if($participant instanceof MineceitTeam and $participant->isInTeam($name))
                $participant->removeFromTeam($name);
        }
    }
}