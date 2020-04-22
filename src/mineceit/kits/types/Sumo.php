<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 15:05
 */

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\AbstractKit;
use mineceit\kits\Kits;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;

class Sumo extends AbstractKit
{

    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Sumo',
            [MineceitUtil::createItem(Item::STEAK, 0, 64)],
            [],
            [new EffectInstance(Effect::getEffect(Effect::RESISTANCE), MineceitUtil::hoursToTicks(1), 10)],
            $xkb, $ykb, $speed, 'textures/items/stick.png');

        $this->duelOnly = true;
        $this->worldType = WorldReplayData::TYPE_SUMO;
    }
}