<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-27
 * Time: 18:04
 */

declare(strict_types=1);

namespace mineceit\commands\basic;


use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class HubCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('hub', 'Go back to spawn.', "Usage: /hub", ['spawn'], true);
        parent::setPermission('mineceit.permission.hub');
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

        if($sender instanceof MineceitPlayer) {

            $duelHandler = MineceitCore::getDuelHandler();

            $itemHandler = MineceitCore::getItemHandler();

            $eventManager = MineceitCore::getEventManager();

            $language = $sender->getLanguage();

            if($this->testPermission($sender) and $this->canUseCommand($sender)) {

                $sendMessage = true;

                if($sender->isInEvent()) {
                    $event = $eventManager->getEventFromPlayer($sender);
                    $event->removePlayer($sender);
                    $sendMessage = false;
                }

                if($sender->isADuelSpec()) {
                    $duel = $duelHandler->getDuelFromSpec($sender);
                    $duel->removeSpectator($sender);
                    $sendMessage = false;
                }

                if($sendMessage){
                    $sender->reset(true, true);
                    $itemHandler->spawnHubItems($sender);
                    $msg = $language->generalMessage(Language::IN_HUB);
                    $sender->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                }

                if ($sender->isWatchingReplay()) {
                    $replay = MineceitCore::getReplayManager()->getReplayFrom($sender);
                    $replay->endReplay(false);
                }
            }

        } else $msg = TextFormat::RED . "Console can't use this command.";

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return true;
    }
}