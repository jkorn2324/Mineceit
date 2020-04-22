<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-03
 * Time: 17:05
 */

declare(strict_types=1);

namespace mineceit\commands\types;


use pocketmine\command\CommandSender;

class MineceitCommandParameter
{

    /** @var callable */
    private $callable;

    /** @var string */
    private $name;

    public function __construct(string $name, callable $callable)
    {
        $this->callable = $callable;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @param CommandSender $sender
     * @param array $args
     *
     * Executes the command.
     */
    public function execute(CommandSender $sender, array $args) : void {
        $callable = $this->callable;
        $callable($sender, $args);
    }
}