<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-23
 * Time: 16:28
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

class DeleteArena extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('deleteArena', 'Deletes an arena.', 'Usage: /deleteArena <name> <kit>', ['deletearena', 'arena-delete']);
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

        if($sender instanceof MineceitPlayer) {

            $p = $sender->getPlayer();

            $language = $p->getLanguage();

            if ($this->testPermission($sender) and $this->canUseCommand($sender)) {

                $size = count($args);

                if ($size === 1) {

                    $arenaName = $args[0];

                    $arena = $arenaHandler->getArena($arenaName);

                    if($arena !== null and $arena instanceof FFAArena) {

                        $arenaName = $arena->getName();

                        $arenaHandler->deleteArena($arenaName);

                        $message = $language->arenaMessage(Language::DELETE_ARENA, $arenaName);

                    } else $message = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);

                    if(isset($message) and $message !== null) {
                        $sender->sendMessage($message);
                        return true;
                    }

                } else $msg = $this->getUsage();
            }
        } else $msg = TextFormat::RED . "Console can't use this command.";

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return true;
    }
}