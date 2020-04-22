<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 20:00
 */

declare(strict_types=1);

namespace mineceit\game\inventories\menus;

use mineceit\game\inventories\menus\inventory\MineceitBaseInv;
use mineceit\player\MineceitPlayer;
use pocketmine\inventory\transaction\action\SlotChangeAction;

abstract class BaseMenu {

    private $edit;

    private $name;

    private $inv;

    public function __construct(MineceitBaseInv $inv) {
        $this->inv = $inv;
        $this->edit = true;
        $this->name = $inv->getName();
    }

    public function getInventory() : MineceitBaseInv {
        return $this->inv;
    }

    public function setName(string $name) : BaseMenu {
        $this->name = $name;
        return $this;
    }

    public function getName() : string {
        return $this->name;
    }

    public function setEdit(bool $edit) : BaseMenu {
        $this->edit = $edit;
        return $this;
    }

    public function canEdit() : bool {
        return $this->edit;
    }

    public function send(MineceitPlayer $player, ?string $customName = null) : bool {
        return $this->getInventory()->send($player, ($customName !== null ? $customName : $this->getName()));
    }

    abstract public function onItemMoved(MineceitPlayer $p, SlotChangeAction $action) : void;

    abstract public function onInventoryClosed(MineceitPlayer $player) : void;

    abstract public function sendTo(MineceitPlayer $player) : void;

}