<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-24
 * Time: 16:46
 */

declare(strict_types=1);

namespace mineceit\commands\ranks;


use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class ListRanks extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('listranks', 'List all of the ranks on the server.', 'Usage: /listRanks', ['rank-list', 'ranklist'], true);
        parent::setPermission('mineceit.permission.listranks');
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

        $rankHandler = MineceitCore::getRankHandler();

        $language = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();

        if ($this->testPermission($sender) and $this->canUseCommand($sender)) {
            $ranks = $rankHandler->listRanks();
            $msg = $language->listMessage(Language::LIST_RANKS, $ranks);
        }

        if($msg !== null) $sender->sendMessage($msg);

    }
}