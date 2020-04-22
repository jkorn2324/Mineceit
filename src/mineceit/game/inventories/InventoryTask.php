<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 18:52
 */

declare(strict_types=1);

namespace mineceit\game\inventories;

use mineceit\game\inventories\menus\inventory\MineceitBaseInv;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\Task;

class InventoryTask extends Task
{

    /* @var MineceitPlayer */
    private $player;

    /* @var MineceitBaseInv */
    private $inventory;

    /**
     * InventoryTask constructor.
     * @param MineceitPlayer $player
     * @param MineceitBaseInv $inv
     */
    public function __construct(MineceitPlayer $player, MineceitBaseInv $inv) {
        $this->player = $player;
        $this->inventory = $inv;
    }

    /**
     * Actions to execute when run
     *
     * @param int $tick
     *
     * @return void
     */
    public function onRun(int $tick) {

        if($this->player->isOnline()) {
            $this->inventory->onSendInvSuccess($this->player);
        } else {
            $this->inventory->onSendInvFail($this->player);
        }
    }
}