<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-12
 * Time: 20:59
 */

declare(strict_types=1);

namespace mineceit\player\info\ips\tasks;


use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncCheckIP extends AsyncTask
{

    const API_KEYS = [
        "NTMzMTprenhOMXRNM0FMcVRHczBlMTd2elVlVTB6Q2wyazJsMg==",
        "NzQ5NTpXQUt2V1ZRZ3pyMkJRaFRXYWhyMHhtcHVuUmh1NUxqcw=="
    ];

    /** @var string */
    private $player;

    /** @var string */
    private $ip;

    public function __construct(string $player, string $ip)
    {
        $this->player = $player;
        $this->ip = $ip;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $index = 0;
        $keysLength = count(self::API_KEYS);
        while($index < $keysLength) {
            $result = $this->curlIP($index);
            $error = (bool)$result['error'];
            $output = $result['result'];
            if(!$error) {
                if($output !== null) {
                    $this->setResult(['set' => true, 'result' => (bool)$output]);
                } else {
                    $this->setResult(['set' => false, 'result' => true]);
                }
                return;
            }
            $index++;
        }

        $this->setResult(['set' => false, 'result' => true]);
    }

    /**
     * @param int $index
     * @return array
     *
     * Curls the ip to determine if it is valid or not.
     */
    private function curlIP(int $index) : array {

        $curl = curl_init();

        $apiKey = self::API_KEYS[$index];

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://v2.api.iphub.info/ip/{$this->ip}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json", "X-Key: {$apiKey}"]
        ]);

        $executed = curl_exec($curl);
        if(curl_errno($curl)){
            // $message = curl_error($curl);
            curl_close($curl);
            return ['error' => true, 'result' => false];
        }
        curl_close($curl);
        $info = json_decode($executed, true);
        if(isset($info['block'])) {
            $block = (int)$info['block'];
            $result = $block != 1;
            return ['error' => false, 'result' => $result];
        }

        return ['error' => false, 'result' => null];
    }


    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin("Mineceit");
        if($plugin instanceof MineceitCore and $plugin->isEnabled()) {
            $player = $server->getPlayer($this->player);
            $results = $this->getResult();
            $playerManager = MineceitCore::getPlayerHandler();
            if($player instanceof MineceitPlayer and $player->isOnline()) {
                $set = (bool)$results['set'];
                $result = (bool)$results['result'];
                if(!$result) {
                    $playerManager->setKickOnJoin($player, "Please turn off your vpn/proxy to play.");
                    return;
                }
                $playerManager->updateAliases($player, true, $set);
            }
        }
    }
}