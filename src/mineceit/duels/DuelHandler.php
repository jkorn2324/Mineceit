<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-28
 * Time: 16:06
 */

declare(strict_types=1);

namespace mineceit\duels;

use mineceit\duels\level\classic\ClassicDuelGen;
use mineceit\duels\level\classic\ClassicSpleefGen;
use mineceit\duels\level\classic\ClassicSumoGen;
use mineceit\duels\groups\MineceitDuel;
use mineceit\duels\players\QueuedPlayer;
use mineceit\duels\requests\RequestHandler;
use mineceit\kits\Kits;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\ScoreboardUtil;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\Server;

class DuelHandler
{

    /* @var QueuedPlayer[]|array */
    private $queuedPlayers;

    /* @var MineceitDuel[]|array */
    private $duels;

    /* @var Server */
    private $server;

    /* @var MineceitCore */
    private $core;

    /** @var RequestHandler */
    private $requestHandler;

    public function __construct(MineceitCore $core)
    {
        $this->requestHandler = new RequestHandler($core);
        $this->queuedPlayers = [];
        $this->duels = [];
        $this->server = $core->getServer();
        $this->core = $core;
    }

    /**
     * @return RequestHandler
     */
    public function getRequestHandler() : RequestHandler {
        return $this->requestHandler;
    }

    /**
     * @param MineceitPlayer $player
     * @param string $queue
     * @param bool $ranked
     */
    public function placeInQueue(MineceitPlayer $player, string $queue, bool $ranked = false) : void {

        $local = strtolower($player->getName());
        if(isset($this->queuedPlayers[$local])) {
            unset($this->queuedPlayers[$local]);
        }

        $theQueue = new QueuedPlayer($player, $queue, $ranked);
        $this->queuedPlayers[$local] = $theQueue;

        MineceitCore::getItemHandler()->addLeaveQueueItem($player);

        $player->addQueueToScoreboard($ranked, $queue);

        $player->sendMessage(MineceitUtil::getPrefix() . " " . $player->getLanguage()->getDuelMessage(Language::DUEL_ENTER_QUEUE, $queue, $ranked));

        if(($matched = $this->findMatch($theQueue)) !== null && $matched instanceof QueuedPlayer) {
            $matchedLocal = strtolower($matched->getPlayer()->getName());
            unset($this->queuedPlayers[$local], $this->queuedPlayers[$matchedLocal]);
            $this->placeInDuel($player, $matched->getPlayer(), $queue, $ranked);
        }

        ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
    }

    /**
     * @param MineceitPlayer|string $player
     * @return bool
     */
    public function isInQueue($player) : bool {
        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;
        return isset($this->queuedPlayers[strtolower($name)]);
    }


    /**
     * @param MineceitPlayer $player
     * @param bool $sendMessage
     */
    public function removeFromQueue(MineceitPlayer $player, bool $sendMessage = true) : void {

        $local = strtolower($player->getName());
        if(!isset($this->queuedPlayers[$local])){
            return;
        }

        /** @var QueuedPlayer $queue */
        $queue = $this->queuedPlayers[$local];
        unset($this->queuedPlayers[$local]);

        MineceitCore::getItemHandler()->removeQueueItem($player);

        $player->removeQueueFromScoreboard();

        if($sendMessage) {
            $player->sendMessage(MineceitUtil::getPrefix() . " " . $player->getLanguage()->getDuelMessage(Language::DUEL_LEAVE_QUEUE, $queue->getQueue(), $queue->isRanked()));
        }

        ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
    }

    /**
     * @param bool $ranked
     * @param string $queue
     * @return int
     */
    public function getPlayersInQueue(bool $ranked, string $queue) : int {

        $count = 0;
        foreach($this->queuedPlayers as $pQueue) {
            if($queue === $pQueue->getQueue() and $pQueue->isRanked() === $ranked) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return null|QueuedPlayer
     */
    public function getQueueOf($player) {

        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;

        if(isset($this->queuedPlayers[strtolower($name)])) {
            return $this->queuedPlayers[strtolower($name)];
        }

        return null;
    }

    /**
     * @return int
     */
    public function getEveryoneInQueues() : int {
        return count($this->queuedPlayers);
    }

    /**
     * @param QueuedPlayer $player
     * @return QueuedPlayer|null
     */
    public function findMatch(QueuedPlayer $player) {

        $p = $player->getPlayer();

        $peOnly = $player->isPeOnly();

        $isPe = $p->isPe();

        foreach($this->queuedPlayers as $queue) {

            $queuedPlayer = $queue->getPlayer();

            $isMatch = false;

            if ($p->getDisplayName() === $queue->getPlayer()->getDisplayName() or !$queuedPlayer->canDuel()) {
                continue;
            }

            if ($queue->isRanked() === $player->isRanked() and $player->getQueue() === $queue->getQueue()) {

                $isMatch = true;

                if ($peOnly and $isPe) {
                    $isMatch = $queuedPlayer->isOnline() and $queuedPlayer->isPe();
                }
            }

            if($isMatch) {
                return $queue;
            }
        }

        return null;
    }

    /**
     * @param MineceitPlayer $p1
     * @param MineceitPlayer $p2
     * @param string $queue
     * @param bool $ranked
     * @param bool $foundDuel
     * @param string|null $generator
     */
    public function placeInDuel(MineceitPlayer $p1, MineceitPlayer $p2, string $queue, bool $ranked = false, bool $foundDuel = true, string $generator = null) : void
    {

        $matchId = 0;

        $dataPath = $this->server->getDataPath() . '/worlds';

        while (isset($this->duels[$matchId]) or is_dir($dataPath . '/' . $matchId)) {
            $matchId++;
        }

        if($generator == null) {
            $kit = MineceitCore::getKits()->getKit($queue);
            switch($kit->getWorldType()) {
                case WorldReplayData::TYPE_SUMO:
                    $generator = MineceitUtil::randomizeSumoArenas();
                    break;
                case WorldReplayData::TYPE_SPLEEF:
                    $generator = MineceitUtil::CLASSIC_SPLEEF_GEN;
                    break;
                default: $generator = MineceitUtil::randomizeDuelArenas();
            }
        }

        $generatorClass = MineceitCore::getGeneratorManager()->getGeneratorClass($generator);

        $generator = GeneratorManager::getGenerator(GeneratorManager::getGeneratorName($generatorClass));

        $this->server->generateLevel("$matchId", null, $generator, []);
        $this->server->loadLevel("$matchId");

        $this->duels[$matchId] = new MineceitDuel($matchId, $p1, $p2, $queue, $ranked, $generatorClass);;

        if($foundDuel) {

            $p1Msg = $p1->getLanguage()->getDuelMessage(Language::DUEL_FOUND_MATCH, $queue, $ranked, $p2->getDisplayName());

            $p2Msg = $p2->getLanguage()->getDuelMessage(Language::DUEL_FOUND_MATCH, $queue, $ranked, $p1->getDisplayName());

            $p1->sendMessage(MineceitUtil::getPrefix() . ' ' . $p1Msg);
            $p2->sendMessage(MineceitUtil::getPrefix() . ' ' . $p2Msg);
        }

        ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
        ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
    }

    /**
     * @param bool $count
     * @return array|MineceitDuel[]|int
     */
    public function getDuels(bool $count = false) {
        return $count ? count($this->duels) : $this->duels;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return MineceitDuel|null
     */
    public function getDuel($player) {

        foreach($this->duels as $duel) {
            if($duel->isPlayer($player)) {
                return $duel;
            }
        }

        return null;
    }

    /**
     * @param int $key
     *
     * Removes a duel with the given key.
     */
    public function removeDuel(int $key) : void {

        if(isset($this->duels[$key])) {
            unset($this->duels[$key]);
        }

        ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
    }

    /**
     * @param string|int $level
     * @return bool
     *
     * Determines if the level is a duel level.
     */
    public function isDuelLevel($level) : bool {
        $name = is_int($level) ? intval($level) : $level;
        return is_numeric($name) and isset($this->duels[intval($name)]);
    }

    /**
     * @param string|int $level
     * @return MineceitDuel|null
     *
     * Gets the duel based on the level name.
     */
    public function getDuelFromLevel($level) {
        $name = is_numeric($level) ? intval($level) : $level;
        return (is_numeric($name) and isset($this->duels[$name])) ? $this->duels[$name] : null;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return MineceitDuel|null
     *
     * Gets the duel from the spectator.
     */
    public function getDuelFromSpec($player) {
        foreach($this->duels as $duel) {
            if($duel->isSpectator($player)){
                return $duel;
            }
        }
        return null;
    }

    /**
     * @param MineceitPlayer $player
     * @param MineceitDuel $duel
     *
     * Adds a spectator to a duel.
     */
    public function addSpectatorTo(MineceitPlayer $player, MineceitDuel $duel) : void {

        $local = strtolower($player->getName());

        if(isset($this->queuedPlayers[$local])) {
            unset($this->queuedPlayers[$local]);
            ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
        }

        $duel->addSpectator($player);
    }
}