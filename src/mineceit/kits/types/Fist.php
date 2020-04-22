<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 15:03
 */

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\AbstractKit;
use pocketmine\item\Item;

class Fist extends AbstractKit
{

    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Fist', [Item::get(Item::STEAK, 0, 64)], [], [], $xkb, $ykb, $speed, 'textures/items/beef_cooked.png');
    }
}