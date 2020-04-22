<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-29
 * Time: 12:12
 */

declare(strict_types=1);

namespace mineceit\commands\bans;


use mineceit\discord\DiscordUtil;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\BanIpCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;

class MineceitBanIPCommand extends BanIpCommand
{


    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) === 0){
            throw new InvalidCommandSyntaxException();
        }

        $value = array_shift($args);
        $reason = implode(" ", $args);

        if(preg_match("/^([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])$/", $value)){
            $this->processIPBan($value, $sender, $reason, true);
            Command::broadcastCommandMessage($sender, new TranslationContainer("commands.banip.success", [$value]));
        }else{
            if(($player = $sender->getServer()->getPlayer($value)) instanceof Player){
                $title = DiscordUtil::boldText("IP Ban");
                $name = $player->getName();
                $theReason = $reason !== "" ? $reason : "IP Banned";
                $senderName = $sender->getName();
                $description = DiscordUtil::boldText("User:") . " {$senderName} \n\n" . DiscordUtil::boldText("Banned:") . " {$name} \n" . DiscordUtil::boldText("Reason:") . " {$theReason}";
                DiscordUtil::sendLog($title, $description, DiscordUtil::DARK_RED);
                $this->processIPBan($player->getAddress(), $sender, $reason, false);
                Command::broadcastCommandMessage($sender, new TranslationContainer("commands.banip.success.players", [$player->getAddress(), $player->getName()]));
            }else{
                $sender->sendMessage(new TranslationContainer("commands.banip.invalid"));

                return false;
            }
        }

        return true;
    }

    private function processIPBan(string $ip, CommandSender $sender, string $reason, bool $sendEmbed = false){

        $sender->getServer()->getIPBans()->addBan($ip, $reason, null, $sender->getName());

        foreach($sender->getServer()->getOnlinePlayers() as $player){
            if($player->getAddress() === $ip){
                $player->kick($reason !== "" ? $reason : "IP banned.");
                if ($sendEmbed) {
                    $title = DiscordUtil::boldText("IP Ban");
                    $name = $player->getName();
                    $theReason = $reason !== "" ? $reason : "IP Banned";
                    $description = DiscordUtil::boldText("User:") . " {$name} \n" . DiscordUtil::boldText("Reason:") . " {$theReason}";
                    DiscordUtil::sendLog($title, $description, DiscordUtil::DARK_RED);
                }
            }
        }

        $sender->getServer()->getNetwork()->blockAddress($ip, -1);
    }

    public function testPermission(CommandSender $target): bool
    {
        if($target instanceof MineceitPlayer and $target->hasModPermissions()) {
            return true;
        }

        return parent::testPermission($target);
    }

}