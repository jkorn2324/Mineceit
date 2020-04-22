<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-02
 * Time: 12:03
 */

declare(strict_types=1);

namespace mineceit\player\language\translate;


use mineceit\player\language\Language;
use mineceit\player\language\translate\tasks\Client5GoogleTranslate;
use mineceit\player\language\translate\tasks\GoogleTranslate;
use mineceit\player\language\translate\tasks\SystranTranslate;
use mineceit\player\MineceitPlayer;
use pocketmine\lang\TranslationContainer;
use pocketmine\Server;

use IntlChar;

class TranslateUtil
{
    const ENGLISH = "en";
    const AUTO = "auto";
    const SPANISH = "es";
    const RUSSIAN = "ru";
    const ARABIC = "ar";
    const FRENCH = "fr";
    const PORTUGUESE = "pt";
    const ROMANIAN = "ro";
    const TURKISH = "tr";
    const VIETNAMESE = "vi";
    const KOREAN = "ko";
    const JAPANESE = "ja";
    const ITALIAN = "it";
    const HAWAIIAN = "haw";
    const ESPERANTO = "eo";
    const CHINESE_SIMPLIFIED = "zh-cn";
    const CHINESE_TRADITIONAL = "zh-tw";

    const GOOGLE_TRANSLATE = 'google';
    const ISO_639_1 = 'iso-639-1';

    /**
     * @param MineceitPlayer $player
     * @param string $msg
     * @param Language $lang
     * @param string $format
     *
     * Translates the message using the googleapps/translate_a hack.
     */
    public static function google_translate(string $msg, MineceitPlayer $player, Language $lang, string $format = "") : void {

        $asyncTask = new GoogleTranslate($player, $msg, $lang->getTranslationName(self::GOOGLE_TRANSLATE), $format);

        $server = Server::getInstance();

        $server->getAsyncPool()->submitTask($asyncTask);
    }


    /**
     * @param string $msg
     * @param MineceitPlayer $player
     * @param Language $lang
     * @param string $format
     *
     * Translate systran (RapidAPI).
     */
    public static function systran_translate(string $msg, MineceitPlayer $player, Language $lang, string $format = "") : void {

        $asyncTask = new SystranTranslate($player, $msg, $lang->getTranslationName(self::ISO_639_1), $format);

        $server = Server::getInstance();

        $server->getAsyncPool()->submitTask($asyncTask);
    }



    /**
     * @param MineceitPlayer $player
     * @param string|TranslationContainer $msg
     * @param Language $lang
     * @param string $format
     * @param int $index
     *
     * Translates the message using the client5 hack.
     */
    public static function client5_translate($msg, MineceitPlayer $player, Language $lang, string $format = "", int $index = 0) : void {

        $asyncTask = new Client5GoogleTranslate($player, $msg, $lang->getTranslationName(self::GOOGLE_TRANSLATE), $format, $index);

        $server = Server::getInstance();

        $server->getAsyncPool()->submitTask($asyncTask);
    }


    public static function is_word(string $var) : bool {

        $length = strlen($var);

        if($length <= 0) {
            return false;
        }

        for($i = 0; $i < $length; $i++) {
            $character = $var[$i];
        }

        return false;
    }
}