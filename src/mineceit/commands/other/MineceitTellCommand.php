<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-29
 * Time: 12:41
 */

declare(strict_types=1);

namespace mineceit\commands\other;


use mineceit\MineceitCore;
use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\TellCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\utils\TextFormat;

class MineceitTellCommand extends TellCommand
{

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) < 2){
            throw new InvalidCommandSyntaxException();
        }

        $player = $sender->getServer()->getPlayer(array_shift($args));

        if($player === $sender){
            $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.message.sameTarget"));
            return true;
        }

        if($player instanceof MineceitPlayer){

            $message = implode(" ", $args);

            $senderName = $sender instanceof MineceitPlayer ? $sender->getDisplayName() : $sender->getName();
            $senderLanguage = $sender instanceof MineceitPlayer ? $sender->getLanguage() : MineceitCore::getPlayerHandler()->getLanguage();

            $format = "[{$senderName} -> {$player->getDisplayName()}] ";

            $sender->sendMessage($format . $message);

            if($player->doesTranslateMessages()) {
                TranslateUtil::client5_translate($message, $player, $senderLanguage, $format);
            } else {
                $player->sendMessage($format . $message);
            }

        }else{

            $sender->sendMessage(new TranslationContainer("commands.generic.player.notFound"));

        }

        return true;
    }

}