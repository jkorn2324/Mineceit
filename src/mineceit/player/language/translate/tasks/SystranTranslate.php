<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-28
 * Time: 19:19
 */

declare(strict_types=1);

namespace mineceit\player\language\translate\tasks;


use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;

class SystranTranslate extends AsyncTask
{

    // THIS IS NOT WORKING

    const TRANSLATE_URL = "https://systran-systran-platform-for-language-processing-v1.p.rapidapi.com/translation/text/translate?source={source}&target={target}&input={input}";

    /** @var string */
    private $originalLang;

    /** @var string */
    private $translatedLang;

    /** @var string */
    private $url;

    /** @var string */
    private $format;

    /** @var string */
    private $originalMessage;

    /** @var string */
    private $playerName;

    public function __construct(MineceitPlayer $player, string $message, string $format = '', string $lang = '')
    {
        $this->translatedLang = $player->getLanguage()->getTranslationName(TranslateUtil::ISO_639_1);

        $this->originalLang = $lang;

        $this->playerName = $player->getName();

        $this->format = $format;

        $this->originalMessage = $message;

        if($this->translatedLang !== $this->originalLang) {
            if(strpos($this->originalMessage, "Unranked") !== false)
                $this->originalMessage = str_replace("Unranked", "Un ranked", $this->originalMessage);
        }

        $this->url = str_replace("{source}", $this->originalLang, str_replace("{target}", $this->translatedLang, str_replace("{input}", $this->originalMessage, self::TRANSLATE_URL)));
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "x-rapidapi-host: systran-systran-platform-for-language-processing-v1.p.rapidapi.com",
                "x-rapidapi-key: 98e492348cmshe501ffed97ec40dp1d8bb5jsn4fdb565074c2"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }

        /* Does not work. */
    }
}