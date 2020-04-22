<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-25
 * Time: 17:45
 */

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\info;


use mineceit\kits\AbstractKit;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;

class DuelReplayInfo
{
    /* @var PlayerReplayData */
    private $playerAData;

    /* @var PlayerReplayData */
    private $playerBData;

    /* @var WorldReplayData */
    private $worldData;

    /* @var int */
    private $endTick;

    /* @var AbstractKit $kit
     * Kit used during the duel.
     */
    private $kit;

    /**
     * DuelReplay constructor.
     * @param int $endTick -> The ending tick;
     * @param PlayerReplayData $p1Data;
     * @param PlayerReplayData $p2Data;
     * @param WorldReplayData $worldData;
     * @param AbstractKit $kit;
     */
    public function __construct(int $endTick, PlayerReplayData $p1Data, PlayerReplayData $p2Data, WorldReplayData $worldData, AbstractKit $kit)
    {
        $this->endTick = $endTick + 5;
        $this->playerAData = $p1Data;
        $this->playerBData = $p2Data;
        $this->worldData = $worldData;
        $this->kit = $kit;
    }

    /**
     * @return PlayerReplayData
     */
    public function getPlayerAData() : PlayerReplayData {
        return $this->playerAData;
    }

    /**
     * @return PlayerReplayData
     */
    public function getPlayerBData() : PlayerReplayData {
        return $this->playerBData;
    }

    /**
     * @return WorldReplayData
     */
    public function getWorldData() : WorldReplayData {
        return $this->worldData;
    }

    /**
     * @return int
     */
    public function getEndTick() : int {
        return $this->endTick;
    }

    /**
     * @return AbstractKit
     */
    public function getKit() : AbstractKit {
        return $this->kit;
    }
}