<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-29
 * Time: 12:58
 */

declare(strict_types=1);

namespace mineceit\commands\other;


use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\MeCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\utils\TextFormat;

class MineceitMeCommand extends MeCommand
{

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) === 0){
            throw new InvalidCommandSyntaxException();
        }

        $message = implode(" ", $args);

        $container = new TranslationContainer("chat.type.emote", [$sender instanceof MineceitPlayer ? $sender->getDisplayName() : $sender->getName(), $message]);

        MineceitUtil::broadcastTranslatedMessage($sender, TextFormat::WHITE, $container, null, 1);

        return true;
    }

}