<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-26
 * Time: 21:54
 */

declare(strict_types=1);

namespace mineceit\commands\bans;


use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\PardonCommand;

class MineceitPardonCommand extends PardonCommand
{

    public function testPermission(CommandSender $target): bool
    {
        if($target instanceof MineceitPlayer and $target->hasModPermissions()) {
            return true;
        }

        return parent::testPermission($target);
    }

}