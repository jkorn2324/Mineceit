<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-03
 * Time: 15:27
 */

declare(strict_types=1);

namespace mineceit\events\duels;

use mineceit\arenas\EventArena;
use mineceit\events\MineceitEvent;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\utils\TextFormat;

class MineceitEventDuel
{

    const STATUS_STARTING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_ENDING = 2;
    const STATUS_ENDED = 3;

    /** @var MineceitPlayer */
    private $p1;
    /** @var string */
    private $p1Name;

    /** @var MineceitPlayer */
    private $p2;
    /** @var string */
    private $p2Name;

    /** @var int */
    private $currentTick;

    /** @var int */
    private $countdownSeconds;

    /** @var int */
    private $durationSeconds;

    /** @var MineceitEvent */
    private $event;

    /** @var int */
    private $status;

    /** @var string|null */
    private $winner;

    /** @var string|null */
    private $loser;

    /** @var int */
    private $endingSeconds;

    public function __construct(MineceitPlayer $p1, MineceitPlayer $p2, MineceitEvent $event)
    {
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->currentTick = 0;
        $this->durationSeconds = 0;
        $this->countdownSeconds = 5;
        $this->event = $event;
        $this->status = self::STATUS_STARTING;
        $this->p1Name = $p1->getName();
        $this->p2Name = $p2->getName();
        $this->endingSeconds = 1;
    }

    /**
     * Sets the players in a duel/
     */
    protected function setPlayersInDuel() : void {

        $arena = $this->event->getArena();

        $this->p1->setGamemode(0);
        $this->p2->setGamemode(0);

        $this->p1->setPlayerFlying(false);
        $this->p2->setPlayerFlying(false);

        $this->p1->setImmobile();
        $this->p2->setImmobile();

        $this->p1->clearInventory();
        $this->p2->clearInventory();

        $arena->teleportPlayer($this->p1, EventArena::P1);
        $arena->teleportPlayer($this->p2, EventArena::P2);

        $this->p1->setDuelNameTag();
        $this->p2->setDuelNameTag();

        $p1Lang = $this->p1->getLanguage();
        $p2Lang = $this->p2->getLanguage();

        $p1Message = $p1Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p2Name]);
        $p2Message = $p2Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p1Name]);

        $this->p1->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p1Message);
        $this->p2->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p2Message);
    }

    /**
     * Updates the duel.
     */
    public function update() : void {

        $this->currentTick++;

        $checkSeconds = $this->currentTick % 20 === 0;

        if(!$this->p1->isOnline() or !$this->p2->isOnline()) {

            if($this->p1->isOnline()) {

                $this->winner = $this->p1Name;
                $this->loser = $this->p2Name;

                if($this->status !== self::STATUS_ENDED) {

                    $this->p1->clearKit();
                    $this->p1->reset(true, false);
                    $arena = $this->event->getArena();
                    $arena->teleportPlayer($this->p1);
                    $this->p1->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                    $this->p1->setSpawnNameTag();
                }

            } elseif ($this->p2->isOnline()) {

                $this->winner = $this->p1Name;
                $this->loser = $this->p2Name;

                if($this->status !== self::STATUS_ENDED) {

                    $this->p2->clearKit();
                    $this->p2->reset(true, false);
                    $arena = $this->event->getArena();
                    $arena->teleportPlayer($this->p2);
                    $this->p2->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                    $this->p2->setSpawnNameTag();
                }
            }

            $this->status = self::STATUS_ENDED;
            return;
        }

        $p1Lang = $this->p1->getLanguage();
        $p2Lang = $this->p2->getLanguage();

        if($this->status === self::STATUS_STARTING) {

            if($this->currentTick === 4) {
                $this->setPlayersInDuel();
            }

            if($checkSeconds) {

                $p1Sb = $this->p1->getScoreboardType();
                $p2Sb = $this->p2->getScoreboardType();

                if($p1Sb !== Scoreboard::SCOREBOARD_NONE and $p1Sb !== Scoreboard::SCOREBOARD_EVENT_DUEL) {
                    $this->p1->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
                }

                if($p2Sb !== Scoreboard::SCOREBOARD_NONE and $p2Sb !== Scoreboard::SCOREBOARD_EVENT_DUEL) {
                    $this->p2->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
                }

                // Countdown messages.
                if ($this->countdownSeconds === 5) {

                    $p1Msg = $this->getCountdownMessage(true, $p1Lang, $this->countdownSeconds);
                    $p2Msg = $this->getCountdownMessage(true, $p2Lang, $this->countdownSeconds);
                    $this->p1->sendTitle($p1Msg, '', 5, 20, 5);
                    $this->p2->sendTitle($p2Msg, '', 5, 20, 5);

                } elseif ($this->countdownSeconds !== 0) {

                    $p1Msg = $this->getJustCountdown($p1Lang, $this->countdownSeconds);
                    $p2Msg = $this->getJustCountdown($p2Lang, $this->countdownSeconds);
                    $this->p1->sendTitle($p1Msg, '', 5, 20, 5);
                    $this->p2->sendTitle($p2Msg, '', 5, 20, 5);

                } else {

                    $p1Msg = $p1Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
                    $p2Msg = $p2Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
                    $this->p1->sendTitle($p1Msg, '', 5, 10, 5);
                    $this->p2->sendTitle($p2Msg, '', 5, 10, 5);
                }

                if($this->countdownSeconds === 0) {
                    $this->status = self::STATUS_IN_PROGRESS;
                    $this->p1->setImmobile(false);
                    $this->p2->setImmobile(false);
                    return;
                }

                $this->countdownSeconds--;
            }

        } elseif ($this->status === self::STATUS_IN_PROGRESS) {

            $arena = $this->event->getArena();

            $centerMinY = $arena->getCenter()->getY() - 4;

            $p1Pos = $this->p1->getPosition();
            $p2Pos = $this->p2->getPosition();

            $p1Y = $p1Pos->getY();
            $p2Y = $p2Pos->getY();

            if($this->event->getType() === MineceitEvent::TYPE_SUMO) {

                if($p1Y < $centerMinY) {
                    $this->winner = $this->p2Name;
                    $this->loser = $this->p1Name;
                    $this->status = self::STATUS_ENDING;
                    return;
                }

                if($p2Y < $centerMinY) {
                    $this->winner = $this->p1Name;
                    $this->loser = $this->p2Name;
                    $this->status = self::STATUS_ENDING;
                    return;
                }
            }

            // Used for updating scoreboards.
            if($checkSeconds) {

                $p1Duration = TextFormat::WHITE . $p1Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                $p2Duration = TextFormat::WHITE . $p2Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);

                $p1DurationStr = TextFormat::WHITE . ' ' . $p1Duration . ': ' . $this->getDuration();
                $p2DurationStr = TextFormat::WHITE . ' ' . $p2Duration . ': ' . $this->getDuration();

                if($p1Lang->getLocale() === Language::ARABIC)
                    $p1DurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $p1Duration;

                if($p2Lang->getLocale() === Language::ARABIC)
                    $p2DurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $p2Duration;

                $this->p1->updateLineOfScoreboard(1, $p1DurationStr);
                $this->p2->updateLineOfScoreboard(1, $p2DurationStr);

                // Add a maximum duel duration if necessary.

                $this->durationSeconds++;
            }

        } elseif ($this->status === self::STATUS_ENDING) {

            if ($checkSeconds and $this->endingSeconds > 0) {

                $this->endingSeconds--;

                if ($this->endingSeconds === 0) {

                    if ($this->p1->isOnline()) {
                        $this->p1->clearKit();
                        $this->p1->reset(true, false);
                        $arena = $this->event->getArena();
                        $arena->teleportPlayer($this->p1);
                        $this->p1->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                        $this->p1->setSpawnNameTag();
                    }

                    if ($this->p2->isOnline()) {
                        $this->p2->clearKit();
                        $this->p2->reset(true, false);
                        $arena = $this->event->getArena();
                        $arena->teleportPlayer($this->p2);
                        $this->p2->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                        $this->p2->setSpawnNameTag();
                    }

                    $this->status = self::STATUS_ENDED;
                }
            }
        }
    }


    /**
     * @return string
     *
     * Gets the duration of the duel for scoreboard;
     */
    public function getDuration(): string
    {

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

    /**
     *
     * @param MineceitPlayer $player
     *
     * @return MineceitPlayer|null
     */
    public function getOpponent(MineceitPlayer $player)
    {
        if($this->p1->equalsPlayer($player)) {
            return $this->p2;
        } elseif ($this->p2->equalsPlayer($player)) {
            return $this->p1;
        }

        return null;
    }

    /**
     * @param MineceitPlayer $player
     * @return bool
     */
    public function isPlayer(MineceitPlayer $player): bool
    {
        return $this->p1->equalsPlayer($player) or $this->p2->equalsPlayer($player);
    }

    /**
     * @return int
     *
     * Gets the status of the duel.
     */
    public function getStatus() : int {
        return $this->status;
    }

    /**
     * @return array
     *
     * Gets the results of the duel.
     */
    public function getResults() : array {
        return ['winner' => $this->winner, 'loser' => $this->loser];
    }


    /**
     * @param MineceitPlayer|null $winner
     */
    public function setResults(MineceitPlayer $winner = null) : void
    {

        if ($winner !== null) {

            $name = $winner->getName();

            if ($this->isPlayer($winner)) {
                $loser = $name === $this->p1Name ? $this->p2Name : $this->p1Name;
                $this->winner = $name;
                $this->loser = $loser;
            }
        }

        $this->status = self::STATUS_ENDING;
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
}