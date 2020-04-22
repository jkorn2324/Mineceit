<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-03
 * Time: 12:46
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

class SetRanks extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('setranks', 'Set ranks of a player.', 'Usage: /setRanks <player> <ranks>', ['giveranks', 'ranksSet']);
        parent::setPermission('mineceit.permission.toggle-ranks');
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

        $language = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();

        if($this->testPermission($sender) and $this->canUseCommand($sender)) {

            $size = count($args);
            if($size <= 3 and $size > 1) {

                $name = (string)$args[0];
                $server = $sender->getServer();
                $player = $server->getPlayer($name);
                $rankHandler = MineceitCore::getRankHandler();

                if($player !== null and $player instanceof MineceitPlayer) {

                    if($size === 2) {
                        $ranks = [$args[1]];
                    } else {
                        $ranks = [$args[1], $args[2]];
                    }

                    $validRank = true;

                    $theRanks = [];

                    foreach($ranks as $rank) {
                        $theRank = $rankHandler->getRank($rank);
                        if($theRank === null) {
                            $validRank = false;
                            break;
                        } else {
                            $theRanks[] = $theRank;
                        }
                    }

                    if($validRank) {

                        $player->setRanks($theRanks);
                        $msg = $language->generalMessage(Language::PLAYER_SET_RANKS);

                    } else {

                        $msg = $language->generalMessage(Language::PLAYER_SET_RANKS_FAIL);
                    }

                } else $msg = $language->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]);


            } else $msg = $this->getUsage();
        }

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return true;
    }
}