<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-02
 * Time: 12:48
 */

declare(strict_types=1);

namespace mineceit\player\language\translate\tasks;


use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class GoogleTranslate extends AsyncTask
{

    // THIS IS NOT WORKING.

    const TRANSLATE_URL = "https://translate.google.com/translate_a/single?client=gtx&";/*ie=UTF-8&oe=UTF-8&";*/

    /** @var string */
    private $playerName;

    /** @var string */
    private $originalLang = TranslateUtil::AUTO;

    /** @var string */
    private $translateUrl;

    /** @var string */
    private $format;

    /** @var string */
    private $originalMessage;

    public function __construct(MineceitPlayer $player, string $msg, string $originalLang = "", string $format = "")
    {
        $this->playerName = $player->getName();
        $this->originalLang = $originalLang !== '' ? $originalLang : $this->originalLang;
        $translatedLang = $player->getLanguage()->getTranslationName(TranslateUtil::GOOGLE_TRANSLATE);
        $this->format = $format;

        $this->originalMessage = $msg;

        if($translatedLang !== $this->originalLang) {
            if(strpos($msg, "Unranked") !== false)
                $msg = str_replace("Unranked", "Un ranked", $msg);
        }

        $this->translateUrl = self::TRANSLATE_URL . "sl={$this->originalLang}&tl={$translatedLang}&dt=t&q=" . urlencode($msg);
    }


    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->translateUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        if (is_bool($response)) {
            print("\nERROR: FAILED TO CURL RESPONSE\n");
            return;
        }

        curl_close($curl);

        $output = json_decode($response, true);

        $result = $output[0][0];

        $translated = $result[0];

        $original = $result[1];

        $this->setResult(["translated" => $translated, "original" => $original]);
    }


    public function onCompletion(Server $server)
    {

        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled() and $result !== null) {

            $translated = trim((string)$result['translated']);
            $original = (string)$result["original"];
            $originalLang = $this->originalLang;

            if(($player = MineceitUtil::getPlayer($this->playerName)) !== null and $player instanceof MineceitPlayer) {

                $translateLang = $player->getLanguage()->getLocale();

                $result = $translated;
                if(strtolower($translated) === strtolower($original) or $translated === '' or $originalLang === $translateLang) {
                    $result = $original;
                }

                if($result === "") {
                    $result = $this->originalMessage;
                }

                $player->sendMessage($this->format . $result);
            }
        }
    }
}