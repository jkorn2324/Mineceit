<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-28
 * Time: 16:46
 */

declare(strict_types=1);

namespace mineceit\commands\basic\discord;


use mineceit\commands\MineceitCommand;
use mineceit\discord\DiscordUtil;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;

class VerifyCommand extends MineceitCommand
{

    public function __construct()
    {
        parent::__construct('verify', 'Links your MCPE account to your discord.', 'Usage: /verify <verification-code>', []);
        parent::setPermission('mineceit.permission.verify');
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

        if($sender instanceof MineceitPlayer) {

            // $lang = $sender->getLanguage();

            if($this->testPermission($sender) and $this->canUseCommand($sender)) {

                $length = count($args);

                if($length === 1) {

                    $code = $args[0];
                    $length = strlen($code);

                    if($length !== 6) {
                        // TODO INVALID DISCORD CODE
                    } else {
                        // DiscordUtil::sendVerification($code, $sender->getName());
                    }

                } else $message = $this->getUsage();

            }
        }

        if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . $message);

        return true;
    }
}