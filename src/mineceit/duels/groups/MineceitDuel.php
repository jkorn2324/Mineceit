<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-28
 * Time: 16:36
 */

declare(strict_types=1);

namespace mineceit\duels\groups;

use mineceit\duels\level\classic\ClassicDuelGen;
use mineceit\kits\AbstractKit;
use mineceit\kits\Kits;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\ReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\block\Block;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitDuel
{

    private const MAX_DURATION_SECONDS = 60 * 15;

    /* @var MineceitPlayer */
    private $player1;

    /* @var MineceitPlayer */
    private $player2;

    /* @var int */
    private $worldId;

    /* @var AbstractKit */
    private $kit;

    /* @var bool */
    private $ranked;

    /* @var Level */
    private $level;

    /* @var int */
    private $durationSeconds;

    /* @var int */
    private $countdownSeconds;

    /* @var bool */
    private $started;

    /* @var bool */
    private $ended, $sentReplays;

    /* @var string|null */
    private $winner;

    /* @var string|null */
    private $loser;

    /* @var MineceitPlayer[]|array */
    private $spectators;

    /* @var Server */
    private $server;

    /* @var int */
    private $currentTicks;

    /* @var int|null */
    private $endTick;

    private $queue;

    /* @var Block[]|array */
    private $blocks;

    /* @var int[]|array */
    private $numHits;

    /* @var Position|null */
    private $centerPosition;

    /* @var array|ReplayData[]
     * The data stored for replay.
     */
    private $replayData;

    /** @var int */
    private $player1Elo, $player2Elo, $player1DevOS, $player2DevOS;

    /** @var string */
    private $player1Name, $player2Name, $player1DisplayName, $player2DisplayName;
    
    public function __construct(int $worldId, MineceitPlayer $p1, MineceitPlayer $p2, string $queue, bool $ranked = false, string $generatorClass = ClassicDuelGen::class)
    {
        $this->player1 = $p1;
        $this->player2 = $p2;
        $this->player1Name = $p1->getName();
        $this->player2Name = $p2->getName();
        $this->player1DisplayName = $p1->getDisplayName();
        $this->player2DisplayName = $p2->getDisplayName();
        $this->worldId = $worldId;
        $this->kit = MineceitCore::getKits()->getKit($queue);
        $this->queue = $queue;
        $this->ranked = $ranked;
        $this->server = Server::getInstance();
        $this->blocks = [];
        $this->level = $this->server->getLevelByName("$worldId");
        $this->centerPosition = null;
        $this->player1Elo = $p1->getElo($this->queue);
        $this->player2Elo = $p2->getElo($this->queue);

        $this->player1DevOS = $p1->getDeviceOS();
        $this->player2DevOS = $p2->getDeviceOS();

        $this->started = false;
        $this->ended = false;
        $this->countdownSeconds = 5;
        $this->durationSeconds = 0;
        $this->currentTicks = 0;

        $this->sentReplays = false;

        $this->endTick = null;

        $this->winner = null;
        $this->loser = null;

        $this->spectators = [];

        $this->numHits = [$this->player1Name => 0, $this->player2Name => 0];

        $this->replayData = [];

        $worldType = $this->kit->getWorldType();

        if($this->kit->isReplaysEnabled()) {

            $this->replayData = ['world' => new WorldReplayData($worldType, $ranked, $generatorClass)];

        }
    }

    /**
     * Sets the players in the duel.
     */
    private function setInDuel(): void
    {
        $this->player1->setGamemode(0);
        $this->player2->setGamemode(0);

        $this->player1->setPlayerFlying(false);
        $this->player2->setPlayerFlying(false);

        $this->player1->setImmobile(true);
        $this->player2->setImmobile(true);

        if(!$this->player1->isDisplayPlayers())
            $this->player1->showPlayers(false);

        if(!$this->player2->isDisplayPlayers())
            $this->player2->showPlayers(false);

        $this->player1->setDuelNameTag();
        $this->player2->setDuelNameTag();

        $this->player1->clearInventory();
        $this->player2->clearInventory();

        $level = $this->level;

        $queue = strtolower($this->queue);

        $x = ($queue === Kits::SUMO) ? 9 : 24;
        $z = ($queue === Kits::SUMO) ? 5 : 40;

        // 24 100 40

        $y = 100;

        $p1Pos = new Position($x, $y, $z, $level);

        MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function () use ($p1Pos) {
            $this->player1->teleport($p1Pos);
        });

        if($queue !== Kits::SUMO) {
            $z = 10;
        } else {
            $x = 1;
        }

        // 24 100 10

        $p2Pos = new Position($x, $y, $z, $level);

        $p2x = $p2Pos->x;
        $p2z = $p2Pos->z;

        $p1x = $p1Pos->x;
        $p1z = $p1Pos->z;

        $this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), intval($p1Pos->y), intval((($p2z + $p1z) / 2)), $this->level);

        MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function () use ($p2Pos) {
            $this->player2->teleport($p2Pos);
        });

        $this->kit->giveTo($this->player1, false);
        $this->kit->giveTo($this->player2, false);

        $p1Level = $this->player1->getLevel();
        $p2Level = $this->player2->getLevel();

        if ($p1Level->getName() !== $level->getName())
            $this->player1->teleport($p1Pos);

        if ($p2Level->getName() !== $level->getName())
            $this->player2->teleport($p2Pos);

        if($this->kit->isReplaysEnabled()) {
            $this->replayData['player1'] = new PlayerReplayData($this->player1);
            $this->replayData['player2'] = new PlayerReplayData($this->player2);
        }
    }

    /**
     * Updates the duel.
     */
    public function update() {

        $this->currentTicks++;

        $checkSeconds = $this->currentTicks % 20 === 0;

        if (!$this->player1->isOnline() or !$this->player2->isOnline()) {
            if($this->ended) $this->endDuel();
            return;
        }

        $p1Lang = $this->player1->getLanguage();
        $p2Lang = $this->player2->getLanguage();

        if ($this->isCountingDown()) {

            if ($this->currentTicks === 5) $this->setInDuel();

            if ($checkSeconds) {

                $p1Sb = $this->player1->getScoreboardType();

                $p2Sb = $this->player2->getScoreboardType();

                if ($this->countdownSeconds === 5) {

                    $p1Msg = $this->getCountdownMessage(true, $p1Lang, $this->countdownSeconds);
                    $p2Msg = $this->getCountdownMessage(true, $p2Lang, $this->countdownSeconds);
                    $this->player1->sendTitle($p1Msg, '', 5, 20, 5);
                    $this->player2->sendTitle($p2Msg, '', 5, 20, 5);

                } elseif ($this->countdownSeconds !== 0) {

                    $p1Msg = $this->getJustCountdown($p1Lang, $this->countdownSeconds);
                    $p2Msg = $this->getJustCountdown($p2Lang, $this->countdownSeconds);
                    $this->player1->sendTitle($p1Msg, '', 5, 20, 5);
                    $this->player2->sendTitle($p2Msg, '', 5, 20, 5);

                } else {

                    $p1Msg = $p1Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
                    $p2Msg = $p2Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
                    $this->player1->sendTitle($p1Msg, '', 5, 10, 5);
                    $this->player2->sendTitle($p2Msg, '', 5, 10, 5);

                }

                if ($p1Sb !== Scoreboard::SCOREBOARD_NONE and $p1Sb !== Scoreboard::SCOREBOARD_DUEL) {
                    $this->player1->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
                }

                if ($p2Sb !== Scoreboard::SCOREBOARD_NONE and $p2Sb !== Scoreboard::SCOREBOARD_DUEL) {
                    $this->player2->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
                }

                if ($this->countdownSeconds <= 0) {
                    $this->started = true;
                    $this->player1->setImmobile(false);
                    $this->player2->setImmobile(false);
                }

                $this->countdownSeconds--;
            }

        } elseif ($this->isRunning()) {

            $queue = strtolower($this->queue);

            if($queue === Kits::SUMO or $queue === Kits::SPLEEF) {

                $minY = $queue === Kits::SUMO ? 97 : 95;

                $p1Pos = $this->player1->getPosition();
                $p2Pos = $this->player2->getPosition();

                $p1Y = $p1Pos->y;
                $p2Y = $p2Pos->y;

                if($p1Y < $minY) {
                    $this->setEnded($this->player2);
                    return;
                }

                if($p2Y < $minY) {
                    $this->setEnded($this->player1);
                    return;
                }
            }

            if ($checkSeconds) {

                $p1Duration = TextFormat::WHITE . $p1Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                $p2Duration = TextFormat::WHITE . $p2Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);

                $p1DurationStr = TextFormat::WHITE . ' ' . $p1Duration . ': ' . $this->getDuration();
                $p2DurationStr = TextFormat::WHITE . ' ' . $p2Duration . ': ' . $this->getDuration();

                if($p1Lang->getLocale() === Language::ARABIC)
                    $p1DurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $p1Duration;

                if($p2Lang->getLocale() === Language::ARABIC)
                    $p2DurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $p2Duration;

                $this->player1->updateLineOfScoreboard(1, $p1DurationStr);
                $this->player2->updateLineOfScoreboard(1, $p2DurationStr);

                foreach($this->spectators as $spec) {

                    if($spec->isOnline()) {
                        $specLang = $spec->getLanguage();
                        $specDuration = TextFormat::WHITE . $specLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                        $specDurationStr = TextFormat::WHITE . ' ' . $specDuration . ': ' . $this->getDuration();
                        if($specLang->getLocale() === Language::ARABIC)
                            $specDurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $specDuration;
                        $spec->updateLineOfScoreboard(1, $specDurationStr);
                    }
                }

                if ($this->durationSeconds >= self::MAX_DURATION_SECONDS) {
                    $this->setEnded();
                    return;
                }

                $this->durationSeconds++;
            }

        } elseif ($this->hasEnded()) {
            $diff = $this->currentTicks - $this->endTick;
            if ($diff >= 10) {
                $this->endDuel();
                return;
            }
        }

        $this->updateReplayData();
    }


    /**
     * Updates the replay data of the duel.
     */
    private function updateReplayData() : void {

        if(isset($this->replayData['world'])) {

            /* @var WorldReplayData $replayData */
            $replayData = $this->replayData['world'];
            $replayData->update($this->currentTicks, $this->level);

            if ($this->player1 !== null and $this->player2 !== null and $this->player1->isOnline() and $this->player2->isOnline()) {

                /* @var PlayerReplayData|null $p1ReplayData */
                $p1ReplayData = isset($this->replayData['player1']) ? $this->replayData['player1'] : null;
                /* @var PlayerReplayData|null $p2ReplayData */
                $p2ReplayData = isset($this->replayData['player2']) ? $this->replayData['player2'] : null;

                $p1Inv = $this->player1->getInventory();
                $p2Inv = $this->player2->getInventory();

                $p1ArmorInv = $this->player1->getArmorInventory();
                $p2ArmorInv = $this->player2->getArmorInventory();

                if ($p1ArmorInv !== null) {

                    $p1ArmorArr = $p1ArmorInv->getContents(true);
                    $p1Armor = ['helmet' => $p1ArmorArr[0], 'chest' => $p1ArmorArr[1], 'pants' => $p1ArmorArr[2], 'boots' => $p1ArmorArr[3]];

                    if ($p1ReplayData !== null) $p1ReplayData->updateArmor($this->currentTicks, $p1Armor);
                }

                if ($p2ArmorInv !== null) {

                    $p2ArmorArr = $p2ArmorInv->getContents(true);
                    $p2Armor = ['helmet' => $p2ArmorArr[0], 'chest' => $p2ArmorArr[1], 'pants' => $p2ArmorArr[2], 'boots' => $p2ArmorArr[3]];
                    if ($p2ReplayData !== null) $p2ReplayData->updateArmor($this->currentTicks, $p2Armor);
                }

                $p1ItemInHand = $p1Inv->getItemInHand();
                $p2ItemInHand = $p2Inv->getItemInHand();

                if ($p1ReplayData !== null) {

                    $clicksInfo = $this->player1->getClicksInfo();

                    $p1ReplayData->setSprintingAt($this->currentTicks, $this->player1->isSprinting());
                    $p1ReplayData->setItemAt($this->currentTicks, $p1ItemInHand);
                    $p1ReplayData->setCpsAt($this->currentTicks, $clicksInfo->getCps());
                    $p1ReplayData->setPositionAt($this->currentTicks, $this->player1->getPosition());
                    $p1ReplayData->setRotationAt($this->currentTicks, ['yaw' => $this->player1->yaw, 'pitch' => $this->player1->pitch]);
                    $p1ReplayData->setNameTagAt($this->currentTicks, $this->player1->getNameTag());
                }

                if ($p2ReplayData !== null) {

                    $clicksInfo = $this->player2->getClicksInfo();

                    $p2ReplayData->setSprintingAt($this->currentTicks, $this->player2->isSprinting());
                    $p2ReplayData->setItemAt($this->currentTicks, $p2ItemInHand);
                    $p2ReplayData->setCpsAt($this->currentTicks, $clicksInfo->getCps());
                    $p2ReplayData->setPositionAt($this->currentTicks, $this->player2->getPosition());
                    $p2ReplayData->setRotationAt($this->currentTicks, ['yaw' => $this->player2->yaw, 'pitch' => $this->player2->pitch]);
                    $p2ReplayData->setNameTagAt($this->currentTicks, $this->player2->getNameTag());
                }
            }
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param float $force
     *
     * Function used for replays.
     */
    public function setReleaseBow(MineceitPlayer $player, float $force) : void {

        $name = ($player->equals($this->player1)) ? 'player1' : 'player2';

        if(isset($this->replayData[$name])) {

            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$name];
            $replayData->setReleaseBowAt($this->currentTicks, $force);
        }
    }

    /**
     * @param string $player
     * @param int $animation
     *
     * Function used for replays.
     */
    public function setAnimationFor(string $player, int $animation) : void {

        $name = ($player === $this->player1Name) ? 'player1' : 'player2';

        if(isset($this->replayData[$name])) {

            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$name];
            $replayData->setAnimationAt($this->currentTicks, $animation);
        }
    }


    /**
     * @param string $player
     *
     * Sets the death time for the player.
     */
    private function setDeathTime(string $player) : void {

        $name = ($player === $this->player1Name) ? 'player1' : 'player2';

        if(isset($this->replayData[$name])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$name];
            $replayData->setDeathTime($this->currentTicks);
        }
    }


    /**
     * @param Block|Vector3 $block
     * @param bool $air
     *
     * Tracks when a block is set.
     */
    public function setBlockAt(Block $block, bool $air = false) : void {

        if(isset($this->replayData['world'])) {

            /* @var WorldReplayData $replayData */
            $replayData = $this->replayData['world'];

            if ($air) {
                $replayData->setBlockAt($this->currentTicks, 0, $block);
                return;
            }

            $replayData->setBlockAt($this->currentTicks, $block);
        }
    }

    /**
     * @param int $id
     * @param Vector3 $pos
     * @param int $meta
     *
     * Tracks when a liquid is set.
     */
    public function setLiquidAt(int $id, Vector3 $pos, int $meta = 0) : void {

        if(isset($this->replayData['world'])) {
            /* @var WorldReplayData $replayData */
            $replayData = $this->replayData['world'];
            $replayData->setBlockAt($this->currentTicks, $id, $pos, $meta);
        }
    }


    /**
     * @param MineceitPlayer $player
     *
     * Tracks when a player jumps.
     */
    public function setJumpFor(MineceitPlayer $player) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setJumpAt($this->currentTicks);
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param ProjectileItem $item
     *
     * Tracks when a player throws a pearl/potion.
     */
    public function setThrowFor(MineceitPlayer $player, ProjectileItem $item) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setThrowAt($this->currentTicks, $item);
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param bool $drink
     *
     * Tracks when a player eats food.
     */
    public function setConsumeFor(MineceitPlayer $player, bool $drink) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setConsumeAt($this->currentTicks, $drink);
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param EffectInstance $effect
     *
     * Sets the effect for the player -> for replays.
     */
    public function setEffectFor(MineceitPlayer $player, EffectInstance $effect) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setEffectAt($this->currentTicks, $effect);
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param bool $sprinting
     *
     * Sets when the player is sprinting at a given time.
     */
    public function setSprintingFor(MineceitPlayer $player, bool $sprinting) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setSprintingAt($this->currentTicks, $sprinting);
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $fishing
     *
     * Sets when the player is fishing.
     */
    public function setFishingFor(MineceitPlayer $player, bool $fishing) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setFishingAt($this->currentTicks, $fishing);
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param bool|int $variable
     *
     * Sets the player on fire for a certain amount of ticks.
     */
    public function setOnFire(MineceitPlayer $player, $variable) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setFireAt($this->currentTicks, $variable);
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param Item $item
     * @param Vector3 $motion
     */
    public function setDropItem(MineceitPlayer $player, Item $item, Vector3 $motion) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setDropAt($this->currentTicks, $item, $motion);
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param Item $item
     */
    public function setPickupItem(MineceitPlayer $player, Item $item) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setPickupAt($this->currentTicks, $item);
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $sneak
     */
    public function setSneakingFor(MineceitPlayer $player, bool $sneak) : void {
        $p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
        if(isset($this->replayData[$p])) {
            /* @var PlayerReplayData $replayData */
            $replayData = $this->replayData[$p];
            $replayData->setSneakingAt($this->currentTicks, $sneak);
        }
    }

    /**
     * @return bool
     */
    public function isCountingDown(): bool
    {
        return !$this->started and !$this->ended;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->started and !$this->ended;
    }

    /**
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->ended;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return bool
     */
    public function isPlayer($player): bool
    {
        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;
        return $this->player1Name === $name or $this->player2Name === $name;
    }

    /**
     * @param MineceitPlayer|string $player
     * @return MineceitPlayer|null
     */
    public function getOpponent($player)
    {
        $result = null;
        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;
        if ($this->isPlayer($player)) {
            if ($name === $this->player1Name)
                $result = $this->player2;
            else $result = $this->player1;
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getDuration(): string {

        $seconds = $this->durationSeconds % 60;
        $minutes = intval($this->durationSeconds / 60);

        $color = MineceitUtil::getThemeColor();

        $result = $color . '%min%:%sec%';

        $secStr = "$seconds";
        $minStr = "$minutes";

        if ($seconds < 10)
            $secStr = '0' . $seconds;

        if ($minutes < 10)
            $minStr = '0' . $minutes;

        return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
    }

    private function endDuel(): void {

        $this->ended = true;

        if(!$this->sentReplays) {

            $this->sentReplays = true;

            if ($this->endTick === null) $this->endTick = $this->currentTicks;

            $itemHandler = MineceitCore::getItemHandler();

            $info = null;

            if (isset($this->replayData['player1']) and isset($this->replayData['player2']) and isset($this->replayData['world']))
                $info = new DuelReplayInfo($this->endTick, $this->replayData['player1'], $this->replayData['player2'], $this->replayData['world'], $this->kit);

            $size = count($this->spectators);

            if ($this->player1 !== null and $this->player1->isOnline()) {

                if ($info !== null) $this->player1->addReplayDataToDuelHistory($info);

                if (!$this->player1->isDisplayPlayers())
                    $this->player1->hidePlayers(false);

                $this->sendFinalMessage($this->player1, ($this->player2 === null or !$this->player2->isOnline()) and $size <= 0);
                if ($this->player1->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) $this->player1->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                $this->player1->reset(true, $this->player1->isAlive());
                $itemHandler->spawnHubItems($this->player1);
                $this->player1->setSpawnNameTag();
            }

            if ($this->player2 !== null and $this->player2->isOnline()) {

                if ($info !== null) $this->player2->addReplayDataToDuelHistory($info);

                if (!$this->player2->isDisplayPlayers())
                    $this->player2->hidePlayers(false);

                $this->sendFinalMessage($this->player2, $size <= 0);
                if ($this->player2->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) $this->player2->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                $this->player2->reset(true, $this->player2->isAlive());
                $itemHandler->spawnHubItems($this->player2);
                $this->player2->setSpawnNameTag();
            }

            $this->sendFinalMessageToSpecs();

            $count = 0;

            foreach ($this->spectators as $spec) {
                if ($spec !== null and $spec->isOnline()) {
                    if ($spec->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) $spec->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                    $spec->reset(true, true);
                    $itemHandler->spawnHubItems($spec, $count + 1 === $size);
                    $spec->setSpawnNameTag();

                    if (!$spec->isDisplayPlayers())
                        $spec->hidePlayers(false);
                }
                $count++;
            }

            $this->spectators = [];

            MineceitUtil::deleteLevel($this->level);

            MineceitCore::getDuelHandler()->removeDuel($this->worldId);
        }
    }

    /**
     * @param MineceitPlayer|null $winner
     * @param bool $logDuelHistory
     *
     * Sets the duel as ended.
     */
    public function setEnded(MineceitPlayer $winner = null, bool $logDuelHistory = true): void
    {

        $online = $this->player1 !== null and $this->player1->isOnline() and $this->player2 !== null and $this->player2->isOnline();

        if ($winner !== null and $this->isPlayer($winner) and $logDuelHistory) {

            $this->winner = $winner->getName();
            $loser = $this->getOpponent($this->winner);
            $this->loser = $loser->getName();

            $this->setDeathTime($this->loser);

            $winnerInfo = new DuelInfo($winner, $this->queue, $this->ranked,  $this->numHits[$winner->getName()]);

            $loserInfo = new DuelInfo($loser, $this->queue, $this->ranked, $this->numHits[$loser->getName()]);

            $winner->addToDuelHistory($winnerInfo, $loserInfo);
            $loser->addToDuelHistory($winnerInfo, $loserInfo);

        } elseif ($winner === null and $online and $logDuelHistory) {

            $p1Info = new DuelInfo($this->player1, $this->queue, $this->ranked, $this->numHits[$this->player1->getName()]);

            $p2Info = new DuelInfo($this->player2, $this->queue, $this->ranked, $this->numHits[$this->player2->getName()]);

            $this->player1->addToDuelHistory($p1Info, $p2Info, true);

            $this->player2->addToDuelHistory($p2Info, $p1Info, true);
        }

        $this->ended = true;
        $this->endTick = $this->currentTicks;
    }

    /**
     * @param MineceitPlayer $player
     */
    public function addSpectator(MineceitPlayer $player): void
    {

        $name = $player->getName();

        $local = strtolower($name);

        $this->spectators[$local] = $player;

        $player->setInSpectatorMode(true, true);

        $itemHandler = MineceitCore::getItemHandler();

        $itemHandler->giveLeaveDuelItem($player);

        $player->teleport($this->centerPosition);

        $this->broadcastMessageFromLang(Language::DUELS_SPECTATOR_ADD, ["name" => $name]);

        $player->setScoreboard(Scoreboard::SCOREBOARD_SPECTATOR);
    }

    /**
     * @param MineceitPlayer|string $player
     * @param bool $teleportToSpawn
     * @param bool $sendMessage
     */
    public function removeSpectator($player, bool $teleportToSpawn = true, bool $sendMessage = true): void
    {

        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;

        $local = strtolower($name);

        if (isset($this->spectators[$local])) {

            $player = $this->spectators[$local];

            if ($player->isOnline()) {
                $player->setInSpectatorMode(false);
                $player->reset(false, $teleportToSpawn);
                $player->setSpawnNameTag();
                if($teleportToSpawn) MineceitCore::getItemHandler()->spawnHubItems($player);
            }

            unset($this->spectators[$local]);
            
            if($sendMessage) $this->broadcastMessageFromLang(Language::DUELS_SPECTATOR_LEAVE, ['name' => $name]);
        }
    }

    /**
     * @param bool $title
     * @param Language $lang
     * @param int $countdown
     * @return string
     */
    private function getCountdownMessage(bool $title, Language $lang, int $countdown): string
    {

        $message = $lang->generalMessage(Language::DUELS_MESSAGE_COUNTDOWN);

        $color = MineceitUtil::getThemeColor();

        if (!$title)
            $message .= ($lang->getLocale() === Language::ARABIC ? TextFormat::BOLD . $color . '...' . $countdown : TextFormat::BOLD . $color . $countdown . '...');
        else {
            $message = ($lang->getLocale() === Language::ARABIC ? TextFormat::BOLD . $color . "...$countdown\n" . $message : $message . "\n" . $color . TextFormat::BOLD . "$countdown...");
        }

        return $message;
    }

    /**
     * @param string $langVal
     * @param array|string $values
     */
    private function broadcastMessageFromLang(string $langVal, $values = []) : void {

        $prefix = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET;

        if ($this->player1 !== null and $this->player1->isOnline()) {
            $language = $this->player1->getLanguage();
            $message = $language->generalMessage($langVal, $values);
            $this->player1->sendMessage($prefix . $message);
        }

        if ($this->player2 !== null and $this->player2->isOnline()) {
            $language = $this->player2->getLanguage();
            $message = $language->generalMessage($langVal, $values);
            $this->player2->sendMessage($prefix . $message);
        }

        foreach ($this->spectators as $spectator) {
            if ($spectator->isOnline()) {
                $language = $spectator->getLanguage();
                $message = $language->generalMessage($langVal, $values);
                $spectator->sendMessage($prefix . $message);
            }
        }

    }

    /**
     * @param Language $lang
     * @param int $countdown
     * @return string
     */
    private function getJustCountdown(Language $lang, int $countdown): string
    {
        $color = MineceitUtil::getThemeColor();
        $message = TextFormat::BOLD . $color . "$countdown...";
        if ($lang->getLocale() === Language::ARABIC)
            $message = TextFormat::BOLD . $color . "...$countdown";
        return $message;
    }

    /**
     * @param MineceitPlayer|null $killer
     * @param MineceitPlayer|null $killed
     */
    public function broadcastDeathMessage(?MineceitPlayer $killer, ?MineceitPlayer $killed): void {

        $message = '';

        if($killed !== null and $killed->isOnline()) {
            $message = TextFormat::RED . $killed->getName() . TextFormat::GRAY . ' died';
            if($killer !== null and $killed->isOnline())
                $message = TextFormat::RED . $killed->getName() . TextFormat::GRAY . ' was killed by ' . TextFormat::GREEN . $killer->getName();
        }

        if($message !== '')
            $this->broadcastMessage($message);
    }

    /**
     * @param string $message
     * @param bool $sendToSpectators
     */
    private function broadcastMessage(string $message, bool $sendToSpectators = true) : void
    {

        if ($this->player1 !== null and $this->player1->isOnline())
            $this->player1->sendMessage($message);

        if ($this->player2 !== null and $this->player2->isOnline())
            $this->player2->sendMessage($message);

        if ($sendToSpectators) {
            foreach ($this->spectators as $spectator) {
                if ($spectator->isOnline()) $spectator->sendMessage($message);
            }
        }
    }

    private function sendFinalMessageToSpecs() : void {
        foreach($this->spectators as $spectator)
            if($spectator->isOnline()) $this->sendFinalMessage($spectator);
    }


    /**
     * @param MineceitPlayer|null $playerToSendMessage
     * @param bool $setElo
     */
    public function sendFinalMessage(?MineceitPlayer $playerToSendMessage, bool $setElo = false) : void {

        if($playerToSendMessage !== null and $playerToSendMessage->isOnline()) {

            $lang = $playerToSendMessage->getLanguage();

            $none = $lang->generalMessage(Language::NONE);

            $winner = $this->winner ?? $none;
            $loser = $this->loser ?? $none;

            $winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner]);
            $loserMessage = $lang->generalMessage(Language::DUELS_MESSAGE_LOSER, ["name" => $loser]);

            $separator = '--------------------------';

            $eloChangesStr = null;

            $playerHandler = MineceitCore::getPlayerHandler();

            $result = ['%', $winnerMessage, $loserMessage, '%'];

            if ($this->ranked and $this->winner !== null and $this->loser !== null) {

                $winnerStartElo = $this->winner === $this->player1Name ? $this->player1Elo : $this->player2Elo;
                $loserStartElo = $this->loser === $this->player1Name ? $this->player1Elo : $this->player2Elo;

                $elo = $playerHandler->setElo($this->winner, $this->loser, $winnerStartElo, $loserStartElo, $this->player1DevOS, $this->player2DevOS, strtolower($this->queue), $setElo);

                $winnerEloChange = $elo['winner-change'];
                $loserEloChange = $elo['loser-change'];

                $wStr = str_replace('%elo-change%', $winnerEloChange, TextFormat::GOLD . "$this->winner" . TextFormat::GRAY . '(' . TextFormat::GREEN . '+%elo-change%' . TextFormat::GRAY . ')');
                $lStr = str_replace('%elo-change%', $loserEloChange, TextFormat::GOLD . "$this->loser" . TextFormat::GRAY . '(' . TextFormat::RED . '-%elo-change%' . TextFormat::GRAY . ')');

                $ec = $wStr . TextFormat::RESET . TextFormat::DARK_GRAY . ', ' . $lStr;

                $eloChange = $lang->generalMessage(Language::DUELS_MESSAGE_ELOCHANGES, ["num" => $ec]);

                $result[] = $eloChange;
                $result[] = '%';
            }

            $size = count($this->spectators);

            $spectators = $lang->generalMessage(Language::DUELS_MESSAGE_SPECTATORS, ["list" => TextFormat::GOLD . "{$none}"]);

            if ($size > 0) {

                $spectators = str_replace("{$none}", '', $spectators);

                $specs = '';

                $theSize = ($size > 3) ? 3 : $size;

                $keys = array_keys($this->spectators);

                $size -= $theSize;

                for ($i = 0; $i < $theSize; $i++) {
                    $key = $keys[$i];
                    $spec = $this->spectators[$key];
                    $comma = $i === $theSize - 1 ? '' : TextFormat::DARK_GRAY . ', ';
                    $specs .= TextFormat::GOLD . $spec->getName() . $comma;
                }

                $specs .= TextFormat::GRAY . ' (' . TextFormat::GOLD . "+$size" . TextFormat::GRAY . ')';

                $spectators .= $specs;
            }

            $result[] = $spectators;
            $result[] = '%';

            $keys = array_keys($result);

            foreach ($keys as $key) {
                $str = $result[$key];
                if ($str === '%') $result[$key] = $separator;
            }

            foreach($result as $res)
                $playerToSendMessage->sendMessage($res);
        }
    }

    /**
     * @param Block $block
     * @param bool $break
     * @return bool
     */
    public function canPlaceBlock(Block $block, bool $break = false) : bool {

        $queue = strtolower($this->queue);

        if(!$this->isRunning() or $queue === Kits::SUMO)
            return false;
        elseif ($queue === Kits::SPLEEF) {
            return $this->isRunning() and $break and $block->getId() === Block::SNOW_BLOCK;
        }

        $blocks = [
            Block::COBBLESTONE => true,
            Block::WOODEN_PLANKS => true,
        ];

        return isset($blocks[$block->getId()]);
    }


    /**
     * @return bool
     */
    public function cantDamagePlayers() : bool {
        return !$this->kit->canDamageOthers();
    }

    /**
     * @return string
     */
    public function getQueue() : string {
        return $this->queue;
    }

    /**
     * @param MineceitPlayer|string $player
     * @param float $damage
     */
    public function addHitTo($player, float $damage) : void {

        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;

        if(isset($this->numHits[$name]) and isset($this->replayData['player1'], $this->replayData['player2'])) {

            /* @var PlayerReplayData $playerReplayData */
            $playerReplayData = ($name === $this->player1Name) ? $this->replayData['player2'] : $this->replayData['player1'];
            $playerReplayData->setDamagedAt($this->currentTicks, $damage);

            $hits = $this->numHits[$name];
            $this->numHits[$name] = $hits + 1;
        }
    }

    /**
     * @param MineceitPlayer|string $player
     * @return bool
     */
    public function isSpectator($player) : bool {
        $name = $player instanceof MineceitPlayer ? $player->getName() : $player;
        $local = strtolower($name);
        return isset($this->spectators[$local]);
    }

    /**
     * @return string
     */
    public function getTexture() : string {
        if($this->kit !== null)
            return $this->kit->getTexture();
        return '';
    }

    /**
     * @return string
     */
    public function getP1Name() : string {
        return $this->player1Name;
    }

    /**
     * @return string
     */
    public function getP2Name() : string {
        return $this->player2Name;
    }

    /**
     * @return bool
     */
    public function isRanked() : bool {
        return $this->ranked;
    }

    /**
     * @return int
     */
    public function getWorldId() : int {
        return $this->worldId;
    }

    /**
     * @param MineceitDuel $duel
     * @return bool
     */
    public function equals(MineceitDuel $duel) : bool {
        return $duel->isRanked() === $this->ranked and $duel->getP1Name() === $this->player1Name
            and $duel->getP2Name() === $this->player2Name and $duel->getQueue() === $this->queue
            and $duel->getWorldId() === $this->worldId;
    }


    /**
     * @return bool
     *
     * Determines whether the duel is a spleef duel.
     */
    public function isSpleef() : bool {
        return strtolower($this->queue) === Kits::SPLEEF;
    }
}