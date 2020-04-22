<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-29
 * Time: 12:11
 */

declare(strict_types=1);

namespace mineceit\commands\bans;


use mineceit\discord\DiscordUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\BanCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;

class MineceitBanCommand extends BanCommand
{


    public function execute(CommandSender $sender, string $commandLabel, array $args){

        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) === 0){
            throw new InvalidCommandSyntaxException();
        }

        $name = array_shift($args);
        $reason = implode(" ", $args);

        $sender->getServer()->getNameBans()->addBan($name, $reason, null, $sender->getName());

        $theReason = "Banned by admin.";
        if($reason !== "") {
            $theReason .= " Reason: {$reason}";
        }

        if(($player = MineceitUtil::getPlayerExact($name)) instanceof Player){
            $player->kick($theReason);
        }

        $senderName = $sender->getName();

        $title = DiscordUtil::boldText("Ban");
        $description = DiscordUtil::boldText("User:") . " {$senderName} \n\n" . DiscordUtil::boldText("Banned:") . " {$name}" . "\n" . DiscordUtil::boldText("Reason:") . " {$theReason}" . "\n";
        DiscordUtil::sendLog($title, $description, DiscordUtil::RED);

        Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.ban.success", [$player !== null ? $player->getName() : $name]));

        return true;
    }


    public function testPermission(CommandSender $target): bool
    {
        if($target instanceof MineceitPlayer and $target->hasModPermissions()) {
            return true;
        }

        return parent::testPermission($target);
    }

}