<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-16
 * Time: 01:04
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

class EnableTagCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct("tag", "Gives the specified player permission to give themselves a tag.", "Usage: /tag <enable:disable> <player>", []);
        parent::setPermission('mineceit.permission.tag');
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

        $size = count($args);

        if($this->testPermission($sender)) {

            $use = true;

            if($sender instanceof MineceitPlayer)
                $use = $this->canUseCommand($sender);

            if($use) {

                $sendUsage = true;

                if($size === 2) {

                    $type = strval($args[0]);

                    if($type === 'enable' or $type === 'disable') {
                        $sendUsage = false;
                        $enable = $type === 'enable' ? true : false;
                        $name = strval($args[1]);
                        $server = Server::getInstance();
                        $player = $server->getPlayer($name);
                        if ($player !== null and $player instanceof MineceitPlayer) {

                            $eng = $playerHandler->getLanguage();

                            $senderLang = ($sender instanceof MineceitPlayer) ? $sender->getLanguage() : $eng;
                            $receivedLang = $player->getLanguage();

                            if($enable) {

                                $msg = $senderLang->generalMessage(Language::SET_TAG_PERMISSION_SENDER, [
                                    "name" => $player->getDisplayName()
                                ]);

                                if($sender instanceof MineceitPlayer and $player->equalsPlayer($sender->getPlayer()))
                                    $msg = $senderLang->generalMessage(Language::SET_TAG_PERMISSION_RECEIVER);
                                else {
                                    $receivedMsg = $receivedLang->generalMessage(Language::SET_TAG_PERMISSION_RECEIVER);
                                    $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $receivedMsg);
                                }
                            } else {

                                $msg = $senderLang->generalMessage(Language::REMOVE_TAG_PERMISSION_SENDER, [
                                    "name" => $player->getDisplayName()
                                ]);

                                if ($sender instanceof MineceitPlayer and $player->equalsPlayer($sender->getPlayer()))
                                    $msg = $senderLang->generalMessage(Language::REMOVE_TAG_PERMISSION_RECEIVER);
                                else {
                                    $receivedMsg = $receivedLang->generalMessage(Language::REMOVE_TAG_PERMISSION_RECEIVER);
                                    $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $receivedMsg);
                                }
                            }

                            // $player->setPermission(MineceitPlayer::PERMISSION_TAG, $enable);

                        } else {

                            $language = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();

                            $msg = $language->generalMessage(Language::PLAYER_NOT_ONLINE, [
                                "name" => $player->getDisplayName()
                            ]);
                        }
                    }
                }

                if($sendUsage) $msg = $this->getUsage();
            }
        }

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return true;
    }


    public function testPermission(CommandSender $sender): bool
    {

        if($sender instanceof MineceitPlayer and $sender->hasAdminPermissions()) {
            return true;
        }

        return parent::testPermission($sender);
    }
}