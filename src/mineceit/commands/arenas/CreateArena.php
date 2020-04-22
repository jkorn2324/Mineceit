<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-23
 * Time: 16:28
 */

declare(strict_types=1);

namespace mineceit\commands\arenas;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class CreateArena extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('createArena', 'Creates an arena with a specified kit.', 'Usage: /createArena <name> <kit>', ['createarena', 'arena-create']);
        parent::setPermission('mineceit.permission.toggle-arenas');
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

        $arenaHandler = MineceitCore::getArenas();

        if ($sender instanceof MineceitPlayer) {

            $p = $sender->getPlayer();

            $language = $p->getLanguage();

            if ($this->testPermission($sender) and $this->canUseCommand($sender)) {

                $size = count($args);

                if ($size === 2) {

                    $arenaName = strval($args[0]);

                    $kitName = strval($args[1]);

                    $kitHandler = MineceitCore::getKits();

                    if ($kitHandler->isKit($kitName)) {

                        $kit = $kitHandler->getKit($kitName);

                        $arena = $arenaHandler->getArena($arenaName);

                        if ($arena !== null) {

                            $msg = $language->arenaMessage(Language::ARENA_EXISTS, $arena);

                        } else {

                            $arenaHandler->createArena($arenaName, $kit->getLocalizedName(), $p);
                            $msg = $language->arenaMessage(Language::CREATE_ARENA, $arenaName);
                        }

                    } else $msg = $language->kitMessage($kitName, Language::KIT_NO_EXIST);
                } else $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $this->getUsage();
            }
        } else $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RED . "Console can't use this command.";

        if ($msg !== null) $sender->sendMessage($msg);

        return true;
    }
}