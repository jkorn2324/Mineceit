<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-16
 * Time: 23:30
 */

declare(strict_types=1);

namespace mineceit\player\info\alias\tasks;


use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadAliases extends AsyncTask
{

    /** @var string */
    private $uuidFile;

    public function __construct(string $cidFile, string $uuidFile)
    {
        // $this->cidFile = $cidFile;
        $this->uuidFile = $uuidFile;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        /* if(!file_exists($this->cidFile)) {
            $file = fopen($this->cidFile, 'wb');
            fclose($file);
        } */

        if(!file_exists($this->uuidFile)) {
            $file = fopen($this->uuidFile, 'wb');
            fclose($file);
        }

        $uuidAliases = yaml_parse_file($this->uuidFile, 0) ?? [];

        $this->setResult(['uuid' => $uuidAliases]);
    }

    public function onCompletion(Server $server)
    {
        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled()) {

            $playerManager = MineceitCore::getPlayerHandler();

            $aliasManager = $playerManager->getAliasManager();

            if($result !== null) {

                $uuid = $result['uuid'];

                $aliasManager->loadAliases($uuid);
            }
        }
    }
}