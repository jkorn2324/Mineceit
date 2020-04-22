<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 19:40
 */

declare(strict_types=1);

namespace mineceit\game\inventories\menus\inventory;


use mineceit\game\inventories\menus\BaseMenu;
use mineceit\game\inventories\menus\data\MineceitHolderData;
use mineceit\player\MineceitPlayer;
use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Tile;

class DoubleChestInv extends MineceitBaseInv
{

    const CHEST_SIZE = 54;

    public function __construct(BaseMenu $menu, array $items = []) {
        parent::__construct($menu, $items, self::CHEST_SIZE, null);
    }

    public function getName(): string {
        return 'Mineceit Chest';
    }

    /**
     * Returns the Minecraft PE inventory type used to show the inventory window to clients.
     * @return int
     */
    public function getNetworkType(): int {
        return WindowTypes::CONTAINER;
    }

    public function getTEId(): string {
        return Tile::CHEST;
    }

    function sendPrivateInv(Player $player, MineceitHolderData $data): void {

        $block = Block::get(Block::CHEST)->setComponents($data->getPos()->x, $data->getPos()->y, $data->getPos()->z);
        $block2 = Block::get(Block::CHEST)->setComponents($data->getPos()->x + 1, $data->getPos()->y, $data->getPos()->z);

        $player->getLevel()->sendBlocks([$player], [$block, $block2]);

        $tag = new CompoundTag();
        if(!is_null($data->getCustomName())){
            $tag->setString('CustomName', $data->getCustomName());
        }

        $tag->setInt('pairz', $block->z);

        $tag->setInt('pairx', $block->x + 1);
        $this->sendTileEntity($player, $block, $tag);

        $tag->setInt('pairx', $block->x);
        $this->sendTileEntity($player, $block2, $tag);

        if($player instanceof MineceitPlayer) $this->onInventorySend($player);
    }

    function sendPublicInv(Player $player, MineceitHolderData $data): void {
        $player->getLevel()->sendBlocks([$player], [$data->getPos(), $data->getPos()->add(1, 0, 0)]);
    }

    /**
     * @param MineceitPlayer $pl
     * @return int
     */
    public function getSendDelay(MineceitPlayer $pl): int {

        $ping = $pl->getPing();

        return $ping < 280 ? 5 : 2;
    }
}