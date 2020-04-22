<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-02
 * Time: 16:34
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports\data;


use mineceit\player\MineceitPlayer;

class HackReport extends ReportInfo
{

    /** @var string */
    private $reported;

    /** @var string */
    private $reason;

    /**
     * HackReport constructor.
     * @param MineceitPlayer|string $reporter
     * @param MineceitPlayer|string $reported
     * @param string $reason
     * @param int|null $time
     * @param bool $resolved
     */
    public function __construct($reporter, $reported, string $reason = "", ?int $time = null, bool $resolved = false)
    {
        parent::__construct(self::TYPE_HACK, $reporter, $time, $resolved);
        $this->reported = $reported instanceof MineceitPlayer ? $reported->getName() : $reported;
        $this->reason = $reason;
    }

    /**
     * @return string
     *
     * Gets the reason.
     */
    public function getReason() : string {
        return $this->reason;
    }

    /**
     * @return string
     *
     * Gets the reported player.
     */
    public function getReported() : string {
        return $this->reported;
    }

    /**
     * @return array
     *
     * Encodes the data to a csv format.
     */
    public function csv_encode(): array
    {
        return [
            $this->type,
            $this->reporter,
            $this->reported,
            $this->reason,
            $this->getTimestamp(),
            $this->resolved
        ];
    }

    /**
     * @return array
     *
     * Serializes so that we can use it to compare.
     */
    public function serialize(): array
    {
        return [
            'timestamp' => $this->getTimestamp(),
            'reporter' => $this->getReporter(),
            'reported' => $this->reported,
            'type' => $this->type,
            'local' => $this->getLocalName(),
            'resolved' => $this->resolved
        ];
    }
}