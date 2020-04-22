<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-12-26
 * Time: 22:35
 */

declare(strict_types=1);

namespace mineceit\player\info\device;


use mineceit\MineceitCore;

class DeviceInfo
{

    /** @var MineceitCore */
    private $core;

    /** @var string[]|array */
    private $deviceModels;


    public function __construct(MineceitCore $core)
    {
        $this->core = $core;

        $this->deviceModels = [];

        $file = $core->getResourcesFolder() . "device/device_models.json";

        if(file_exists($file)) {

            $contents = file_get_contents($file);

            $this->deviceModels = json_decode($contents, true);
        }
    }


    /**
     * @param string $model
     * @return string|null
     *
     * Device Model.
     */
    public function getDeviceFromModel(string $model) {

        if(isset($this->deviceModels[$model])) {
            return $this->deviceModels[$model];
        }

        return null;
    }

}