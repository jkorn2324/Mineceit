<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-19
 * Time: 11:44
 */

declare(strict_types=1);

namespace mineceit\network\requests;


use mineceit\player\MineceitPlayer;
use Closure;
use pocketmine\utils\Binary;

class Request
{

    const TYPE_PING = 0;

    /** @var string */
    private $uuid;

    /** @var int */
    private $type;

    /** @var string */
    private $buffer;

    /** @var Closure */
    private $function;

    /** @var int */
    private $offset;

    /** @var string */
    private $eventData;

    /** @var array */
    private $data;

    public function __construct(int $type, string $buffer, MineceitPlayer $player, Closure $closure)
    {
        $this->uuid = $player->getUniqueId()->toString();
        $this->type = $type;
        $this->buffer = $buffer;
        $this->function = $closure;
        $this->eventData = "";
        $this->offset = 0;
        $this->data = [];
    }

    /**
     * @param string $eventData
     *
     * Updates the event data.
     */
    public function update(string $eventData) : void {

        $this->eventData = $eventData;

        switch($this->type) {

            case self::TYPE_PING:
                $this->data["ping"] = $this->readInt();
                break;
        }

        $function = $this->function;
        $function($this->data, $this->uuid);
    }


    /**
     *
     * Reads an integer value.
     *
     * @return int|null
     */
    private function readInt() {

        $offset = $this->offset;

        try {
            $this->offset += 4;
            return Binary::readInt(substr($this->eventData, $offset, 4));
        } catch (\Exception $e) {
            return null;
        }
    }
}