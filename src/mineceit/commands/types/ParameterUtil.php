<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-03
 * Time: 17:06
 */

declare(strict_types=1);

namespace mineceit\commands\types;


use mineceit\arenas\EventArena;
use mineceit\commands\arenas\EventArena as CommandEventArena;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ParameterUtil
{

    // TODO DO THE LANGUAGE FOR SUCCESSFULLY EDITED ARENA

    /**
     * @return array|MineceitCommandParameter[]
     *
     * Initializes and gets the event arena command parameters.
     */
    public static function getEventArenaParameters() {

        $createParameter = new MineceitCommandParameter("create", function(CommandSender $sender, array $args) {

            $length = count($args);

            $msg = null;

            $usage = "Usage: /event create <name> <kit>";

            if($sender instanceof MineceitPlayer) {

                $language = $sender->getLanguage();

                if ($length === 2) {

                    $arenaName = strval($args[0]);

                    $kitName = strval($args[1]);

                    $kitHandler = MineceitCore::getKits();
                    $arenaHandler = MineceitCore::getArenas();

                    if ($kitHandler->isKit($kitName)) {

                        $kit = $kitHandler->getKit($kitName);

                        $arena = $arenaHandler->getArena($arenaName);

                        if ($arena !== null) {

                            $msg = $language->arenaMessage(Language::ARENA_EXISTS, $arena);

                        } else {

                            $arenaHandler->createArena($arenaName, $kit->getLocalizedName(), $sender->getPlayer(), true);
                            $msg = $language->arenaMessage(Language::CREATE_ARENA, $arenaName);
                        }

                    } else $msg = $language->kitMessage($kitName, Language::KIT_NO_EXIST);

                } else {
                    $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
                }
            } else {
                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
            }

            if($msg !== null) {
                $sender->sendMessage($msg);
            }

        });


        $deleteParameter = new MineceitCommandParameter('delete', function(CommandSender $sender, array $args) {

            $length = count($args);

            $msg = null;

            $usage = "Usage: /event delete <name>";

            if($sender instanceof MineceitPlayer) {

                $arenaHandler = MineceitCore::getArenas();

                $language = $sender->getLanguage();

                if ($length === 1) {

                    $arenaName = $args[0];

                    $arena = $arenaHandler->getArena($arenaName);

                    if ($arena !== null and $arena instanceof EventArena) {

                        $arenaName = $arena->getName();

                        $arenaHandler->deleteArena($arenaName);

                        $msg = $language->arenaMessage(Language::DELETE_ARENA, $arenaName);

                    } else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);

                } else {
                    $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
                }
            } else {
                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
            }

            if($msg !== null) {
                $sender->sendMessage($msg);
            }
        });


        $pos1Parameter = new MineceitCommandParameter('p1start', function(CommandSender $sender, array $args) {

            $length = count($args);

            $msg = null;

            $usage = "Usage: /event p1Start <name>";

            if($sender instanceof MineceitPlayer) {

                $arenaHandler = MineceitCore::getArenas();

                $language = $sender->getLanguage();

                if ($length === 1) {

                    $arenaName = $args[0];

                    $arena = $arenaHandler->getArena($arenaName);

                    if ($arena !== null and $arena instanceof EventArena) {

                        $arena->setP1SpawnPos($sender->asVector3());

                        $arenaHandler->editArena($arena);

                        $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';
                        // TODO LANG

                    } else {
                        $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
                    }

                } else {
                    $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
                }
            } else {
                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
            }

            if($msg !== null) {
                $sender->sendMessage($msg);
            }
        });


        $pos2Parameter = new MineceitCommandParameter('p2start', function(CommandSender $sender, array $args) {

            $length = count($args);

            $msg = null;

            $usage = "Usage: /event p2Start <name>";

            if($sender instanceof MineceitPlayer) {

                $arenaHandler = MineceitCore::getArenas();

                $language = $sender->getLanguage();

                if ($length === 1) {

                    $arenaName = $args[0];

                    $arena = $arenaHandler->getArena($arenaName);

                    if ($arena !== null and $arena instanceof EventArena) {

                        $arena->setP2SpawnPos($sender->asVector3());

                        $arenaHandler->editArena($arena);

                        $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';

                        // TODO LANG

                    } else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);

                } else {
                    $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
                }
            } else {
                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
            }

            if($msg !== null) {
                $sender->sendMessage($msg);
            }
        });


        $spawnParameter = new MineceitCommandParameter('spawn', function(CommandSender $sender, array $args) {

            $length = count($args);

            $msg = null;

            $usage = "Usage: /event spawn <name>";

            if($sender instanceof MineceitPlayer) {

                $arenaHandler = MineceitCore::getArenas();

                $language = $sender->getLanguage();

                if ($length === 1) {

                    $arenaName = $args[0];

                    $arena = $arenaHandler->getArena($arenaName);

                    if ($arena !== null and $arena instanceof EventArena) {

                        $arena->setSpawn($sender->asVector3());

                        $arenaHandler->editArena($arena);

                        $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';

                        // TODO LANG

                    } else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);

                } else {
                    $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
                }
            } else {
                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
            }

            if($msg !== null) {
                $sender->sendMessage($msg);
            }
        });

        return [
            $createParameter->getName() => $createParameter,
            $deleteParameter->getName() => $deleteParameter,
            $pos1Parameter->getName() => $pos1Parameter,
            $pos2Parameter->getName() => $pos2Parameter,
            $spawnParameter->getName() => $spawnParameter
        ];
    }

}