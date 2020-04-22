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
use pocketmine\lang\TranslationContainer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Client5GoogleTranslate extends AsyncTask
{

    const TRANSLATE_URL = "https://clients5.google.com/translate_a/t?client=dict-chrome-ex&";/*ie=UTF-8&oe=UTF-8&";*/

    /** @var string */
    private $playerName;

    /** @var string */
    private $translateUrl;

    /** @var string */
    private $format;

    /** @var string */
    private $originalMessage;

    /** @var string */
    private $originalCleanedMessage;

    /** @var array */
    private $translationContainerValues;

    /**
     * Client5GoogleTranslate constructor.
     * @param MineceitPlayer $player
     * @param string|TranslationContainer $msg
     * @param string $originalLang
     * @param string $format
     * @param int $transContMsgParam
     */
    public function __construct(MineceitPlayer $player, $msg, string $originalLang = "", string $format = "", int $transContMsgParam = 0)
    {
        $this->playerName = $player->getName();
        $translatedLang = $player->getLanguage()->getTranslationName(TranslateUtil::GOOGLE_TRANSLATE);
        $this->format = $format;

        $translationContainerValues = [];

        if($msg instanceof TranslationContainer) {

            $translationContainerValues = [
                "parameters" => $msg->getParameters(),
                "message_parameter" => $transContMsgParam,
                "text" => $msg->getText()
            ];

            $theMessage = $msg->getParameter($transContMsgParam);
            $msg = $theMessage ?? "";
        }

        $this->translationContainerValues = $translationContainerValues;

        $this->originalMessage = $msg;

        $msg = TextFormat::clean(trim($msg));

        $this->originalCleanedMessage = $msg;

        $auto = TranslateUtil::AUTO;

        $this->translateUrl = self::TRANSLATE_URL . "sl={$auto}&tl={$translatedLang}&dt=t&q=" . urlencode($msg);
    }


    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $curl = $this->curl_info();

        $response = curl_exec($curl);

        curl_close($curl);

        if (is_bool($response)) {
            print("\nERROR: FAILED TO CURL RESPONSE\n");
            return;
        }

        $output = json_decode($response, true);

        $result = $output["sentences"][0];

        $translated = $result["trans"];

        $original = $result["orig"];

        $originalLanguage = $output["src"];

        $this->setResult(["translated" => $translated, "original" => $original, "original_lang" => $originalLanguage]);
    }

    /**
     *
     * @param string $encoding
     *
     * @return false|resource
     */
    private function curl_info($encoding = "utf-8") {

        $headers = [
            "Content-Type: application/json;charset=\"{$encoding}\""
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->translateUrl . "&ie=utf-8&oe={$encoding}");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_ENCODING, "");

        return $curl;
    }


    public function onCompletion(Server $server)
    {

        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled() and $result !== null) {

            $translated = trim((string)$result['translated']);
            $original = (string)$result["original"];
            $outputOriginalLang = (string)$result['original_lang'];

            $size = count($this->translationContainerValues);

            if(($player = MineceitUtil::getPlayer($this->playerName)) !== null and $player instanceof MineceitPlayer) {

                $translateLang = $player->getLanguage()->getTranslationName(TranslateUtil::GOOGLE_TRANSLATE);

                $result = $translated;
                if(strtolower($translated) === strtolower($original) or $translated === '' or $outputOriginalLang === $translateLang) {
                    $result = $original;
                }

                if($result === "") {
                    $result = $this->originalMessage;
                }

                $result = $this->format . $result;

                if($size > 0) {

                    $text = (string)$this->translationContainerValues["text"];
                    $parameters = (array)$this->translationContainerValues["parameters"];
                    $messageParameter = (int)$this->translationContainerValues["message_parameter"];
                    $parameters[$messageParameter] = $result;

                    $result = new TranslationContainer($text, $parameters);
                }

                $player->sendMessage($result);
            }
        }
    }
}