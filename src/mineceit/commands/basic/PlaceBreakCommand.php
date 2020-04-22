<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-27
 * Time: 21:45
 */

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PlaceBreakCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('pb-Perms', 'Gives place and break perms to a certain player.', 'Usage: /pb-perms <player>', ['pbPerms', 'pb-perms']);
        parent::setPermission('mineceit.permissions.place-break');
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {

        $msg = null;

        $playerHandler = MineceitCore::getPlayerHandler();

        $size = count($args);

        if($this->testPermission($sender)) {

            $use = true;

            if($sender instanceof MineceitPlayer)
                $use = $this->canUseCommand($sender);

            if($use) {

                if($size === 1) {
                    $name = strval($args[0]);
                    $server = Server::getInstance();
                    $player = $server->getPlayer($name);
                    if($player !== null and $player instanceof MineceitPlayer) {
                        // $player->setPermission(MineceitPlayer::PERMISSION_BUILDER_MODE, true);
                    } else {
                        $language = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();
                        $msg = $language->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]);
                    }
                } else $msg = $this->getUsage();
            }
        }

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return true;
    }
}