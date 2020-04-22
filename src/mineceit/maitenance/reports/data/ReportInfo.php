<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-02
 * Time: 16:11
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports\data;


use mineceit\MineceitCore;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

abstract class ReportInfo
{

    const REPORT_TYPES = [
        self::TYPE_BUG,
        self::TYPE_STAFF,
        self::TYPE_HACK,
        self::TYPE_TRANSLATE
    ];


    const PERMISSION_NORMAL = 0;
    const PERMISSION_VIEW_ALL_REPORTS = 1;
    const PERMISSION_MANAGE_REPORTS = 2;

    const MAX_REPORT_NUM = 4;

    const TYPE_BUG = 0;
    const TYPE_STAFF = 1;
    const TYPE_HACK = 2;
    const TYPE_TRANSLATE = 3;

    /** @var int */
    protected $type;

    /** @var string */
    protected $reporter;

    /** @var int */
    protected $time;

    /** @var bool */
    protected $resolved;

    /**
     * ReportInfo constructor.
     * @param int $type
     * @param MineceitPlayer|string $reporter
     * @param int|null $time
     * @param bool $resolved
     */
    public function __construct(int $type, $reporter, int $time = null, bool $resolved = false)
    {
        $this->type = $type;
        $this->reporter = $reporter instanceof MineceitPlayer ? $reporter->getName() : $reporter;
        $theTime = $time ?? time();
        $this->time = $theTime;
        $this->resolved = $resolved;
    }

    /**
     * @return bool
     *
     * Determines whether the report is resolved or not.
     */
    public function isResolved() : bool {
        return $this->resolved;
    }

    /**
     * Sets the report as resolved.
     */
    public function setResolved(bool $resolved) : void {
        $this->resolved = $resolved;
    }

    /**
     * @return string
     */
    public function getLocalName() : string {
        return $this->reporter . ":" . $this->time;
    }

    /**
     *
     * @param int|null $time
     * @return int
     *
     * Gets the current time difference.
     */
    public function getSecondsDifference(int $time = null) : int {

        $currentTime = $time ?? time();

        $seconds = $currentTime - $this->time;

        return $seconds;
    }

    /**
     * @return string
     *
     * Gets the name of the reporter.
     */
    public function getReporter() : string {
        return $this->reporter;
    }

    /**
     * @return int
     *
     * Gets the timestamp of the report.
     */
    public function getTimestamp() : int {
        return $this->time;
    }


    /**
     * @param bool $name
     * @param Language|null $lang
     * @return int|string
     *
     * Gets the report type.
     */
    public function getReportType(bool $name = false, Language $lang = null) {

        if(!$name) {
            return $this->type;
        }

        return self::getReportsType($this->type, $lang);
    }


    /**
     * @return array
     *
     * Encodes the data to a csv format.
     */
    abstract public function csv_encode() : array;


    /**
     * @param string $timezone
     * @param Language|null $lang
     * @param string $format
     * @return string
     *
     * Gets the date of the report based on format.
     */
    public function getDate(string $timezone, Language $lang = null, string $format = "%m%/%d%/%y%") : string {

        $time = $this->getTimeValues($timezone, $lang);

        $replaced = ["%m%" => $time["month-num"], "%d%" => $time["day-month"], "%y%" => $time["year"]];

        $format = trim($format);
        $result = $format === '' ? "%m%/%d%/%y%" : $format;
        foreach($replaced as $key => $value) {
            $result = str_replace($key, $value, $result);
        }

        return $result;
    }


    /**
     * @param string $timeZone
     * @param Language|null $lang
     * @param string $format
     * @param bool $displayTz
     * @return string
     *
     * Gets the time values.
     */
    public function getTime(string $timeZone, Language $lang = null, string $format = "%h%:%m% %me%", bool $displayTz = true) : string {

        $time = $this->getTimeValues($timeZone, $lang);

        $is23Hr = $time["23hr"];
        $merd = $is23Hr ? "" : $time["merd"];

        $replaced = ["%h%" => $time["hour"], "%m%" => $time["mins"], ($is23Hr ? " %me%" : "%me%") => $merd];

        $format = trim($format);
        $result = $format === '' ? '%h%:%m% %me%' : $format;

        foreach($replaced as $key => $value) {
            $result = str_replace($key, $value, $result);
        }

        $timeZone = $time['tz-zone'];

        if($displayTz) {
            $result .= ' (' . $timeZone . ')';
        }

        return $result;
    }

    /**
     *
     * @param string $timezone
     * @param Language $lang
     *
     * @return array
     *
     * Gets the time.
     *
     */
    private function getTimeValues(string $timezone, Language $lang = null) : array {

        $time = $this->time;
        $lang = $lang ?? MineceitCore::getPlayerHandler()->getLanguage();

        try {

            $is23Hr = false;
            $date = new \DateTime("@{$time}");
            $date->setTimezone(new \DateTimeZone($timezone));
            $format = "Y,m,d,D,l,F,i,s,A,T,g";
            if($lang->getLocale() === Language::PORTUGUESE_BR) {
                // TODO FIND MORE LANGUAGES THAT DISPLAY TIME IN 24 HR TIME
                $format = "Y,m,d,D,l,F,i,s,A,T,G";
                $is23Hr = true;
            }
            $result = $date->format($format);
            $result =  explode(",", $result);
            $tzValues = explode('+', $result[9]);

            return [
                "year" => $result[0],
                "month-num" => $result[1],
                "day-month" => $result[2],
                "day-week-simple" => $result[3],
                "day-week-comp" => $result[4],
                "month-comp" => $result[5],
                "mins" => $result[6],
                "secs" => $result[7],
                "merd" => $result[8],
                "tz-zone" => $tzValues[0],
                'tz-ahead' => isset($tzValues[1]) ? $tzValues[0] : "00:00",
                "hour" => $result[10],
                "23hr" => $is23Hr
            ];
        } catch (\Exception $e){
            $stacktrace = $e->getTraceAsString();
            print($stacktrace);
            return [
                "year" => "",
                "month-num" => "",
                "day-month" => "",
                "day-week-simple" => "",
                "day-week-comp" => "",
                "month-comp" => "",
                "mins" => "",
                "secs" => "",
                "merd" => "",
                "tz-zone" => "",
                'tz-ahead' => "",
                "hour" => "",
                "23hr" => ""
            ];
        }
    }

    /**
     * @param array $data
     * @return null|ReportInfo
     */
    public static function csv_decode(array $data) {

        $type = $data[0];
        $reporter = $data[1];

        switch($type) {
            case self::TYPE_BUG:
                $description = $data[2];
                $occurrence = $data[3];
                $time = $data[4];
                $resolved = (bool)$data[5];
                return new BugReport($reporter, $description, $occurrence, intval($time), $resolved);
            case self::TYPE_STAFF:
                $reported = $data[2];
                $reason = $data[3];
                $time = $data[4];
                $resolved = (bool)$data[5];
                return new StaffReport($reporter, $reported, $reason, intval($time), $resolved);
            case self::TYPE_HACK:
                $reported = $data[2];
                $reason = $data[3];
                $time = $data[4];
                $resolved = (bool)$data[5];
                return new HackReport($reporter, $reported, $reason, intval($time), $resolved);
            case self::TYPE_TRANSLATE:
                $language = $data[2];
                $original = $data[3];
                $new = $data[4];
                $time = $data[5];
                $resolved = (bool)$data[6];
                return new TranslateReport($reporter, $language, $original, $new, intval($time), $resolved);
        }

        return null;
    }


    /**
     * @param int $type
     * @param Language|null $lang
     * @return string
     *
     * Gets the type of reports.
     */
    public static function getReportsType(int $type, Language $lang = null) : string {

        $type = $type % 4;

        $playerManager = MineceitCore::getPlayerHandler();
        $lang = $lang ?? $playerManager->getLanguage();

        switch($type) {
            case self::TYPE_BUG:
                return TextFormat::clean($lang->getMessage(Language::BUG_REPORT_FORM_TITLE));
            case self::TYPE_STAFF:
                return TextFormat::clean($lang->getMessage(Language::STAFF_REPORT_FORM_TITLE));
            case self::TYPE_HACK:
                return TextFormat::clean($lang->getMessage(Language::HACK_REPORT_FORM_TITLE));
            case self::TYPE_TRANSLATE:
                return TextFormat::clean($lang->getMessage(Language::TRANSLATION_REPORT_FORM_TITLE));
        }

        return "";
    }


    /**
     * @return array
     *
     * Serializes so that we can use it to compare.
     */
    abstract public function serialize() : array;
}