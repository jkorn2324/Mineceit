<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-31
 * Time: 21:25
 */

namespace mineceit\game\inventories\menus;


use mineceit\game\inventories\menus\inventory\DoubleChestInv;
use mineceit\MineceitCore;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\utils\TextFormat;

class PostMatchInv extends BaseMenu
{

    /**
     * PostMatchInv constructor.
     * @param DuelInfo $info
     * @param Language $lang
     */
    public function __construct(DuelInfo $info, Language $lang)
    {
        parent::__construct(new DoubleChestInv($this));
        $playerName = $info->getPlayerName();

        $name = $lang->formWindow(Language::DUEL_INVENTORY_TITLE, [
            "name" => TextFormat::BLUE . $playerName
        ]);

        $this->setName($name);
        $this->setEdit(false);

        $allItems = [];

        $count = 0;

        $row = 0;

        $maxRows = 3;

        $items = $info->getItems();

        foreach($items as $item) {

            $currentRow = $maxRows - $row;
            $v = ($currentRow + 1) * 9;

            if($row === 0) {
                $v = $v - 9;
                $val = intval(($count % 9) + $v);
            } else $val = $count - 9;

            if($val != -1) $allItems[$val] = $item;

            $count++;

            if($count % 9 == 0 and $count != 0) $row++;
        }

        $row = $maxRows + 1;
        $lastRowIndex = ($row + 1) * 9;
        $secLastRowIndex = $row * 9;

        $armorItems = $info->getArmor();

        foreach($armorItems as $armor) {
            $allItems[$secLastRowIndex] = $armor;
            $secLastRowIndex++;
        }

        $statsItems = $info->getStatsItems();

        foreach($statsItems as $statsItem) {
            $allItems[$lastRowIndex] = $statsItem;
            $lastRowIndex++;
        }

        $keys = array_keys($allItems);

        foreach($keys as $index) {
            $index = intval($index);
            $item = $allItems[$index];
            $this->getInventory()->setItem($index, $item);
        }
    }

    /**
     * @param MineceitPlayer $p
     * @param SlotChangeAction $action
     */
    public function onItemMoved(MineceitPlayer $p, SlotChangeAction $action): void {}

    /**
     * @param MineceitPlayer $player
     */
    public function onInventoryClosed(MineceitPlayer $player): void {
        MineceitCore::getPlayerHandler()->setOpenInventoryID($player);
    }

    /**
     * @param MineceitPlayer $player
     */
    public function sendTo(MineceitPlayer $player): void
    {
        if($player->isOnline()) $this->send($player);
    }
}