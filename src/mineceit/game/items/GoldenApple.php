<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-16
 * Time: 15:53
 */

declare(strict_types=1);

namespace mineceit\game\items;

use mineceit\MineceitUtil;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\GoldenApple as PMGoldenApple;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;


class GoldenApple extends PMGoldenApple
{

    public function __construct()
    {
        parent::__construct(2);
    }

    public function getAdditionalEffects(): array
    {
        if($this->getDamage() === 0) {
            return [
                new EffectInstance(Effect::getEffect(Effect::REGENERATION), 100, 1),
                new EffectInstance(Effect::getEffect(Effect::ABSORPTION), MineceitUtil::minutesToTicks(2))
            ];
        } else {
            return [
                new EffectInstance(Effect::getEffect(Effect::REGENERATION), MineceitUtil::secondsToTicks(10), 1),
                new EffectInstance(Effect::getEffect(Effect::ABSORPTION), MineceitUtil::minutesToTicks(2))
            ];
        }
    }

    /**
     *
     * @param bool $head
     * @param int $count
     * @return Item
     *
     * Creates a Golden Head.
     */
    public static function create(bool $head = true, int $count = 1) : Item {
        return Item::get(Item::GOLDEN_APPLE, !$head ? 0 : 1, $count)->setCustomName(TextFormat::GOLD . "Golden Head");
    }
}