<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-19
 * Time: 22:30
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports\tasks;


use mineceit\game\FormUtil;
use mineceit\maitenance\reports\data\BugReport;
use mineceit\maitenance\reports\data\HackReport;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\maitenance\reports\data\StaffReport;
use mineceit\MineceitCore;
use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncTranslateReport extends AsyncTask
{

    const TRANSLATE_URL = "https://clients5.google.com/translate_a/t?client=dict-chrome-ex&";/*ie=UTF-8&oe=UTF-8&";*/

    /** @var string */
    private $player;

    /** @var string[]|array */
    private $urls;

    /** @var string[]|array */
    private $originals;

    /** @var string */
    private $reportLocal;

    /** @var bool */
    private $history;

    public function __construct(string $playerName, string $translatedLang, ReportInfo $info, bool $history)
    {

        $this->player = $playerName;
        $this->reportLocal = $info->getLocalName();
        $this->history = $history;

        $originals = [];

        if($info instanceof HackReport) {

            $originals = [
                "reason" => $info->getReason()
            ];

        } elseif ($info instanceof StaffReport) {

            $originals = [
                "reason" => $info->getReason()
            ];

        } elseif ($info instanceof BugReport) {

            $originals = [
                "desc" => $info->getDescription(),
                "reproduce" => $info->getReproduceInfo()
            ];
        }

        $this->originals = $originals;

        $auto = TranslateUtil::AUTO;

        $urls = [];

        foreach($originals as $key => $msg) {
            $url = self::TRANSLATE_URL . "sl={$auto}&tl={$translatedLang}&dt=t&q=" . urlencode($msg);
            $urls[$key] = $url;
        }

        $this->urls = $urls;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $urls = (array)$this->urls;
        $originals = (array)$this->originals;

        $translates = [];

        foreach($urls as $key => $url) {

            $curl = $this->curl_info($url);

            $response = curl_exec($curl);
            curl_close($curl);

            if(is_bool($response)) {
                $translates[$key] = ["translated" => $originals[$key], "original" => $originals[$key], "original_lang" => ""];
                continue;
            }

            $output = json_decode($response, true);
            $result = $output['sentences'][0];
            $translated = $result['trans'];
            $originalLanguage = $output['src'];
            $translates[$key] = ["translated" => $translated, "original" => $originals[$key], "original_lang" => $originalLanguage];
        }

        $this->setResult(["info" => $translates]);
    }

    /**
     * @param string $url
     * @param string $encoding
     *
     * @return false|resource
     */
    private function curl_info(string $url, $encoding = "utf-8") {

        $headers = [
            "Content-Type: application/json;charset=\"{$encoding}\""
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url . "&ie=utf-8&oe={$encoding}");
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

        if($core instanceof MineceitCore and $core->isEnabled() and ($player = $server->getPlayer($this->player)) !== null and $player instanceof MineceitPlayer) {

            $reportManager = MineceitCore::getReportManager();
            $lang = $player->getLanguage()->getTranslationName(TranslateUtil::GOOGLE_TRANSLATE);

            if($result !== null) {

                $result = $result['info'];

                $array = [];

                foreach($result as $key => $value) {

                    $translated = $value['translated'];
                    $original = $value['original'];

                    $origLang = $value['original_lang'];
                    if(strtolower($translated) === strtolower($original) or $origLang === "" or $origLang === $lang) {
                        $translated = $value["original"];
                    }

                    $array[$key] = $translated;
                }

                $report = $reportManager->getReport($this->reportLocal);

                $perm = $player->getReportPermissions();

                $form = FormUtil::getInfoOfReportForm($report, $player, $array);
                $player->sendFormWindow($form, ['perm' => $perm, 'report' => $report, 'history' => $this->history]);
            }
        }

        /*
         *   $form = self::getInfoOfReportForm($report, $event);
         *
         *   $event->sendFormWindow($form, ['history' => $history, 'perm' => $perm, 'report' => $report]);
         */
    }
}