<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-28
 * Time: 23:24
 */

declare(strict_types=1);

namespace mineceit\player\autodetector\checks;


abstract class AbstractCheck
{

    const MAX_VIOLATIONS_BAN_CLICK_SPEED = 30;
    const MAX_VIOLATIONS_BAN_DIGGING = 30;
    const MAX_VIOLATIONS_BAN_CONSISTENCY = 30;

    const MAX_VIOLATIONS_ALERT = 10;

    const CONSISTENT_CLICK_SPEED_SENSITIVITY = 16;

    const MAX_CLICK_SPEED = 18;
    const MAX_CLICK_SPEED_CONSISTENCY = 6;

    /** @var int|float */
    protected $disableTime;

    /** @var int */
    protected $violations;

    /** @var int */
    private $lastViolation;

    public function __construct()
    {
        $this->disableTime = 0;
        $this->violations = 0;
        $this->lastViolation = 0;
    }

    /**
     * Increases the amount of violations the player has.
     */
    public function increaseViolation() : void {
        $this->lastViolation = $this->violations;
        $this->violations++;
    }

    /**
     * @return int
     *
     * Returns the number of violations.
     */
    public function getViolationCount() : int {
        return $this->violations;
    }


    /**
     * @return int
     *
     * Gets the last violation of the player.
     */
    public function getLastViolation() : int {
        return $this->lastViolation;
    }


    /**
     * Clears all of the violations.
     */
    public function clearViolations() : void {
        $this->violations = 0;
        $this->lastViolation = 0;
    }


    /**
     * @param int $num
     *
     * Decreases the violation count.
     */
    public function decreaseViolation(int $num = 1) : void {
        $this->lastViolation = $this->violations;
        $this->violations -= $num;
        if($this->violations < 0) {
            $this->violations =0;
        }
    }


    /**
     *
     * @param int $amount
     *
     * Sets the player as disabled.
     */
    public function setDisabled(int $amount = 1500) : void {
        $milliseconds = round(microtime(true) * 1000);
        $this->disableTime = $milliseconds + $amount;
    }


    /**
     * @return bool
     *
     * Determines if the player is disabled.
     */
    public function isDisabled() : bool {
        $currentTime = round(microtime(true) * 1000);
        return $this->disableTime > $currentTime;
    }


    /**
     * Sends an alert.
     */
    abstract public function sendAlert() : void;
}