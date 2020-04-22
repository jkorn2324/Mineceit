<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-26
 * Time: 22:37
 */

declare(strict_types=1);

namespace mineceit\commands\basic;


use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class FreezeCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('freeze', 'Freeze/Unfreeze a player.', 'Usage: /freeze', []);
        parent::setPermission('mineceit.permissions.freeze');
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

        $message = null;

        if($sender instanceof MineceitPlayer){

            if($this->testPermission($sender) and $this->canUseCommand($sender)) {

                $onlinePlayers = $sender->getServer()->getOnlinePlayers();
                $online = [];

                foreach($onlinePlayers as $player) {
                    $name = $player->getDisplayName();
                    $online[] = $name;
                }

                $form = FormUtil::getFreezeForm($sender, $online);

                $sender->sendFormWindow($form, ['online-players' => $online]);
            }
        } else $message = TextFormat::RED . "Console can't use this command.";

        if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

        return true;
    }
}