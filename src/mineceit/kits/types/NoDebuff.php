<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 15:08
 */

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\AbstractKit;
use mineceit\MineceitUtil;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class NoDebuff extends AbstractKit
{

    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('NoDebuff', [], [], [], $xkb, $ykb, $speed, 'textures/items/potion_bottle_splash_heal.png');

        $items = [
            MineceitUtil::createItem(276, 0, 1,
                [new EnchantmentInstance(Enchantment::getEnchantment(9), 2),
                 new EnchantmentInstance(Enchantment::getEnchantment(17), 3)]),
            MineceitUtil::createItem(368, 0, 16)
        ];

        for($i = 0; $i < 6; $i++) {
            $id = ($i !== 5) ? 438 : 373;
            $meta = ($i !== 5) ? 22 : 14;
            $items[] = MineceitUtil::createItem($id, $meta, 1);
        }

        $items[] = Item::get(Item::STEAK, 0, 64);

        $arr = [
            8 => true,
            17 => true,
            26 => true
        ];

        for($i = 0; $i < 27; $i++) {
            $id = (isset($arr[$i])) ? 373 : 438;
            $meta = (isset($arr[$i])) ? 14 : 22;
            $items[] = MineceitUtil::createItem($id, $meta, 1);
        }

        $this->items = $items;

        $e1 = new EnchantmentInstance(Enchantment::getEnchantment(0), 2);

        $e2 = new EnchantmentInstance(Enchantment::getEnchantment(17), 3);

        $helmet = MineceitUtil::createItem(310, 0, 1, [$e1, $e2]);

        $chest = MineceitUtil::createItem(311, 0, 1, [$e1, $e2]);

        $legs = MineceitUtil::createItem(312, 0, 1, [$e1, $e2]);

        $boots = MineceitUtil::createItem(313, 0, 1,
            [$e1, $e2, new EnchantmentInstance(Enchantment::getEnchantment(2), 4)]);

        $this->armor = [$helmet, $chest, $legs, $boots];

        $this->effects = [new EffectInstance(Effect::getEffect(Effect::SPEED), MineceitUtil::minutesToTicks(60))];
    }
}