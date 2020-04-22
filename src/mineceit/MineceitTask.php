<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-26
 * Time: 18:45
 */

declare(strict_types=1);

namespace mineceit;


use mineceit\game\entities\ReplayHuman;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitTask extends Task
{

    /* @var Server */
    private $server;

    /* @var MineceitCore */
    private $core;

    /** @var int */
    private $currentTick;

    public function __construct(MineceitCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();
        $this->currentTick = 0;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick) {

        $fiveSecs = MineceitUtil::secondsToTicks(5);
        $mins = MineceitUtil::minutesToTicks(15);

        if($this->currentTick % $fiveSecs === 0 or $this->currentTick === 0) {

            $leaderboards = MineceitCore::getLeaderboards();

            $leaderboards->reloadEloLeaderboards();
            $leaderboards->reloadStatsLeaderboards();
        }

        $this->updatePlayers();
        $this->updateEvents();
        $this->updateDuels();
        $this->updateReplays();
        $this->updateParties();

        if($this->currentTick % $mins === 0) {
            $this->clearEntities();
        }

        $this->currentTick++;
    }

    /**
     * Updates all of the events in the server.
     */
    private function updateEvents() : void {

        $events = MineceitCore::getEventManager();
        $events = $events->getEvents();

        foreach($events as $event) {
            $event->update();
        }
    }

    /**
     *
     * Updates the players in the server.
     */
    private function updatePlayers() : void {

        $players = $this->server->getOnlinePlayers();

        foreach($players as $player) {

            if($player instanceof MineceitPlayer) {

                if ($this->currentTick % 20 === 0) {
                    $player->update();
                }

                if ($this->currentTick % 10 === 0) {
                    $player->updateCPSTrackers($this->currentTick);
                }

                $player->updateCps();
            }
        }
    }

    /**
     * Updates the duels.
     */
    private function updateDuels() : void {

        $duelHandler = MineceitCore::getDuelHandler();

        $duels = $duelHandler->getDuels();

        foreach($duels as $duel)
            $duel->update();
    }


    /**
     * Updates the replays.
     */
    private function updateReplays() : void {

        $replayHandler = MineceitCore::getReplayManager();

        $replays = $replayHandler->getReplays();

        foreach($replays as $replay)
            $replay->update();
    }

    /**
     * Clears the entities within a level.
     */
    private function clearEntities() : void {

        $levels = $this->server->getLevels();

        $defaultLevel = $this->server->getDefaultLevel();

        foreach($levels as $level) {

            // TODO DO MORE LEVELS

            if($defaultLevel !== null and $defaultLevel->getId() === $level->getId()) {
                $entities = $level->getEntities();
                foreach($entities as $entity) {
                    if($entity instanceof ReplayHuman){
                        $entity->close();
                    }
                }
            }
        }
    }


    /**
     * Updates the parties and party events.
     */
    private function updateParties() : void {

        $partyManager = MineceitCore::getPartyManager();

        $partyManager->getEventManager()->updateEvents();
    }
}