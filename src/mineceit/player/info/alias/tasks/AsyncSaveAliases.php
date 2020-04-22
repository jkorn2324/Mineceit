<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-17
 * Time: 00:08
 */

declare(strict_types=1);

namespace mineceit\player\info\alias\tasks;


use pocketmine\scheduler\AsyncTask;

class AsyncSaveAliases extends AsyncTask
{

    /** @var string */
    private $uuidFile;

    /** @var array */
    private $uuids;

    public function __construct(string $uuidFile, array $uuids)
    {
        $this->uuidFile = $uuidFile;
        $this->uuids = $uuids;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $uuids = (array)$this->uuids;
        $result = [];

        foreach($uuids as $uuid => $igns) {
            $result[strval($uuid)] = (array)$igns;
        }

        yaml_emit_file($this->uuidFile, $result);
    }
}