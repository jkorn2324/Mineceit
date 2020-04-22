<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-28
 * Time: 16:29
 */

declare(strict_types=1);

namespace mineceit\discord;


use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncNotifyDiscord extends AsyncTask
{

    /** @var string */
    private $curlopts;

    /** @var string */
    private $webhook;

    public function __construct(string $webhook, array $curlopts)
    {
        $this->curlopts = json_encode($curlopts);
        $this->webhook = $webhook;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $curl = curl_init();

        $curlOpts = json_decode((string)$this->curlopts, true);
        $encodedCurlOpts = json_encode($curlOpts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // var_dump($curlOpts);
        $length = strval(strlen($encodedCurlOpts));

        curl_setopt($curl, CURLOPT_URL, $this->webhook);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedCurlOpts);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Length: {$length}",
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        if (is_bool($response)) {
            print("ERROR: FAILED TO CURL RESPONSE");
            return;
        }

        $error = curl_error($curl);

        $outputError = "ERROR: SOMETHING WENT WRONG";

        if ($error != '') {
            $outputError = $error;
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
            if(isset($curl["message"])) {
                $outputError = (string)$curl["message"];
            }
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204 or $response === '') {
            $outputError = "SUCCESS!";
        }

        curl_close($curl);

        $this->setResult($outputError);
    }
}