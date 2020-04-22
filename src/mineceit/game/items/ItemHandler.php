<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-24
 * Time: 16:55
 */

declare(strict_types=1);

namespace mineceit\game\items;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;

class ItemHandler
{

    public const HUB_PLAY_FFA = 'hub.ffa';
    public const HUB_PLAY_UNRANKED_DUELS = 'hub.duels.unranked';
    public const HUB_PLAY_RANKED_DUELS = 'hub.duels.ranked';
    public const HUB_PLAYER_SETTINGS = 'hub.settings';
    public const HUB_LEAVE_QUEUE = 'hub.leave-queue';
    public const HUB_DUEL_HISTORY = 'hub.duels.history';
    public const HUB_REQUEST_INBOX = 'hub.request.inbox';
    public const HUB_PARTY_ITEM = 'hub.party';
    public const HUB_REPORTS_ITEM = 'hub.report';

    public const HUB_PLAY = 'hub.play';

    public const PARTY_SETTINGS = 'party.settings';
    public const PARTY_LEAVE = 'party.leave';

    public const PLAY_REPLAY = 'replay.play';
    public const PAUSE_REPLAY = 'replay.pause';
    public const REWIND_REPLAY = 'replay.rewind';
    public const FAST_FORWARD_REPLAY = 'replay.fast_forward';

    public const SPEC_LEAVE = 'spec.leave';

    /* @var MineceitItem[]|array */
    private $items;

    /** string[]|array */
    private $hubKeys;

    /** @var string[]|array */
    private $partyKeys;

    /** @var string[]|array */
    private $replayKeys;

    public function __construct()
    {
        $this->items = [];
        $this->hubKeys = [];
        $this->partyKeys = [];
        $this->replayKeys = [];
        $this->initItems();

    }

    /**
     * Initializes the items.
     */
    private function initItems() : void {
        $this->initItemsWithActions();
        $this->registerNewItems();
    }

    /**
     * Initializes the items with actions.
     */
    private function initItemsWithActions() : void {

        $this->registerItem(0, self::HUB_PLAY, Item::get(Item::NETHER_STAR, 0, 1));

        $this->registerItem(2, self::HUB_REPORTS_ITEM, Item::get(Item::PAPER, 0, 1));

        $this->registerItem(4, self::HUB_PLAYER_SETTINGS, Item::get(Item::CLOCK, 0, 1));
        $this->registerItem(5, self::HUB_DUEL_HISTORY, Item::get(Item::BOOK, 0, 1));
        $this->registerItem(6, self::HUB_REQUEST_INBOX, Item::get(Item::CHEST, 0, 1));

        /* $this->registerItem(0, self::HUB_PLAY_FFA, Item::get(Item::NETHER_STAR, 0, 1));
        $this->registerItem(1, self::HUB_PLAY_UNRANKED_DUELS, Item::get(Item::NETHER_STAR, 0, 1));
        $this->registerItem(2, self::HUB_PLAY_RANKED_DUELS, Item::get(Item::NETHER_STAR, 0, 1)); */

        if(MineceitCore::PARTIES_ENABLED) {
            $this->registerItem(7, self::HUB_PARTY_ITEM, Item::get(Item::TOTEM, 0, 1));
        }

        $this->registerItem(8, self::HUB_LEAVE_QUEUE, Item::get(Item::REDSTONE_DUST, 0, 1));
        $this->registerItem(8, self::SPEC_LEAVE, Item::get(Item::DYE, 1, 1));

        $this->registerItem(0, self::PARTY_SETTINGS, Item::get(Item::REPEATER, 0, 1));
        $this->registerItem(8, self::PARTY_LEAVE, Item::get(Item::REDSTONE_DUST, 0, 1));
        $this->registerItem(0, self::PLAY_REPLAY, Item::get(Item::DYE, 10, 1));
        $this->registerItem(0, self::PAUSE_REPLAY, Item::get(Item::DYE, 8, 1));
        $this->registerItem(3, self::REWIND_REPLAY, Item::get(Item::CLOCK));
        $this->registerItem(5, self::FAST_FORWARD_REPLAY, Item::get(Item::CLOCK));
    }


    /**
     * Registers new items to the item factory.
     */
    private function registerNewItems() : void {

        // Golden apple.
        ItemFactory::registerItem(new GoldenApple(), true);
        Item::addCreativeItem(GoldenApple::create());
    }


    /**
     * @param int $slot
     * @param string $localName
     * @param Item $item
     */
    private function registerItem(int $slot, string $localName, Item $item) : void {

        $item = new MineceitItem($slot, $localName, $item);
        $this->items[$localName] = $item;

        if(strpos($localName, 'hub.') !== false) {
            $this->hubKeys[] = $localName;
        } elseif (strpos($localName, 'party.') !== false) {
            $this->partyKeys[] = $localName;
        } elseif (strpos($localName, 'replay.') !== false) {
            $this->replayKeys[] = $localName;
        }
    }

    /**
     * @param Item $item
     * @return MineceitItem|null
     */
    public function getItem(Item $item) {
        foreach($this->items as $mineceitItem) {
            if($this->compareItems($mineceitItem, $item)) {
                return $mineceitItem;
            }
        }
        return null;
    }

    /**
     * @param MineceitItem $i1
     * @param Item $i2
     * @return bool
     */
    private function compareItems(MineceitItem $i1, Item $i2) : bool {
        $name = $i2->getName();
        if($i1->getItem()->getId() === $i2->getId()) {
            return $i1->isName($name);
        }

        return false;
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $clearInv
     */
    public function spawnHubItems(MineceitPlayer $player, bool $clearInv = true) : void {

        $inv = $player->getInventory();

        if($clearInv) {
            $player->clearInventory();
        }

        $lang = $player->getLanguage();
        $locale = $lang->getLocale();

        $inQueue = $player->isInQueue();

        foreach($this->hubKeys as $localName) {

            $item = $this->items[$localName];
            $slot = $item->getSlot();

            if(!$inQueue and strpos($localName, '.leave-queue') !== false) {
                continue;
            }

            $name = $item->getName($locale);
            $i = clone $item->getItem()->setCustomName($name);
            $inv->setItem($slot, $i);
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $clearInv
     */
    public function spawnEventItems(MineceitPlayer $player, bool $clearInv = true) : void {

        $inv = $player->getInventory();

        if($clearInv) {
            $player->clearInventory();
        }

        $lang = $player->getLanguage();

        $keys = [self::HUB_PLAYER_SETTINGS => 4, self::SPEC_LEAVE => 8];

        foreach($keys as $key => $slot) {
            $item = $this->items[$key];
            if($item instanceof MineceitItem) {
                $name = $item->getName($lang->getLocale());
                $i = clone $item->getItem()->setCustomName($name);
                $inv->setItem($slot, $i);
            }
        }
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $clearInv
     */
    public function spawnPartyItems(MineceitPlayer $player, bool $clearInv = true) : void {

        $partyManager = MineceitCore::getPartyManager();

        $party = $partyManager->getPartyFromPlayer($player);

        if($party === null) return;

        $inv = $player->getInventory();

        if($clearInv) {
            $player->clearInventory();
        }

        $lang = $player->getLanguage();
        $locale = $lang->getLocale();

        $sendSettings = $party->isOwner($player);

        $partyKeys = array_merge($this->partyKeys, [self::HUB_PLAYER_SETTINGS]);

        foreach($partyKeys as $localName) {

            $item = $this->items[$localName];
            if($localName === self::PARTY_SETTINGS and !$sendSettings) {
                continue;
            }

            $slot = $item->getSlot();
            $name = $item->getName($locale);
            $i = clone $item->getItem()->setCustomName($name);
            $inv->setItem($slot, $i);
        }
    }


    /**
     * @param MineceitPlayer $player
     * @param bool $clearInv
     *
     * Gives the player the replay items.
     */
    public function spawnReplayItems(MineceitPlayer $player, bool $clearInv = true) : void {

        $inv = $player->getInventory();

        if($clearInv) {
            $player->clearInventory();
        }

        $lang = $player->getLanguage();
        $locale = $lang->getLocale();

        $replayKeys = array_merge($this->replayKeys, [self::SPEC_LEAVE]);

        foreach($replayKeys as $localName) {

            $item = $this->items[$localName];
            $slot = $item->getSlot();

            if($localName === self::PLAY_REPLAY) {
                continue;
            }

            $name = $item->getName($locale);
            $i = clone $item->getItem()->setCustomName($name);
            $inv->setItem($slot, $i);
        }
    }

    /**
     * @param MineceitPlayer $player
     */
    public function givePlayItem(MineceitPlayer $player) : void {

        $item = $this->items[self::PLAY_REPLAY];

        $i = clone $item->getItem()->setCustomName($item->getName(
            $player->getLanguage()->getLocale())
        );

        $player->getInventory()->setItem($item->getSlot(), $i);
    }

    /**
     * @param MineceitPlayer $player
     */
    public function givePauseItem(MineceitPlayer $player) : void {

        $item = $this->items[self::PAUSE_REPLAY];

        $i = clone $item->getItem()->setCustomName($item->getName(
            $player->getLanguage()->getLocale())
        );

        $player->getInventory()->setItem($item->getSlot(), $i);
    }

    /**
     * @param MineceitPlayer $player
     */
    public function addLeaveQueueItem(MineceitPlayer $player) : void {

        if(!$player->isInQueue()) {
            return;
        }

        $item = $this->items[self::HUB_LEAVE_QUEUE];

        $i = clone $item->getItem()->setCustomName($item->getName(
            $player->getLanguage()->getLocale())
        );

        $player->getInventory()->setItem($item->getSlot(), $i);
    }

    /**
     * @param MineceitPlayer $player
     */
    public function removeQueueItem(MineceitPlayer $player) : void {

        if($player->isInQueue()) {
            return;
        }

        $inv = $player->getInventory();

        $item = $inv->getItem(8);

        $mineceitItem = $this->getItem($item);

        if($mineceitItem !== null and $mineceitItem->getLocalName() === self::HUB_LEAVE_QUEUE) {
            $inv->setItem(8, Item::get(Item::AIR));
        }
    }

    /**
     * @param MineceitPlayer $player
     */
    public function giveLeaveDuelItem(MineceitPlayer $player)
    {
        $item = $this->items[self::SPEC_LEAVE];

        $player->clearInventory();

        $player->getInventory()->setItem($item->getSlot(), clone $item->getItem()->setCustomName($item->getName(
            $player->getLanguage()->getLocale()
        )));
    }
}