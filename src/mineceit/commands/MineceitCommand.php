<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-31
 * Time: 19:28
 */

declare(strict_types=1);

namespace mineceit\commands;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

abstract class MineceitCommand extends Command
{

    /* @var bool */
    private $canUseInDuelAndCombat;

    /* @var bool */
    private $canUseAsASpec;

    public function __construct(string $name, string $description = "", string $usageMessage = null, $aliases = [], $canUseAsASpec = false, $canUseInDuelAndCombat = false)
    {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->canUseInDuelAndCombat = $canUseInDuelAndCombat;
        $this->canUseAsASpec = $canUseAsASpec;
    }

    /**
     * @param CommandSender $player
     * @return bool
     */
    public function canUseCommand(CommandSender $player) : bool {

        $result = true;

        $msg = null;

        $playerHandler = MineceitCore::getPlayerHandler();

        $language = $player instanceof MineceitPlayer ? $player->getLanguage() : $playerHandler->getLanguage();

        if($player instanceof MineceitPlayer) {

            if($player->isInCombat() or $player->isInDuel() or $player->isInEventDuel()) {
                $result = $this->canUseInDuelAndCombat;
                if(!$result) {
                    if($player->isInDuel() or $player->isInEventDuel())
                        $msg = $language->generalMessage(Language::COMMAND_FAIL_IN_DUEL);
                    elseif ($player->isInCombat())
                        $msg = $language->generalMessage(Language::COMMAND_FAIL_IN_COMBAT);
                }
            } elseif ($player->isADuelSpec()) {
                $result = $this->canUseAsASpec;
                if(!$result) {
                    // $msg = $language->generalMessage(Language::COMMAND_FAIL_IN_DUELSPEC);
                    $msg = TextFormat::RED . "Can't use this command while spectating a duel!";
                    // TODO
                }
            }

        }

        if($msg !== null) $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

        return $result;
    }

    /**
     * @param CommandSender $sender
     *
     * @return bool
     */
    public function testPermission(CommandSender $sender) : bool {

        if($this->testPermissionSilent($sender))
            return true;

        $playerHandler = MineceitCore::getPlayerHandler();

        $language = $sender instanceof MineceitPlayer ? $sender->getLanguage() : $playerHandler->getLanguage();

        $message = $language->getPermissionMessage();

        $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

        return false;
    }
}