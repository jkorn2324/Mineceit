<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-18
 * Time: 15:53
 */

declare(strict_types=1);

namespace mineceit\parties\events\types\match;


use mineceit\parties\events\types\PartyTournament;
use mineceit\player\MineceitPlayer;

class TournamentMatch
{

    /* @var int */
    private $currentTick;

    /* @var PartyTournament */
    private $tournament;

    /* @var MineceitPlayer */
    private $player1;

    /* @var MineceitPlayer */
    private $player2;

    /* @var bool */
    private $started;

    /* @var bool */
    private $ended;

    /* @var bool */
    private $close;

    public function __construct(MineceitPlayer $p1, MineceitPlayer $p2, PartyTournament $tournament)
    {
        $this->tournament = $tournament;

        $this->player1 = $p1;
        $this->player2 = $p2;

        $this->currentTick = 0;

        $this->started = false;

        $this->ended = false;
    }

    public function update() : void {
        // TODO
    }

    /**
     * @return bool
     */
    public function canClose() : bool {
        return $this->close;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isPlayer(string $name) : bool {
        $result = false;

        if($this->player1 !== null)
            $result = $this->player1->getName() === $name;

        if($this->player2 !== null)
            $result = $this->player2->getName() === $name;

        return $result;
    }

    /**
     * @return bool
     */
    public function hasEnded() : bool {
        return $this->ended;
    }
}