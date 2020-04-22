<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-16
 * Time: 22:49
 */

declare(strict_types=1);

namespace mineceit\player\info\alias\tasks;


use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncSearchAliases extends AsyncTask
{
    /** @var string */
    private $name;

    /** @var string */
    private $hostIp;

    /** @var string */
    private $uuid;

    /** @var array */
    private $ipAliases;

    /** @var array */
    private $uuidAliases;

    /** @var bool */
    private $update;

    public function __construct(string $name, string $hostIp, string $uuid, array $ipAliases, array $uuidAliases, bool $update)
    {
        $this->name = $name;
        $this->hostIp = $hostIp;
        $this->uuid = $uuid;
        $this->ipAliases = $ipAliases;
        $this->uuidAliases = $uuidAliases;
        $this->update = $update;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {

        $ipAliases = (array)$this->ipAliases;
        $ipAliases = array_map(function($obj) {
            return (array)$obj;
        }, $ipAliases);

        $ipOutput = (array)$ipAliases[$this->hostIp];
        $resultIPOutput = $ipOutput;

        foreach($ipOutput as $ign) {
            foreach($ipAliases as $hostIp => $igns) {
                if(in_array($ign, (array)$igns)) {
                    $resultIPOutput = array_merge($resultIPOutput, $igns);
                }
            }
        }

        $ipOutput = array_values($resultIPOutput);

        $uuidAliases = (array)$this->uuidAliases;
        $uuidAliases = array_map(function($obj) {
            return (array)$obj;
        }, $uuidAliases);

        $uuidOutput = (array)$uuidAliases[$this->uuid];
        $resultUUIDOutput = $uuidOutput;

        foreach($uuidOutput as $ign) {
            foreach($uuidAliases as $uuid => $igns) {
                if(in_array($ign, (array)$igns)) {
                    $resultUUIDOutput = array_merge($resultUUIDOutput, $igns);
                }
            }
        }

        $uuidOutput = array_values($resultUUIDOutput);

        $this->setResult(['ip-aliases' => array_unique($ipOutput), 'uuid-aliases' => array_unique($uuidOutput)]);
    }


    public function onCompletion(Server $server)
    {
        $core = $server->getPluginManager()->getPlugin('Mineceit');

        $result = $this->getResult();

        if($core instanceof MineceitCore and $core->isEnabled()) {

            $playerHandler = MineceitCore::getPlayerHandler();
            $aliasManager = $playerHandler->getAliasManager();

            if($result !== null) {

                $ipAliases = (array)$result['ip-aliases'];
                $uuidAliases = (array)$result['uuid-aliases'];

                $aliasManager->setAliases($this->name, $uuidAliases, $ipAliases, $this->update);
            }
        }
    }
}