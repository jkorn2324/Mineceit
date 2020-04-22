<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-26
 * Time: 00:15
 */

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay;

use mineceit\MineceitCore;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\MineceitPlayer;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\Server;

class ReplayManager
{

    /* @var array|MineceitReplay[] */
    private $replays;

    /* @var MineceitCore */
    private $core;

    /* @var Server */
    private $server;

    public function __construct(MineceitCore $core)
    {
        $this->replays = [];
        $this->core = $core;
        $this->server = $core->getServer();
    }

    /**
     * @param MineceitPlayer $player
     * @param DuelReplayInfo $info
     */
    public function startReplay(MineceitPlayer $player, DuelReplayInfo $info) : void {

        $worldId = 0;

        $dataPath = $this->server->getDataPath() . 'worlds/';

        $worldData = $info->getWorldData();

        $worldName = "replay$worldId";

        while(isset($this->replays[$worldName]) or is_dir($dataPath . "/$worldName")) {
            $worldId++;
            $worldName = "replay$worldId";
        }

        $generatorClass = $worldData->getGeneratorClass();

        $generator = GeneratorManager::getGenerator(GeneratorManager::getGeneratorName($generatorClass));

        $this->server->generateLevel($worldName, null, $generator, []);
        $this->server->loadLevel($worldName);

        $duelHandler = MineceitCore::getDuelHandler();

        if($duelHandler->isInQueue($player)) {
            $duelHandler->removeFromQueue($player, false);
        }

        $this->replays[$worldName] = new MineceitReplay($player, $worldName, $info);
    }

    /**
     * @return array|MineceitReplay[]
     */
    public function getReplays() : array {
        return $this->replays;
    }

    public function deleteReplay(string $worldId) : void {

        if(isset($this->replays[$worldId]))
            unset($this->replays[$worldId]);

    }

    /**
     * @param MineceitPlayer|string $player
     * @return MineceitReplay|null
     *
     * Gets the replay from the spectator.
     */
    public function getReplayFrom($player) {

        $name = $player instanceof MineceitPlayer ? $player->getName() : strval($player);

        foreach($this->replays as $key => $replay) {
            if($replay->getSpectator()->getName() === $name) {
                return $replay;
            }
        }

        return null;
    }

    /**
     * @param Level|string $level
     * @return bool
     *
     * Determines if the level is a replay level.
     */
    public function isReplayLevel($level) : bool {
        $name = $level instanceof Level ? $level->getName() : $level;
        return isset($this->replays[$name]);
    }

    /**
     * @param Level|string $level
     * @return MineceitReplay|null
     */
    public function getReplayFromLevel($level)
    {
        $name = $level instanceof Level ? $level->getName() : $level;
        return $this->isReplayLevel($level) ? $this->replays[$name] : null;
    }
}