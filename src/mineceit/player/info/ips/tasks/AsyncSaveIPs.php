<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-10
 * Time: 17:25
 */

declare(strict_types=1);

namespace mineceit\player\info\ips\tasks;


use pocketmine\scheduler\AsyncTask;

class AsyncSaveIPs extends AsyncTask
{

    /** @var string */
    private $ipsFile;
    /** @var string */
    private $tzFile;
    /** @var string */
    private $aliasFile;

    /** @var array */
    private $data;
    /** @var array */
    private $tzData;
    /** @var array */
    private $aliasData;


    public function __construct(string $file, array $data, string $tzFile, array $tzData, string $aliasFile, array $aliases)
    {
        $this->ipsFile = $file;
        $this->data = $data;
        $this->tzFile = $tzFile;
        $this->tzData = $tzData;
        $this->aliasFile = $aliasFile;
        $this->aliasData = $aliases;
    }

    /**
     * Runs this code async to main thread.
     */
    public function onRun()
    {

        // Time zone file.
        $file = fopen($this->tzFile, 'w');
        $data = (array)$this->tzData;

        foreach($data as $ip => $tz) {
            $tzData = (array)$tz;
            $timeZone = $tzData['tz'];
            $is24Hour = $tzData['24-hr'];
            fputcsv($file, [$ip, (string)$timeZone, (bool)$is24Hour]);
        }
        fclose($file);

        $safeIps = (array)$this->data;
        $file = fopen($this->ipsFile, 'w');
        foreach($safeIps as $ip) {
            fwrite($file, strval($ip) . "\n");
        }

        fclose($file);

        $aliases = (array)$this->aliasData;
        $results = [];
        foreach($aliases as $ip => $igns) {
            $results[$ip] = (array)$igns;
        }

        yaml_emit_file($this->aliasFile, $results);

    }
}