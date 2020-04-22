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

class BugReport extends ReportInfo
{

    /** @var string */
    private $occurrence;

    /** @var string */
    private $description;

    /**
     * BugReport constructor.
     * @param MineceitPlayer|string $reporter
     * @param string $description
     * @param string $occurrence
     * @param int|null $time
     * @param bool $resolved
     */
    public function __construct($reporter, string $description, string $occurrence, ?int $time = null, bool $resolved = false)
    {
        parent::__construct(self::TYPE_BUG, $reporter, $time, $resolved);
        $this->occurrence = $occurrence;
        $this->description = $description;
    }

    /**
     * @return string
     *
     * Gets the bug description
     */
    public function getDescription() : string {
        return $this->description;
    }

    /**
     * @return string
     *
     * Gets the reduce info.
     */
    public function getReproduceInfo() : string {
        return $this->occurrence;
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
            $this->description,
            $this->occurrence,
            $this->time,
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
            'type' => $this->type,
            'local' => $this->getLocalName(),
            'resolved' => $this->resolved
        ];
    }
}