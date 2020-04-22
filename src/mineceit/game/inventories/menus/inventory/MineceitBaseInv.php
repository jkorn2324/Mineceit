<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 18:37
 */

declare(strict_types=1);

namespace mineceit\game\inventories\menus\inventory;

use mineceit\game\inventories\InventoryTask;
use mineceit\game\inventories\menus\BaseMenu;
use mineceit\game\inventories\menus\data\MineceitHolderData;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\inventory\ContainerInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\Player;

abstract class MineceitBaseInv extends ContainerInventory {

    private $size;

    const HEIGHT_ABOVE = 3;

    protected $sendDelay = 0;

    private $holders = [];

    private $menu;

    public function __construct(BaseMenu $menu, array $items = [], int $size = 0, string $title = null) {
        parent::__construct(new Vector3(), $items, $size, $title);
        $this->size = $size;
        $this->menu = $menu;
    }

    public function getMenu() : BaseMenu {
        return $this->menu;
    }

    abstract public function getTEId() : string;

    public function send(MineceitPlayer $player, ?string $customName) : bool {

        $pos = $player->getPosition()->floor()->add(0, self::HEIGHT_ABOVE, 0);

        $result = false;

        if($player->getLevel()->isInWorld($pos->x, $pos->y, $pos->z)) {

            $this->holders[$player->getId()] = new MineceitHolderData($pos, $customName);

            $this->sendPrivateInv($player, $this->holders[$player->getId()]);

            $result = true;
        }

        return $result;
    }

    public function onOpen(Player $who): void {

        $data = $this->holders[$who->getId()];

        if($data instanceof MineceitHolderData) {
            $this->holder = $data->getPos();
        }

        parent::onOpen($who);
        $this->holder = null;
    }

    public function onClose(Player $who): void {

        $holder = $this->holders[$who->getId()];

        if(isset($holder) and $holder instanceof MineceitHolderData){

            $pos = $holder->getPos();

            if($who->getLevel()->isChunkLoaded($pos->x >> 4, $pos->z >> 4)){

                $this->sendPublicInv($who, $holder);
            }

            unset($holder);

            parent::onClose($who);

        }
    }

    public function open(Player $player) : bool{

        if(!isset($this->holders[$player->getId()])){
            return false;
        }

        return parent::open($player);
    }

    public function setSendDelay(int $delay) : void {
        $this->sendDelay = $delay;
    }

    public function getSendDelay(MineceitPlayer $player) : int {
        return $this->sendDelay;
    }

    abstract function sendPrivateInv(Player $player, MineceitHolderData $data) : void;

    abstract function sendPublicInv(Player $player, MineceitHolderData $data) : void;

    public function onInventorySend(MineceitPlayer $player) : void {

        $delay = $this->getSendDelay($player);

        if($delay > 0)
            MineceitCore::getInstance()->getScheduler()->scheduleDelayedTask(new InventoryTask($player, $this), $delay);
        else $this->onSendInvSuccess($player);
    }

    public function onSendInvSuccess(MineceitPlayer $player) : void {

        $playerHandler = MineceitCore::getPlayerHandler();

        $id = $playerHandler->getOpenChestID($player);

        if($playerHandler->setClosedInventoryID($id, $player))
            $player->addWindow($this, $id);
        else $this->onSendInvFail($player);
    }

    public function onSendInvFail(MineceitPlayer $player) : void {
        unset($this->holders[$player->getId()]);
    }

    public function getDefaultSize(): int {
        return $this->size;
    }

    protected function sendTileEntity(Player $player, Vector3 $pos, CompoundTag $tag) : void {
        $writer = new NetworkLittleEndianNBTStream();
        $tag->setString('id', $this->getTEId());
        $pkt = new BlockActorDataPacket();
        $pkt->x = $pos->x;
        $pkt->y = $pos->y;
        $pkt->z = $pos->z;
        $pkt->namedtag = $writer->write($tag);
        $player->sendDataPacket($pkt);
    }
}