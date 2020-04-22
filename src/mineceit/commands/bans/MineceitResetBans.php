<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-31
 * Time: 19:43
 */

declare(strict_types=1);

namespace mineceit\commands\bans;


use mineceit\commands\MineceitCommand;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\permission\BanList;
use pocketmine\utils\TextFormat;

class MineceitResetBans extends MineceitCommand
{

    private const VALID_COMMANDS = ["all", "ips", "names"];

    public function __construct()
    {
        parent::__construct("resetbans", "Resets the bans on the server.", "Usage: /resetbans <ips:names:all>", ["banreset"]);
        parent::setPermission("mineceit.permission.reset");
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

        if($this->testPermission($sender) and $this->canUseCommand($sender)){

            $length = count($args);

            if($length <= 1) {

                $command = "all";

                if($length === 1) {

                    $command = (string)$args[0];
                }

                if(in_array(strtolower($command), self::VALID_COMMANDS)) {

                    $nameBans = $sender->getServer()->getNameBans();

                    $ipBans = $sender->getServer()->getIPBans();

                    // TODO ADD MESSAGES

                    switch(strtolower($command)) {

                        case "ips":
                            $msg = TextFormat::GREEN . "Successfully reset all ip bans.";
                            $this->unbanFromList($ipBans);
                            break;

                        case "names":
                            $msg = TextFormat::GREEN . "Successfully reset all name bans.";
                            $this->unbanFromList($nameBans);
                            break;

                        default:
                            $msg = TextFormat::GREEN . "Successfully reset all bans.";
                            $this->unbanFromList($ipBans);
                            $this->unbanFromList($nameBans);

                    }

                } else {
                    $msg = $this->getUsage();
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

        if($sender instanceof MineceitPlayer and $sender->hasOwnerPermissions()) {
            return true;
        }

        return parent::testPermission($sender);
    }


    /**
     * @param BanList $list
     */
    private function unbanFromList(BanList $list) : void {

        $entries = $list->getEntries();

        foreach($entries as $name => $entry) {
            $list->remove($entry->getName());
        }
    }
}