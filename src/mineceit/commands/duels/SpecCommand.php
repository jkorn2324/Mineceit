<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-03
 * Time: 08:02
 */

declare(strict_types=1);

namespace mineceit\commands\duels;


use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class SpecCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('spec', 'Command to spectate duels.', '/spec', ['spectate']);
        parent::setPermission('mineceit.permission.spec');
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
            $language = $sender->getLanguage();
            if($this->testPermission($sender) and $this->canUseCommand($sender)) {
                if($sender->isInHub()) {
                    $duels = MineceitCore::getDuelHandler()->getDuels();
                    $duels = array_values($duels);
                    $form = FormUtil::getSpectateForm($sender, $duels);
                    $sender->sendFormWindow($form, ['duels' => $duels]);
                } else $msg = $language->generalMessage(Language::ONLY_USE_IN_LOBBY);
            }
        } else $msg = TextFormat::RED . "Console can't use this command.";

        if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return $msg;
    }
}