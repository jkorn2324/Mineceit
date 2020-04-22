<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-28
 * Time: 16:25
 */

declare(strict_types=1);

namespace mineceit\discord;

use mineceit\discord\objects\DiscordEmbed;
use mineceit\maitenance\reports\data\BugReport;
use mineceit\maitenance\reports\data\HackReport;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\maitenance\reports\data\StaffReport;
use mineceit\maitenance\reports\data\TranslateReport;
use mineceit\MineceitCore;
use pocketmine\Server;
use pocketmine\utils\Internet;

class DiscordUtil
{

    const MINECEIT_LOGO = "https://pbs.twimg.com/profile_images/1189257177103568903/Br6AkYsl_400x400.jpg";


    const BLUE = "1f5fa0";
    const SEA_GREEN = "35b27f";
    const RED = "ff5252";
    const FOREST_GREEN = "255836";
    const GREEN = "35b27f";
    const ORANGE = "ff8633";
    const YELLOW = "FFF933";
    const GOLD = 'FCD309';
    const DARK_RED = 'BB0000';


    /**
     * @param ReportInfo $info
     *
     * Sends a report to the webhook.
     */
    public static function sendReport(ReportInfo $info) : void {

        $server = Server::getInstance();

        $reportType = $info->getReportType(true);

        $reporter = $info->getReporter();

        $array = [
            "Author" => $reporter,
            "",
        ];

        if($info instanceof TranslateReport) {
            $array = array_merge($array, [
                "Language" => $info->getLang(true),
                "",
                "Original" => $info->getOriginalMessage(),
                "New" => $info->getNewMessage()
            ]);
        } elseif ($info instanceof BugReport) {
            $array = array_merge($array, [
                "Bug" => $info->getDescription(),
                "Reproduces" => $info->getReproduceInfo()
            ]);
        } elseif ($info instanceof StaffReport) {
            $array = array_merge($array, [
                "Staff" => $info->getReported(),
                "Reason" => $info->getReason()
            ]);
        } elseif ($info instanceof HackReport) {
            $array = array_merge($array, [
                "Reported" => $info->getReported(),
                "Reason" => $info->getReason()
            ]);
        }

        $message = "";
        foreach($array as $key => $value) {
            if(is_int($key)) {
                $message .= "\n";
            } else {
                $message .= DiscordUtil::boldText(strval($key) . ':') . " {$value}\n";
            }
        }

        $embed = new DiscordEmbed($reportType, $message);
        $curlopts = $embed->encode(true);

        $webhook = MineceitCore::getDiscordWebhooks()["reports"];
        if($webhook !== "" and MineceitCore::DISCORD_ENABLED) {
            $server->getAsyncPool()->submitTask(new AsyncNotifyDiscord($webhook, $curlopts));
        }
    }

    /**
     * @param string $title
     * @param string $description
     * @param string $color
     *
     * Sends a log to the discord server.
     */
    public static function sendLog(string $title, string $description, string $color) : void {

        $server = Server::getInstance();

        $embed = new DiscordEmbed($title, $description, $color);

        $curlopts = $embed->encode();

        $webhook = MineceitCore::getDiscordWebhooks()['logs'];

        if($webhook !== "" and MineceitCore::DISCORD_ENABLED) {
            $server->getAsyncPool()->submitTask(new AsyncNotifyDiscord($webhook, $curlopts));
        }
    }


    /**
     * @param string $title
     * @param string $description
     * @param string $color
     *
     * Sends a server status update.
     */
    public static function sendStatusUpdate(string $title, string $description, string $color) : void {

        $server = Server::getInstance();

        $embed = new DiscordEmbed($title, $description, $color);

        $curlopts = $embed->encode();

        $webhook = MineceitCore::getDiscordWebhooks()['status'];

        if($webhook !== "" and MineceitCore::DISCORD_ENABLED) {
            $server->getAsyncPool()->submitTask(new AsyncNotifyDiscord($webhook, $curlopts));
        }
    }

    /**
     * @param $linkCode
     * @param string $playerName
     *
     * Sends a link code to the discord server.
     */
    public static function sendVerification($linkCode, string $playerName) : void {

        $username = "Mineceit";

        $data = [
            "player" => $playerName,
            "code" => $linkCode
        ];

        $encoded = json_encode($data);

        $curlopts = [
            'username' => $username,
            'content' => "link={$encoded}"
        ];

        $server = Server::getInstance();

        $webhook = "";

        if($webhook !== "" and MineceitCore::DISCORD_ENABLED)
            $server->getAsyncPool()->submitTask(new AsyncNotifyDiscord($webhook, $curlopts));
    }


    /**
     * @param $linkCode
     *
     * Sends a link code.
     */
    public static function sendServerLink($linkCode) : void {

        $username = "Mineceit";

        $server = Server::getInstance();

        $ip = Internet::getIP();

        $data = [
            "linkCode" => $linkCode,
            "server" => [
                "ip" => $ip,
                "port" => $server->getPort(),
                "name" => MineceitCore::getServerType()
            ],
            "mysql" => MineceitCore::getMysqlData()
        ];

        $encoded = json_encode($data);

        $curlopts = [
            'username' => $username,
            'content' => "server={$encoded}"
        ];

        $webhook = "";

        if($webhook !== "" and MineceitCore::DISCORD_ENABLED)
            $server->getAsyncPool()->submitTask(new AsyncNotifyDiscord($webhook, $curlopts));
    }


    /**
     * @param string $text
     * @return string
     */
    public static function boldText(string $text) : string
    {
        $string = explode(" ", $text);
        $length = count($string);
        if ($length <= 0) {
            return "**{$text}**";
        }
        $array = [];
        foreach ($string as $word)
            $array[] = "**{$word}**";
        return implode(" ", $array);
    }

}