<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-02
 * Time: 13:45
 */

declare(strict_types=1);

namespace mineceit\duels\requests;


use Grpc\Server;
use mineceit\duels\level\classic\ClassicSpleefGen;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class RequestHandler
{

    /* @var DuelRequest[]|array */
    private $requests;

    /* @var Server */
    private $server;

    /* @var MineceitCore */
    private $core;

    public function __construct(MineceitCore $core)
    {
        $this->server = $core->getServer();
        $this->requests = [];
        $this->core = $core;
    }

    /**
     * @param MineceitPlayer $from
     * @param MineceitPlayer $to
     * @param string $queue
     * @param bool $ranked
     * @param string|null $generator
     *
     * Sends a duel request from a player to another.
     */
    public function sendRequest(MineceitPlayer $from, MineceitPlayer $to, string $queue, bool $ranked, string $generator = null): void
    {

        if($generator == null) {

            $kit = MineceitCore::getKits()->getKit($queue);

            // TODO JUAN FRIENDLY MODE

            switch ($kit->getWorldType()) {
                case WorldReplayData::TYPE_SUMO:
                    $generator = MineceitUtil::randomizeSumoArenas();
                    break;
                case WorldReplayData::TYPE_SPLEEF:
                    $generator = ClassicSpleefGen::class;
                    break;
                default: $generator = MineceitUtil::randomizeDuelArenas();
            }
        }

        $fromMsg = $from->getLanguage()->generalMessage(Language::SENT_REQUEST, ["name" => $to->getDisplayName()]);
        $from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

        $toMsg = $to->getLanguage()->generalMessage(Language::RECEIVE_REQUEST, ["name" => $from->getDisplayName()]);
        $key = $from->getName() . ':' . $to->getName();

        $send = true;

        if(isset($this->requests[$key])) {
            /** @var DuelRequest $oldRequest */
            $oldRequest = $this->requests[$key];
            $send = $oldRequest->getQueue() !== $queue or $oldRequest->isRanked() !== $ranked;
        }

        if($send) {
            $to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);
        }

        $this->requests[$key] = new DuelRequest($from, $to, $queue, $ranked, $generator);
    }

    /**
     * @param MineceitPlayer $player\
     * @return array|DuelRequest[]
     *
     * Gets the requests of a player.
     */
    public function getRequestsOf(MineceitPlayer $player)
    {

        $result = [];

        $name = $player->getName();

        foreach ($this->requests as $request) {
            $from = $request->getFrom();
            if($request->getTo()->getName() === $name and $from->isOnline()) {
                $result[$from->getName()] = $request;
            }
        }

        return $result;
    }

    /**
     * @param MineceitPlayer|string $player
     *
     * Removes all requests with the player's name.
     */
    public function removeAllRequestsWith($player) : void {
        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;
        foreach($this->requests as $key => $request) {
            if($request->getFromName() === $name or $request->getToName() === $name) {
                unset($this->requests[$key]);
            }
        }
    }

    /**
     * @param DuelRequest $request
     *
     * Accepts a duel request.
     */
    public function acceptRequest(DuelRequest $request) : void
    {
        $from = $request->getFrom();
        $to = $request->getTo();

        $toMsg = $to->getLanguage()->generalMessage(Language::DUEL_ACCEPTED_REQUEST_TO, ["name" => $request->getFromDisplayName()]);
        $to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);

        $fromMsg = $from->getLanguage()->generalMessage(Language::DUEL_ACCEPTED_REQUEST_FROM, ["name" => $request->getToDisplayName()]);
        $from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

        unset($this->requests[$request->getFromName() . ':' . $request->getToName()]);
    }
}