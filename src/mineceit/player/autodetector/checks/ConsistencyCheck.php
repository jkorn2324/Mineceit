<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-29
 * Time: 00:13
 */

declare(strict_types=1);

namespace mineceit\player\autodetector\checks;


use mineceit\MineceitCore;
use mineceit\player\autodetector\tasks\ConsistencyTask;
use mineceit\player\MineceitPlayer;

class ConsistencyCheck extends AbstractCheck
{

    /** @var int|float */
    private $lastTime;

    /** @var array */
    private $lastDifferences;

    /** @var int|float */
    private $lastTimeDifference;

    /** @var float|int */
    private $lastTargetYaw;

    /** @var MineceitPlayer */
    private $player;

    /** @var int */
    private $clicks;

    /** @var MineceitCore */
    private $core;

    /** @var int */
    private $consistent;

    /** @var float|int */
    private $startClickingTime;

    /** @var float|int */
    private $lastTimeClicked;

    /** @var array */
    private $cpsDifferences;

    /** @var int */
    private $clicksInASecond;

    public function __construct(MineceitPlayer $player)
    {
        parent::__construct();
        $this->player = $player;
        $this->lastTime = -1;
        $this->lastDifferences = [];
        $this->lastTimeDifference = -1;
        $this->lastTargetYaw = -1;
        $this->consistent = 0;
        $this->core = MineceitCore::getInstance();
        $this->clicks = 0;
        $this->clicksInASecond = 0;
        $this->startClickingTime = -1;
        $this->lastTimeClicked = -1;
        $this->cpsDifferences = [];
    }


    /**
     * Adds a click to the player.
     */
    public function addClick() : void {

        $currentTime = microtime(true) * 1000;

        if($this->startClickingTime === -1) {
            $this->startClickingTime = $currentTime;
        } elseif (!$this->isClicking()) {
            $this->clicks = 0;
            $this->startClickingTime = $currentTime;
        }

        $this->lastTimeClicked = $currentTime;

        $this->clicks++;

        if($this->clicksInASecond === 0) {
            $task = new ConsistencyTask($this);
            $this->core->getScheduler()->scheduleDelayedTask($task, 20);
        }

        $this->clicksInASecond++;
    }

    /**
     * @return bool
     *
     * Determines if the player is clicking or not.
     */
    private function isClicking() : bool {
        $ms = $this->timeSinceLastClick();
        return $ms < 500 and $this->startClickingTime > 0;
    }

    /**
     * @return float|int
     *
     * Gets the time clicked in MS.
     */
    private function timeSinceLastClick() {
        $currentTime = microtime(true) * 1000;
        $timeClicked = $currentTime - $this->lastTimeClicked;
        return $timeClicked;
    }


    /**
     * @return float|int
     *
     * Determines the total time clicked.
     */
    private function timeClicked() {
        return $this->lastTimeClicked - $this->startClickingTime;
    }


    /**
     * Resets all clicks.
     */
    public function resetClicks() : void {
        var_dump($this->clicksInASecond);
        // var_dump($this->getTotalCps());
        $this->clicksInASecond = 0;
    }

    /**
     * @return float
     *
     * Gets the total cps.
     */
    private function getTotalCps() : float {
        $currentTime = microtime(true) * 1000;
        $timeClicked = $currentTime - $this->startClickingTime;
        $result = (float)(($this->clicks / $timeClicked) * 1000);
        if($result > 1000) {
            $result /= 1000;
        }
        return $result;
    }

    /**
     * @return MineceitPlayer|null
     */
    public function getPlayer() {
        return $this->player;
    }

    /**
     * @param MineceitPlayer $target
     * @param float|int $currentTime
     *
     * Checks the consistency. -> Based on the autokiller plugin.
     */
    public function checkClick(MineceitPlayer $target, $currentTime) : void {

        $location = $target->getLocation();
        $yaw = $location->getYaw();
        // $ping = $this->getPlayer()->getPing();

        if($this->isClicking()) {
            $totalCps = $this->getTotalCps();
            $cpsInt = intval($totalCps);
            $difference = $totalCps - $cpsInt;
            //var_dump($difference);
            // var_dump($this->timeClicked());
        }

        if($this->lastTargetYaw === $yaw) {
            return;
        }

        $this->lastTargetYaw = $yaw;

        if($this->lastTime === -1) {
            $this->lastTime = $currentTime * 1000;
        }

        if($this->lastTimeDifference === -1) {
            $this->lastTimeDifference = 200;
        }

        $currentTimeDifference = ($currentTime * 1000) - $this->lastTime;

        $size = count($this->lastDifferences);

        if($size < self::CONSISTENT_CLICK_SPEED_SENSITIVITY) {
            $differences = abs($currentTimeDifference - $this->lastTimeDifference);
            if($differences > 200) {
                $this->lastDifferences[] = 70;
            } else {
                $this->lastDifferences[] = $currentTimeDifference - $this->lastTimeDifference;
            }
        } else {
            unset($this->lastDifferences[0]);
            $differences = abs($currentTimeDifference - $this->lastTimeDifference);
            if($differences > 200) {
                $this->lastDifferences[] = 70;
            } else {
                $this->lastDifferences[] = $currentTimeDifference - $this->lastTimeDifference;
            }
        }

        $this->lastDifferences = array_values($this->lastDifferences);

        /* var_dump($currentTimeDifference);
        var_dump($this->lastTimeDifference);
        var_dump($this->lastDifferences); */

        $this->lastTimeDifference = $currentTimeDifference;
        $this->lastTime = ($currentTime * 1000);

        // var_dump($this->getTotalCps());

        /* $absSequence = 0;
        $largestValue = -1; $smallestValue = -1;

        $numberTracker = []; */

        foreach($this->lastDifferences as $difference) {

            $absDifference = abs($difference);

            if($absDifference > 70) {
                return;
            }

            /* $absSequence += $absDifference;
            if($largestValue === -1 and $smallestValue === -1) {
                $largestValue = $absDifference;
                $smallestValue = $absDifference;
            } else {
                if($absDifference > $largestValue) {
                    $largestValue = $absDifference;
                } elseif ($absDifference < $smallestValue) {
                    $smallestValue = $absDifference;
                }
            }

            if(!isset($numberTracker[$absDifference])) {
                $numberTracker[$absDifference] = 0;
            } else {
                $numberTracker[$absDifference] += 1;
            } */
        }

        $clickInfo = $this->player->getClicksInfo();
        $cps = $clickInfo->getCps();

        if($cps > 8) {
            // var_dump("Increased the violations.");
            $this->increaseViolation();
        }

        /* $average = intval($absSequence / count($this->lastDifferences));
        $range = ($largestValue - $smallestValue) / 2;

        if($range <= 30 and $average <= 30) {
            $this->increaseViolation();
            return;
        }

        foreach($numberTracker as $number => $count) {
            if($count >= 5) {
                $this->increaseViolation();
                return;
            }
        } */
    }

    /**
     * Sends an alert.
     */
    public function sendAlert(): void {}
}