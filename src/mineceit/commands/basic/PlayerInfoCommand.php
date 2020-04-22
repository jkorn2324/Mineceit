<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-17
 * Time: 13:34
 */

namespace mineceit\commands\basic;


use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PlayerInfoCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct("pInfo", "Gets the player's information.", "Usage: /pInfo <player>", ["pinfo", "playerInfo"]);
        parent::setPermission('permission.mineceit.pinfo');
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

        $msg = null;

        $playerHandler = MineceitCore::getPlayerHandler();

        $lang = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();

        $size = count($args);

        if($this->testPermission($sender)) {

            $use = true;

            if($sender instanceof MineceitPlayer)
                $use = $this->canUseCommand($sender);

            if($use) {

                if($size === 1) {

                    $name = (string)$args[0];

                    $server = Server::getInstance();

                    $p = $server->getPlayer($name);

                    if($p !== null and $p instanceof MineceitPlayer) {

                        $info = $playerHandler->listInfo($p, $lang);

                        $msg = implode("\n", $info);

                        $sender->sendMessage($msg);

                        return true;

                    } else $msg = $lang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]);

                } else $msg = $this->getUsage();
            }
        }

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return true;

    }


    public function testPermission(CommandSender $sender): bool
    {

        if($sender instanceof MineceitPlayer and $sender->hasModPermissions()) {
            return true;
        }

        return parent::testPermission($sender);
    }
}