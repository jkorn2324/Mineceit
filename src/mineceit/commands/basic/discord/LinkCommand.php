<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-29
 * Time: 15:22
 */

declare(strict_types=1);

namespace mineceit\commands\basic\discord;


use mineceit\commands\MineceitCommand;
use mineceit\discord\DiscordUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;

class LinkCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct("link", "Links your Minecraft server to your discord server.", "Usage: /link <code>");
        parent::setPermission('mineceit.permission.link');
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        $message = null;

        $playerHandler = MineceitCore::getPlayerHandler();

        $lang = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();

        if($this->testPermission($sender) and $this->canUseCommand($sender)) {

            $length = count($args);

            if($length === 1) {

                $code = $args[0];
                $length = strlen($code);

                if($length !== 8) {
                    // TODO INVALID DISCORD CODE
                } else {
                    DiscordUtil::sendServerLink($code);
                }

            } else $message = $this->getUsage();
        }

        if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . $message);

        return true;
    }
}