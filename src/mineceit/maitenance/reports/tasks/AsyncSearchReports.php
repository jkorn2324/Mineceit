<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-13
 * Time: 12:55
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports\tasks;

use mineceit\game\FormUtil;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncSearchReports extends AsyncTask
{

    /** @var string */
    private $name;

    /** @var string */
    private $searched;

    /** @var int */
    private $timeDifference;

    /** @var array */
    private $reportTypes;

    /** @var array|string[] */
    private $serializedReports;

    /** @var bool|null */
    private $resolved;

    /**
     * AsyncSearchReports constructor.
     * @param string $player
     * @param string $searched
     * @param int $timespan
     * @param array $reportTypes
     * @param array|ReportInfo[] $reports
     * @param int $resolved
     */
    public function __construct(string $player, string $searched, int $timespan, array $reportTypes, array $reports, int $resolved)
    {
        $this->name = $player;
        $this->searched = trim(strtolower($searched));

        $difference = -1;
        switch($timespan) {
            case 1:
                $difference = 3600; // last hour
                break;
            case 2:
                $difference = 43200; // last 12 hours
                break;
            case 3:
                $difference = 86400; // last day
                break;
            case 4:
                $difference = 604800; // last week
                break;
            case 5:
                $difference = 2592000; // last month
                break;
        }

        $resolvedOutput = null;
        if($resolved !== 0) {
            $resolvedOutput = $resolved === 2;
        }

        $this->resolved = $resolvedOutput;

        $this->timeDifference = $difference;

        $theReports = [];

        foreach($reports as $report) {
            $theReports[$report->getTimestamp()] = json_encode($report->serialize());
        }

        $this->serializedReports = $theReports;

        $types = [];
        foreach($reportTypes as $type => $value) {
            if($value) {
                $types[$type] = true;
            }
        }

        $this->reportTypes = $types;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $now = time();

        $reports = (array)$this->serializedReports;

        // Checks if there is at least one report.
        if(count($reports) <= 0) {
            return;
        }

        $reports = array_map(function(string $encoded) {
            return json_decode($encoded, true);
        }, $reports);

        // Checks if there is at least more than one report type checked off.
        $reportTypes = (array)$this->reportTypes;

        if(count($reportTypes) <= 0) {
            return;
        }

        $outputReports = [];

        foreach($reports as $timestamp => $report) {

            $reportType = (int)$report['type'];

            if(isset($reportTypes[$reportType])) {

                if($this->timeDifference !== -1) {
                    $difference = intval($now - $timestamp);
                    if($difference > $this->timeDifference) {
                        continue;
                    }
                }

                if($this->resolved !== null) {
                    $resolved = (bool)$report['resolved'];
                    if($this->resolved !== $resolved) {
                        continue;
                    }
                }

                if($this->searched !== "") {

                    $reporter = (string)$report['reporter'];
                    $lower = trim(strtolower($reporter));

                    if($lower === $this->searched or strpos($lower, $this->searched) !== false) {
                        $outputReports[$timestamp] = $report;
                        continue;
                    }

                    if(isset($report['lang'])) {
                        $language = (array)$report['lang'];
                        $found = false;
                        foreach($language as $key => $value) {
                            $lower = trim(strtolower(strval($value)));
                            if(strpos($lower, $this->searched) !== false) {
                                $outputReports[$timestamp] = $report;
                                $found = true;
                                continue;
                            }
                        }

                        if($found) {
                            continue;
                        }
                    }

                    if(isset($report['reported'])) {
                        $reported = (string)$report['reported'];
                        $lower = trim(strtolower($reported));
                        if($lower === $this->searched or strpos($lower, strtolower($this->searched)) !== false) {
                            $outputReports[$timestamp] = $report;
                        }
                    }

                    continue;
                }

                $outputReports[$timestamp] = $report;
            }
        }

        $reports = $outputReports;

        $this->setResult(['reports' => $reports]);
    }


    public function onCompletion(Server $server)
    {
        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled()) {

            $reportManager = MineceitCore::getReportManager();

            $result = $result ?? ['reports' => []];

            $reports = (array)$result['reports'];

            if (($player = $server->getPlayer($this->name)) !== null and $player instanceof MineceitPlayer) {

                if ($player->isInHub() or ($player->isInEvent() and !$player->isInEventDuel())) {

                    $theReports = [];

                    foreach($reports as $reportData) {
                        $localName = $reportData['local'];
                        if(($report = $reportManager->getReport($localName)) !== null and $report instanceof ReportInfo) {
                            $theReports[$report->getTimestamp()] = $report;
                        }
                    }

                    krsort($theReports);

                    $form = FormUtil::getListOfReports($player, $theReports);
                    $player->sendFormWindow($form, ['reports' => array_values($theReports), 'history' => false, 'perm' => $player->getReportPermissions()]);
                }
            }
        }
    }
}