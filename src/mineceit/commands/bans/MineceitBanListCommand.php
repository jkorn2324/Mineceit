<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-26
 * Time: 21:52
 */

declare(strict_types=1);

namespace mineceit\commands\bans;


use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\BanListCommand;

class MineceitBanListCommand extends BanListCommand
{


    public function testPermission(CommandSender $target): bool
    {
        if($target instanceof MineceitPlayer and $target->hasModPermissions()) {
            return true;
        }

        return parent::testPermission($target);
    }

}