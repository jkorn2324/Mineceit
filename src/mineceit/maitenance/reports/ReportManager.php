<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-02
 * Time: 16:05
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports;


use mineceit\discord\DiscordUtil;
use mineceit\maitenance\reports\data\BugReport;
use mineceit\maitenance\reports\data\HackReport;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\maitenance\reports\data\StaffReport;
use mineceit\maitenance\reports\data\TranslateReport;
use mineceit\maitenance\reports\tasks\AsyncLoadReports;
use mineceit\maitenance\reports\tasks\AsyncSaveReports;
use mineceit\maitenance\reports\tasks\AsyncSearchReports;
use mineceit\maitenance\reports\tasks\AsyncTranslateReport;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class ReportManager
{

    /** @var MineceitCore */
    private $core;

    /** @var string */
    private $file;

    /** @var Server */
    private $server;

    /** @var array|ReportInfo[] */
    private $reports;

    public function __construct(MineceitCore $core)
    {
        $this->core = $core;
        $this->file = $core->getDataFolder() . 'reports.csv';
        $this->server = $core->getServer();
        $this->reports = [];

        $this->initFile();
    }

    /**
     * Initializes the report file.
     */
    private function initFile() : void {

        if(!file_exists($this->file)) {
            $file = fopen($this->file, 'wb');
            fclose($file);
        } else {
            $task = new AsyncLoadReports($this->file);
            $this->server->getAsyncPool()->submitTask($task);
        }
    }

    /**
     * @param $reports
     *
     * Loads the reports and saves them.
     */
    public function loadReports($reports) : void {

        foreach($reports as $data) {

            $report = ReportInfo::csv_decode($data);

            if($report !== null) {
                $this->reports[$report->getLocalName()] = $report;
            }
        }
    }


    /**
     * @param bool $count
     * @return array|ReportInfo[]|int
     */
    public function getReports(bool $count = false) {
        return $count ? count($this->reports) : $this->reports;
    }

    /**
     * @param string $name
     * @param array|null $reportTypes
     *
     * @return array|ReportInfo[]
     *
     * Gets all the staff/hacker reports of the given player.
     *
     */
    public function getReportsOf(string $name, $reportTypes = null) {

        $result = [];
        $reportTypes = $reportTypes ?? [ReportInfo::TYPE_STAFF, ReportInfo::TYPE_HACK];

        $searched = array_flip($reportTypes);

        foreach($this->reports as $report) {

            if(!isset($searched[$report->getReportType()])) {
                continue;
            }

            if($report instanceof HackReport and $report->getReported() === $name) {
                $result[$report->getTimestamp()] = $report;
            } elseif ($report instanceof StaffReport and $report->getReported() === $name) {
                $result[$report->getTimestamp()] = $report;
            }
        }

        krsort($result);

        return $result;
    }


    /**
     * @param string $name
     *
     * @return array|ReportInfo[]
     */
    public function getReportHistoryOf(string $name) {

        $result = [];

        foreach($this->reports as $report) {
            if($report->getReporter() === $name) {
                $result[$report->getTimestamp()] = $report;
            }
        }

        krsort($result);
        return $result;
    }



    /**
     * @param ReportInfo $info
     * @return bool
     *
     * Creates a new report.
     */
    public function createReport(ReportInfo $info) : bool {

        $localname = $info->getLocalName();

        if(!isset($this->reports[$localname])) {

            $this->reports[$localname] = $info;

            DiscordUtil::sendReport($info);

            $task = new AsyncSaveReports($this->file, $this->reports);
            $this->server->getAsyncPool()->submitTask($task);

            $local = Language::NEW_HACK_REPORT;
            switch($info->getReportType()) {
                case ReportInfo::TYPE_BUG:
                    $local = Language::NEW_BUG_REPORT;
                    break;
                case ReportInfo::TYPE_TRANSLATE:
                    $local = Language::NEW_TRANSLATION_REPORT;
                    break;
                case ReportInfo::TYPE_STAFF:
                    $local = Language::NEW_STAFF_REPORT;
                    break;
            }

            MineceitUtil::broadcastNoticeToStaff($local);

            return true;
        }

        return false;
    }

    /**
     * @param ReportInfo $info
     * @return bool
     *
     * Removes the report -> used for clearing a report.
     *
     */
    public function removeReport(ReportInfo $info) : bool {

        $localname = $info->getLocalName();

        if(isset($this->reports[$localname])) {

            unset($this->reports[$localname]);

            $task = new AsyncSaveReports($this->file, $this->reports);
            $this->server->getAsyncPool()->submitTask($task);

            return true;
        }

        return false;
    }


    /**
     * @param string $localName
     * @return ReportInfo|null
     *
     * Gets the report.
     */
    public function getReport(string $localName) {
        return isset($this->reports[$localName]) ? $this->reports[$localName] : null;
    }


    /**
     * @param MineceitPlayer $searcher
     * @param string $name
     * @param int $timespan
     * @param array $types
     * @param int $resolved
     *
     * Searches the reports for the player.
     */
    public function searchReports(MineceitPlayer $searcher, string $name, int $timespan, array $types, int $resolved) : void {

        $task = new AsyncSearchReports($searcher->getName(), $name, $timespan, $types, $this->reports, $resolved);

        $this->server->getAsyncPool()->submitTask($task);
    }


    /**
     * @param MineceitPlayer $player
     * @param ReportInfo $info
     * @param bool $history
     *
     * Translates the report so that the player can read it.
     */
    public function sendTranslatedReport(MineceitPlayer $player, ReportInfo $info, bool $history) : void {

        $lang = $player->getLanguage()->getTranslationName(TranslateUtil::GOOGLE_TRANSLATE);

        $task = new AsyncTranslateReport($player->getName(), $lang, $info, $history);

        $this->server->getAsyncPool()->submitTask($task);
    }
}