<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-01
 * Time: 12:08
 */

declare(strict_types=1);

namespace mineceit\commands\basic;


use jojoe77777\FormAPI\SimpleForm;
use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\DesktopPlayer;

class GamemodeCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('gamemode', 'Change gamemode of a player.', 'Usage: /gamemode', ['gm', 'gmui']);
        parent::setPermission("pocketmine.command.gamemode");
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
        $message = null;

        if($sender instanceof MineceitPlayer || $sender instanceof DesktopPlayer){

            if($this->testPermission($sender) and $this->canUseCommand($sender)) {

                $onlinePlayers = $sender->getServer()->getOnlinePlayers();
                $online = [];

                foreach($onlinePlayers as $player) {
                    $name = $player->getName();
                    $online[] = $name;
                }

                if($player instanceof MineceitPlayer) {

                    $form = FormUtil::getGamemodeForm($sender, $online);

                    $sender->sendFormWindow($form, ['online-players' => $online]);
                } elseif($player instanceof DesktopPlayer) {

                    $sender->setGamemode(1);
                }
            }
        } else {
            $message = TextFormat::RED . "Console can't use this command.";
        }

        if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

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