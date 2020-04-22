<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-02
 * Time: 18:01
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports\tasks;


use mineceit\maitenance\reports\data\ReportInfo;
use pocketmine\scheduler\AsyncTask;

class AsyncSaveReports extends AsyncTask
{

    /** @var array */
    private $reports;

    /** @var string */
    private $file;

    /**
     * AsyncSaveReports constructor.
     * @param string $file
     * @param array|ReportInfo[] $savedReports
     */
    public function __construct(string $file, array $savedReports)
    {
        $reports = [];

        $this->file = $file;

        foreach($savedReports as $localname => $report) {
            $reports[] = $report->csv_encode();
        }

        $this->reports = $reports;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $reports = (array)$this->reports;

        $file = fopen($this->file, 'w');

        foreach($reports as $report) {
            fputcsv($file, (array)$report);
        }

        fclose($file);
    }
}