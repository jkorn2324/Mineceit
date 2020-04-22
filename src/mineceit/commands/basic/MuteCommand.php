<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-28
 * Time: 14:27
 */

declare(strict_types=1);

namespace mineceit\commands\basic;


use mineceit\commands\MineceitCommand;
use mineceit\discord\DiscordUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MuteCommand extends MineceitCommand
{
    /** @var bool */
    private $muted;

    public function __construct(bool $mute)
    {
        parent::__construct($mute ? "mute" : "unmute", ($mute ? "Mutes " : "Unmutes ") . "the specified player.", "Usage: " . ($mute ? "/mute" : "/unmute") . " <player>", []);
        parent::setPermission('mineceit.permission.mute');
        $this->muted = $mute;
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

        if($this->testPermission($sender) and $this->canUseCommand($sender)) {

            $count = count($args);

            $language = MineceitCore::getPlayerHandler()->getLanguage();

            if($sender instanceof MineceitPlayer) {
                $language = $sender->getLanguage();
            }

            if($count === 1) {

                $playerName = (string)$args[0];
                $server = Server::getInstance();
                if(($player = $server->getPlayer($playerName)) !== null and $player instanceof MineceitPlayer) {

                    $string = Language::PLAYER_SET_UNMUTED_SENDER;

                    if($this->muted) {
                        $string = Language::PLAYER_SET_MUTED_SENDER;
                    }

                    $player->setMuted($this->muted);
                    $msg = $language->getMessage($string, ['name' => $player->getDisplayName()]);

                    $title = DiscordUtil::boldText($this->muted ? "Mute" : "Unmute");
                    $description = DiscordUtil::boldText("User:") . " " . $sender->getName() . "\n" . DiscordUtil::boldText($this->muted ? "Muted:" : "Unmuted:") . " " . $player->getName();

                    DiscordUtil::sendLog($title, $description, DiscordUtil::GOLD);


                } else {
                    $msg = $language->getMessage(Language::PLAYER_NOT_ONLINE, ['name' => $playerName]);
                }

            } else {
                $msg = $this->getUsage();
            }
        }

        if($msg !== null) {
            $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
        }

        return true;
    }


    public function testPermission(CommandSender $sender): bool
    {

        if($sender instanceof MineceitPlayer and $sender->hasModPermissions()) {
            return true;
        }

        return parent::testPermission($sender);
    }
}