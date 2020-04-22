<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-07
 * Time: 13:58
 */

declare(strict_types=1);

namespace mineceit\scoreboard;

use mineceit\player\MineceitPlayer;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

class Scoreboard
{

    public const SCOREBOARD_SPAWN = 'spawn';
    public const SCOREBOARD_DUEL = 'duel';
    public const SCOREBOARD_FFA = 'ffa';
    public const SCOREBOARD_NONE = 'none';
    public const SCOREBOARD_SPECTATOR = 'spec';
    public const SCOREBOARD_REPLAY = 'replay';
    public const SCOREBOARD_EVENT_SPEC = 'event.spectator';
    public const SCOREBOARD_EVENT_DUEL = 'event.duel';

    private const SORT_ASCENDING = 0;

    private const SLOT_SIDEBAR = 'sidebar';

    /* @var ScorePacketEntry[] */
    private $lines;

    /* @var string */
    private $title;

    /* @var MineceitPlayer */
    private $player;

    public function __construct(MineceitPlayer $player, string $title)
    {
        $this->title = $title;
        $this->lines = [];
        $this->player = $player;
        $this->initScoreboard();
    }

    private function initScoreboard() : void {
        $pkt = new SetDisplayObjectivePacket();
        $pkt->objectiveName = $this->player->getName();
        $pkt->displayName = $this->title;
        $pkt->sortOrder = self::SORT_ASCENDING;
        $pkt->displaySlot = self::SLOT_SIDEBAR;
        $pkt->criteriaName = 'dummy';
        $this->player->dataPacket($pkt);
    }

    public function clearScoreboard() : void {

        $pkt = new SetScorePacket();

        $pkt->entries = $this->lines;

        $pkt->type = SetScorePacket::TYPE_REMOVE;

        $this->player->dataPacket($pkt);

        $this->lines = [];
    }

    public function addLine(int $id, string $line) : void {

        $entry = new ScorePacketEntry();

        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

        if(isset($this->lines[$id])) {

            $pkt = new SetScorePacket();

            $pkt->entries[] = $this->lines[$id];

            $pkt->type = SetScorePacket::TYPE_REMOVE;

            $this->player->dataPacket($pkt);

            unset($this->lines[$id]);
        }

        $entry->score = $id;

        $entry->scoreboardId = $id;

        $entry->entityUniqueId = $this->player->getId();

        $entry->objectiveName = $this->player->getName();

        $entry->customName = $line;

        $this->lines[$id] = $entry;

        $pkt = new SetScorePacket();

        $pkt->entries[] = $entry;

        $pkt->type = SetScorePacket::TYPE_CHANGE;

        $this->player->dataPacket($pkt);
    }

    public function removeLine(int $id) : void {

        if(isset($this->lines[$id])) {

            $line = $this->lines[$id];

            $packet = new SetScorePacket();

            $packet->entries[] = $line;

            $packet->type = SetScorePacket::TYPE_REMOVE;

            $this->player->dataPacket($packet);

            unset($this->lines[$id]);
        }
    }

    public function removeScoreboard() : void {

        $packet = new RemoveObjectivePacket();

        $packet->objectiveName = $this->player->getName();

        $this->player->dataPacket($packet);
    }

    public function resendScoreboard() : void {
        $this->initScoreboard();
    }

}