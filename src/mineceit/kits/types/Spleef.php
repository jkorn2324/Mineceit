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
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class Spleef extends AbstractKit
{

    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Spleef',
            [
                MineceitUtil::createItem(Item::DIAMOND_SHOVEL, 0, 1, [
                    new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10),
                    new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10)
                ]),
                MineceitUtil::createItem(Item::STEAK, 0, 64)],
            [],
            [new EffectInstance(Effect::getEffect(Effect::RESISTANCE), MineceitUtil::hoursToTicks(1), 10)],
            $xkb, $ykb, $speed, 'textures/items/diamond_shovel.png');

        $this->duelOnly = true;
        $this->damageOthers = false;
        $this->replaysEnabled = false;
        $this->worldType = WorldReplayData::TYPE_SPLEEF;
    }
}