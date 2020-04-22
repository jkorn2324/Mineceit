<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-10
 * Time: 17:32
 */

declare(strict_types=1);

namespace mineceit\player\info\ips\tasks;


use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncCollectInfo extends AsyncTask
{

    /** @var string */
    private $name;

    /** @var string */
    private $ip;

    public function __construct(string $name, string $ip)
    {
        $this->name = $name;
        $this->ip = $ip;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        // IP Locate = https://iplocate.io/api/lookup/{ip}"
        // Free Geo IP = https://freegeoip.app/json/{ip}

        // TODO TEST FREE GEO IP

        $context = stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ]);

        $ip = $this->ip;

        try {
            $contents = file_get_contents("https://iplocate.io/api/lookup/{$ip}", false, $context);
        } catch (\Exception $e) {
            try {
                $contents = file_get_contents("https://freegeoip.app/json{$ip}", false, $context);
            } catch (\Exception $e) {
                return;
            }
        }

        if(!isset($contents)) {
            return;
        }

        $result = json_decode($contents, true);

        if(isset($result['country_name'])) {
            $result['country'] = $result['country_name'];
        }

        if (isset($result["time_zone"], $result['country'])) {

            $country = $result['country'];

            $countries12Hr = ["United States", "United Kingdom", "Philippines", "Canada", "Australia", "New Zealand", "India", "Egypt", "Saudi Arabia", "Columbia", "Pakistan", "Malaysia"];
            $is24Hour = !in_array($country, $countries12Hr);

            $array = ["timezone" => $result["time_zone"], "24-hour" => $is24Hour];
            $this->setResult($array);
        }
    }

    public function onCompletion(Server $server)
    {
        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled()) {

            $playerManager = MineceitCore::getPlayerHandler();
            $ipManager = $playerManager->getIPManager();

            if($result !== null) {

                $timeZone = (string)$result['timezone'];
                $is24Hour = (bool)$result['24-hour'];

                $ipManager->setTimeZone($this->ip, $timeZone, $is24Hour);
            }
        }
    }
}