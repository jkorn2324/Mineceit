<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 14:29
 */

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\AbstractKit;
use pocketmine\item\Item;

class Gapple extends AbstractKit
{

    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Gapple',
            [Item::get(Item::DIAMOND_SWORD), Item::get(Item::GOLDEN_APPLE, 0, 64), Item::get(Item::STEAK, 0, 64)],
            [Item::get(Item::DIAMOND_HELMET), Item::get(Item::DIAMOND_CHESTPLATE), Item::get(Item::DIAMOND_LEGGINGS), Item::get(Item::DIAMOND_BOOTS)],
            [], $xkb, $ykb, $speed, 'textures/items/apple_golden.png');
    }
}