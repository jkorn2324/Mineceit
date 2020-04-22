<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-24
 * Time: 17:57
 */

declare(strict_types=1);

namespace mineceit\commands\arenas;


use mineceit\arenas\FFAArena;
use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class SetArenaSpawn extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('arenaSpawn', "Updates an arena's spawn.", 'Usage: /arenaSpawn <name>', ['setarenaSpawn', 'arenaspawn', 'spawnarena', 'updatearena', 'arenaupdate']);
        parent::setPermission('mineceit.permission.setarenaspawn');
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

                if ($size === 1) {

                    $arenaName = strval($args[0]);

                    $arena = $arenaHandler->getArena($arenaName);

                    if ($arena !== null and $arena instanceof FFAArena) {

                        $arena->setSpawn($sender);
                        $arenaHandler->editArena($arena);
                        $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';

                    } else {

                        $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
                    }

                } else {

                    $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $this->getUsage();
                }
            }
        } else $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RED . "Console can't use this command.";

        if ($msg !== null) $sender->sendMessage($msg);

        return true;
    }
}