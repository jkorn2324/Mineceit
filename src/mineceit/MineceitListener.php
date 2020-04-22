<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-07
 * Time: 13:50
 */

declare(strict_types=1);

namespace mineceit;

use mineceit\commands\MineceitCommand;
use mineceit\fixes\player\PMPlayer;
use mineceit\game\entities\ReplayHuman;
use mineceit\game\FormUtil;
use mineceit\game\inventories\menus\inventory\MineceitBaseInv;
use mineceit\game\items\ItemHandler;
use mineceit\network\requests\RequestPool;
use mineceit\player\info\skins\SkinInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\DelayScoreboardUpdate;
use mineceit\scoreboard\Scoreboard;
use mineceit\scoreboard\ScoreboardUtil;
use pocketmine\block\Liquid;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\SplashPotion;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitListener implements Listener
{

    /* @var Server */
    private $server;

    /* @var MineceitCore */
    private $core;

    public function __construct(MineceitCore $core)
    {
        $this->server = $core->getServer();
        $this->core = $core;
    }

    /**
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event): void
    {
        $player = MineceitPlayer::class;
        $base = PMPlayer::class;
        $event->setBaseClass($base);
        $event->setPlayerClass($player);
    }

    /**
     * @param PlayerChangeSkinEvent $event
     */
    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {

        $newSkin = $event->getNewSkin();

        $skinData = SkinAdapterSingleton::get()->toSkinData($newSkin);

        $skinData = new SkinInfo($skinData);

        if (!$skinData->isValidSkin()) {
            try {
                $skin = new Skin("Standard_Custom", str_repeat(random_bytes(3) . "\xff", 2048));
                $event->setNewSkin($skin);
            } catch (\Exception $e) {
                $event->setNewSkin($event->getOldSkin());
            }
        }
    }

    /**
     * @param PlayerPreLoginEvent $event
     */
    public function onPreLogin(PlayerPreLoginEvent $event): void
    {

        $player = $event->getPlayer();

        if ($player instanceof MineceitPlayer) {

            // $ip = $player->getAddress();
            $skinData = SkinAdapterSingleton::get()->toSkinData($player->getSkin());

            $skinData = new SkinInfo($skinData);

            if (!$skinData->isValidSkin()) {
                try {
                    $skin = new Skin("Standard_Custom", str_repeat(random_bytes(3) . "\xff", 2048));
                    $player->setSkin($skin);
                } catch (\Exception $e) {
                    $player->kick("Invalid skin.");
                    return;
                }
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event): void
    {

        $player = $event->getPlayer();

        $playerHandler = MineceitCore::getPlayerHandler();

        if ($player instanceof MineceitPlayer) {

            $address = $player->getAddress();
            if (MineceitCore::PROXY_ENABLED && $address !== "127.0.0.1") {
                $player->transfer("mineceit.ml", 19132);
            }


            if ($playerHandler->doKickOnJoin($player)) {
                $event->setJoinMessage("");
                return;
            }

            // $players = $this->server->getOnlinePlayers();

            /* foreach($players as $p) {
                if($p instanceof MineceitPlayer and $p->getName() !== $player->getName() and $p->isInHub()) {
                    if(!$p->isDisplayPlayers())
                        $p->hidePlayer($player);
                }
            } */

            $level = $this->server->getDefaultLevel();
            $pos = $level->getSpawnLocation();
            $player->teleport($pos);

            $playerHandler->loadPlayerData($player);

            $player->reset();
            $player->onJoin();

            ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::ONLINE_PLAYERS, $player);

            $event->setJoinMessage(MineceitUtil::getJoinMessage($player));
        }
    }

    /**
     * @param PlayerLoginEvent $event
     */
    public function onLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $ipManager = MineceitCore::getPlayerHandler()->getIPManager();
        if ($player instanceof MineceitPlayer) {
            $ipManager->checkIPSafe($player);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onLeave(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        if($player instanceof MineceitPlayer) {

            $duelHandler = MineceitCore::getDuelHandler();

            $playerHandler = MineceitCore::getPlayerHandler();

            $partyManager = MineceitCore::getPartyManager();

            if ($player->isInQueue()) {
                $duelHandler->removeFromQueue($player, false);
            }

            if ($player->isFrozen()) {
                $player->setLimitedFeatureMode(true, false);
            }

            if ($player->isInEvent()) {
                $theEvent = MineceitCore::getEventManager()->getEventFromPlayer($player);
                $theEvent->removePlayer($player, false);
            }

            if ($player->isInParty()) {

                $party = $partyManager->getPartyFromPlayer($player);

                $partyEvent = $partyManager->getEventManager()->getPartyEvent($party);

                if ($partyEvent !== null) {
                    $partyEvent->removeFromEvent($player);
                }

                $party->removePlayer($player);
            }

            if ($player->isInDuel()) {
                $duel = $duelHandler->getDuel($player);
                $opponent = $duel->getOpponent($player);
                if ($duel->isRunning() && $opponent !== null) {
                    $duel->setEnded($opponent);
                } elseif ($duel->isCountingDown()) {
                    $duel->setEnded(null, false);
                }
            }

            if ($player->isInCombat() && $player->isInArena()) {

                $lastDamageCause = $player->getLastDamageCause();

                if ($lastDamageCause !== null && $lastDamageCause instanceof EntityDamageByEntityEvent) {

                    $cause = $lastDamageCause->getDamager();

                    if ($cause instanceof MineceitPlayer && $cause->isOnline() && $cause->isInArena()) {

                        $cause->addKill();
                        $cause->setInCombat(false);
                        // TODO ADD CUSTOM DEATH MESSAGES
                        $message = MineceitUtil::getDeathMessage($player->getName(), $lastDamageCause);
                        $this->server->broadcastMessage($message);
                    }
                }
                $player->addDeath();
                $player->setInCombat(false, false);
            }

            $duelHandler->getRequestHandler()->removeAllRequestsWith($player);

            $playerHandler->savePlayerData($player);

            $event->setQuitMessage(MineceitUtil::getLeaveMessage($player));

            $this->core->getScheduler()->scheduleDelayedTask(new DelayScoreboardUpdate(ScoreboardUtil::ONLINE_PLAYERS, $player), 3);
        }
    }

    /**
     * @param PlayerKickEvent $event
     */
    public function onKick(PlayerKickEvent $event): void
    {

        $player = $event->getPlayer();

        $duelHandler = MineceitCore::getDuelHandler();

        $playerHandler = MineceitCore::getPlayerHandler();

        $eventManager = MineceitCore::getEventManager();

        $partyManager = MineceitCore::getPartyManager();

        if ($player instanceof MineceitPlayer) {

            if ($player->isInQueue()) {
                $duelHandler->removeFromQueue($player, false);
            }

            if ($player->isInEvent()) {
                $theEvent = $eventManager->getEventFromPlayer($player);
                $theEvent->removePlayer($player, false);
            }

            if ($player->isInParty()) {

                $party = $partyManager->getPartyFromPlayer($player);

                $eventManager = $partyManager->getEventManager();

                $partyEvent = $eventManager->getPartyEvent($party);

                if ($partyEvent !== null)
                    $partyEvent->removeFromEvent($player);

                $party->removePlayer($player);
            }

            if ($player->isInDuel()) {
                $duel = $duelHandler->getDuel($player);
                $duel->setEnded(null, false);
            }

            if ($player->isInCombat() and $player->isInArena()) {
                $lastDamageCause = $player->getLastDamageCause();
                if ($lastDamageCause !== null and $lastDamageCause instanceof EntityDamageByEntityEvent) {
                    $cause = $lastDamageCause->getDamager();
                    if ($cause instanceof MineceitPlayer and $cause->isOnline() and $cause->isInArena()) {
                        $cause->addKill();
                        $cause->setInCombat(false);
                    }
                }
                $player->setInCombat(false, false);
            }

            $duelHandler->getRequestHandler()->removeAllRequestsWith($player);

            $playerHandler->savePlayerData($player);

            $this->core->getScheduler()->scheduleDelayedTask(new DelayScoreboardUpdate(ScoreboardUtil::ONLINE_PLAYERS, $player), 3);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void
    {

        $action = $event->getAction();
        $player = $event->getPlayer();

        $item = $event->getItem();

        $itemHandler = MineceitCore::getItemHandler();

        $duelHandler = MineceitCore::getDuelHandler();

        if ($player instanceof MineceitPlayer) {

            if ($player->isPe() and $action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                $player->addCps(false);
            }

            $mineceitItem = $itemHandler->getItem($item);

            if ($mineceitItem !== null) {

                $localName = $mineceitItem->getLocalName();

                $partyManager = MineceitCore::getPartyManager();

                $eventManager = MineceitCore::getEventManager();

                if ($player->isInParty()) {

                    $party = $partyManager->getPartyFromPlayer($player);

                    switch ($localName) {
                        case ItemHandler::PARTY_LEAVE:
                            $form = FormUtil::getLeavePartyForm($player, $party->isOwner($player));
                            $player->sendFormWindow($form);
                            break;
                        case ItemHandler::PARTY_SETTINGS:
                            $form = FormUtil::getPartyOptionsForm($party);
                            $player->sendFormWindow($form);
                            break;
                        case ItemHandler::HUB_PLAYER_SETTINGS:
                            $form = FormUtil::getSettingsMenu($player);
                            $player->sendFormWindow($form);
                            break;
                    }

                } elseif ($player->isInEvent()) {

                    $theEvent = $eventManager->getEventFromPlayer($player);

                    switch ($localName) {
                        case ItemHandler::SPEC_LEAVE:
                            $theEvent->removePlayer($player);
                            break;
                        case ItemHandler::HUB_PLAYER_SETTINGS:
                            $form = FormUtil::getSettingsMenu($player);
                            $player->sendFormWindow($form);
                            break;
                    }

                } elseif ($player->isInHub()) {

                    switch ($localName) {
                        case ItemHandler::HUB_PLAY:
                            $form = FormUtil::getPlayForm($player);
                            $player->sendFormWindow($form);
                            break;
                        case ItemHandler::HUB_REPORTS_ITEM:
                            $perms = $player->getReportPermissions();
                            $form = FormUtil::getReportMenuForm($player, $perms);
                            $player->sendFormWindow($form, ["perms" => $perms]);
                            break;
                        /* case ItemHandler::HUB_PLAY_FFA:
                            $form = FormUtil::getFFAForm($player);
                            $player->$this->sendFormWindow($form);
                            break;
                        case ItemHandler::HUB_PLAY_UNRANKED_DUELS:
                            $form = FormUtil::getDuelForm($player, $language->formWindow(Language::DUELS_UNRANKED_FORM_TITLE), false);
                            $player->$this->sendFormWindow($form, ['ranked' => false]);
                            break;
                        case ItemHandler::HUB_PLAY_RANKED_DUELS:
                            $form = FormUtil::getDuelForm($player, $language->formWindow(Language::DUELS_RANKED_FORM_TITLE), true);
                            $player->$this->sendFormWindow($form, ['ranked' => true]);
                            break; */
                        case ItemHandler::HUB_PLAYER_SETTINGS:
                            $form = FormUtil::getSettingsMenu($player);
                            $player->sendFormWindow($form);
                            break;
                        case ItemHandler::HUB_LEAVE_QUEUE:
                            $duelHandler->removeFromQueue($player);
                            break;
                        case ItemHandler::HUB_DUEL_HISTORY:
                            $form = FormUtil::getDuelHistoryForm($player);
                            $player->sendFormWindow($form);
                            break;
                        case ItemHandler::HUB_REQUEST_INBOX:
                            $requestHandler = MineceitCore::getDuelHandler()->getRequestHandler();
                            $requests = $requestHandler->getRequestsOf($player);
                            $form = FormUtil::getDuelInbox($player, $item->getName(), $requests);
                            $player->sendFormWindow($form, ['requests' => $requests]);
                            break;
                        case ItemHandler::HUB_PARTY_ITEM:
                            $form = FormUtil::getDefaultPartyForm($player);
                            $party = $partyManager->getPartyFromPlayer($player);
                            $option = $party !== null ? 'leave' : 'join';
                            $player->sendFormWindow($form, ['party-option' => $option]);
                            break;
                    }

                } elseif ($player->isADuelSpec()) {

                    $duel = $duelHandler->getDuelFromSpec($player);

                    switch ($localName) {
                        case ItemHandler::SPEC_LEAVE:
                            $duel->removeSpectator($player);
                            $player->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                            break;
                    }

                } elseif ($player->isWatchingReplay()) {

                    $replayManager = MineceitCore::getReplayManager();

                    $replay = $replayManager->getReplayFrom($player);

                    switch ($localName) {
                        case ItemHandler::SPEC_LEAVE:
                            $replay->endReplay();
                            break;
                        case ItemHandler::PAUSE_REPLAY:
                            $replay->setPaused(true);
                            break;
                        case ItemHandler::PLAY_REPLAY:
                            $replay->setPaused(false);
                            break;
                        case ItemHandler::REWIND_REPLAY:
                            $replay->rewind(5);
                            break;
                        case ItemHandler::FAST_FORWARD_REPLAY:
                            $replay->fastForward(5);
                            break;
                    }
                }

                $event->setCancelled();

            } elseif (!$player->isInHub()) {

                $id = $item->getId();

                if ($id === Item::FISHING_ROD) {

                    $use = false;
                    if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK or $action === PlayerInteractEvent::RIGHT_CLICK_AIR) {
                        if ($player->getDeviceOS() === MineceitPlayer::WINDOWS_10)
                            $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
                        else $use = true;
                    }

                    if ($use) $player->useRod($item);
                    else $event->setCancelled();

                } elseif ($id === Item::ENDER_PEARL and $item instanceof EnderPearl) {

                    $use = false;
                    if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK or $action === PlayerInteractEvent::RIGHT_CLICK_AIR) {
                        if ($player->getDeviceOS() === MineceitPlayer::WINDOWS_10)
                            $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
                        else $use = true;
                    }

                    if ($use and $player->canThrowPearl())
                        $player->throwPearl($item);

                    $event->setCancelled();

                } elseif ($id === Item::SPLASH_POTION and $item instanceof SplashPotion) {

                    $use = false;
                    if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK or $action === PlayerInteractEvent::RIGHT_CLICK_AIR) {
                        if ($player->getDeviceOS() === MineceitPlayer::WINDOWS_10)
                            $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
                        else $use = true;
                    }

                    if ($use) {
                        $player->throwPotion($item);
                        if ($action === PlayerInteractEvent::RIGHT_CLICK_AIR)
                            $event->setCancelled();
                    } else $event->setCancelled();

                } elseif ($player->isInDuel() and $id === Item::BUCKET AND $item->getDamage() === 0) {
                    $blockClicked = $event->getBlock();
                    $duel = $duelHandler->getDuel($player);
                    if ($blockClicked !== null and ($blockClicked instanceof Liquid))
                        $duel->setLiquidAt(0, $blockClicked);
                }
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function onEntityDamaged(EntityDamageEvent $event): void
    {

        $cancel = false;

        $e = $event->getEntity();

        $cause = $event->getCause();

        if ($e instanceof MineceitPlayer) {

            if ($cause === EntityDamageEvent::CAUSE_FALL)
                $cancel = true;
            elseif ($e->isFrozen()) {
                $cancel = true;
            } elseif ($e->isSpectator() or $e->isADuelSpec() or $e->isWatchingReplay()) {
                $cancel = true;
            } elseif ($e->isImmobile() and $e->isInDuel()) {
                $duel = MineceitCore::getDuelHandler()->getDuel($e);
                $cancel = $duel->isCountingDown();
            } elseif ($e->isInEvent() and !$e->isInEventDuel()) {
                $cancel = true;
            }

            if ($cancel) {
                $event->setCancelled();
                return;
            }

            if ($e->isInHub()) {

                $cancel = !$e->isInEventDuel();

                if ($cause === EntityDamageEvent::CAUSE_VOID) {
                    $level = $this->server->getDefaultLevel();
                    $center = $level->getSpawnLocation();
                    $e->teleport($center);
                    $cancel = true;
                }
            }

        } elseif ($e instanceof ReplayHuman)
            $cancel = true;

        $event->setCancelled($cancel);

    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onEntityDamagedByEntity(EntityDamageByEntityEvent $event): void
    {

        $cancel = false;

        $e = $event->getEntity();

        if ($e instanceof MineceitPlayer) {

            $cancel = $e->isInHub() or $e->isSpectator() or $e->isADuelSpec() or ($e->isInEvent() and !$e->isInEventDuel());

            if ($event->getDamager() instanceof MineceitPlayer) {
                $e->disableForChecks(1500);
            }
        }

        $event->setCancelled($cancel);
    }


    /**
     * @param EntityDamageByChildEntityEvent $event
     */
    public function onEntityDamagedByChildEntity(EntityDamageByChildEntityEvent $event): void
    {

        $child = $event->getChild();
        $e = $event->getDamager();

        if ($child instanceof Arrow and $e instanceof MineceitPlayer) {

            $cancel = $e->isInHub() or $e->isSpectator() or $e->isADuelSpec() or ($e->isInEvent() and !$e->isInEventDuel());

            if (!$cancel) {
                MineceitUtil::sendArrowDingSound($e);
            }

            $event->setCancelled($cancel);
        }
    }

    /**
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {

        $player = $event->getPlayer();

        $block = $event->getBlock();

        $cancel = true;

        if ($player instanceof MineceitPlayer) {

            if ($player->isInDuel()) {
                $duel = MineceitCore::getDuelHandler()->getDuel($player);
                $cancel = !$duel->canPlaceBlock($block);
                if (!$cancel) {
                    $duel->setBlockAt($block);
                }
            } elseif ($player->canBuild()) {
                $cancel = $player->isSpectator() or $player->isFrozen() or $player->isInParty() or $player->isWatchingReplay() or $player->isInArena() or $player->isInEvent();
            }
        }
        $event->setCancelled($cancel);
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {

        $player = $event->getPlayer();

        $block = $event->getBlock();

        $cancel = true;

        if ($player->isSpectator()) {
            $event->setCancelled(true);
            return;
        }

        if ($player instanceof MineceitPlayer) {

            if ($player->isInDuel()) {
                $duel = MineceitCore::getDuelHandler()->getDuel($player);
                $cancel = !$duel->canPlaceBlock($block, true);
                if (!$cancel) {
                    $duel->setBlockAt($block, true);
                    if ($duel->isSpleef()) {
                        $drops = $event->getDrops();
                        foreach ($drops as $drop) {
                            $player->getInventory()->addItem($drop);
                        }
                        $event->setDrops([]);
                    }
                }
            } elseif ($player->canBuild()) {
                $cancel = $player->isSpectator() or $player->isFrozen() or $player->isInParty() or $player->isWatchingReplay() or $player->isInArena();
            }
        }
        $event->setCancelled($cancel);
    }

    public function onBucketEmpty(PlayerBucketEmptyEvent $event): void
    {

        $player = $event->getPlayer();

        $cancel = true;

        if ($player instanceof MineceitPlayer) {

            if ($player->isInDuel()) {
                $duel = MineceitCore::getDuelHandler()->getDuel($player);
                $cancel = !$duel->isRunning() and $duel->getQueue() !== 'Sumo';
                if (!$cancel) {
                    $blockClicked = $event->getBlockClicked();
                    $bucket = $event->getBucket();
                    $duel->setLiquidAt($bucket->getDamage(), $blockClicked);
                }
            } elseif ($player->canBuild()) {
                $cancel = $player->isSpectator() or $player->isFrozen() or $player->isInParty() or $player->isWatchingReplay() or $player->isInArena() or $player->isInEvent();
            }
        }

        $event->setCancelled($cancel);
    }

    /**
     * @param PlayerBucketFillEvent $event
     */
    public function onBucketFill(PlayerBucketFillEvent $event): void
    {

        $player = $event->getPlayer();

        $cancel = true;

        if ($player instanceof MineceitPlayer) {

            if ($player->isInDuel()) {
                $duel = MineceitCore::getDuelHandler()->getDuel($player);
                $cancel = !$duel->isRunning() and $duel->getQueue() !== 'Sumo';
            } elseif ($player->canBuild()) {
                $cancel = $player->isSpectator() or $player->isFrozen() or $player->isInParty() or $player->isWatchingReplay() or $player->isInArena() or $player->isInEvent();
            }
        }

        $event->setCancelled($cancel);
    }

    /**
     * @param BlockSpreadEvent $event
     */
    public function onSpread(BlockSpreadEvent $event): void
    {

        $block = $event->getNewState();

        $pos = $event->getBlock();

        $level = $pos->getLevel();

        $duelHandler = MineceitCore::getDuelHandler();

        $replayHandler = MineceitCore::getReplayManager();

        $duel = $level !== null ? $duelHandler->getDuelFromLevel($level->getName()) : null;

        $cancel = true;

        if ($duel !== null and $block instanceof Liquid) {
            $duel->setLiquidAt($block->getId(), $pos, $block->getDamage());
            $cancel = false;
        } elseif (($replay = $replayHandler->getReplayFromLevel($level)) !== null) {
            $cancel = $replay->isPaused();
        }

        $event->setCancelled($cancel);
    }


    /**
     * @param BlockFormEvent $event
     */
    public function onBlockForm(BlockFormEvent $event): void
    {

        $block = $event->getBlock();

        $newState = $event->getNewState();

        $duelHandler = MineceitCore::getDuelHandler();

        $level = $block->getLevel();

        if ($level !== null and ($duel = $duelHandler->getDuelFromLevel($level->getName())) !== null) {
            $duel->setLiquidAt($newState->getId(), $block, $newState->getDamage());
            return;
        }

        $event->setCancelled();
    }


    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onPacketReceive(DataPacketReceiveEvent $event): void
    {

        $pkt = $event->getPacket();

        $p = $event->getPlayer();

        if($p instanceof MineceitPlayer) {

            if ($pkt instanceof PlayerActionPacket/* and $pkt->action === PlayerActionPacket::ACTION_START_BREAK */) {

                $position = new Position($pkt->x, $pkt->y, $pkt->z, $p->getLevel());

                $p->setAction($pkt->action);

                if ($pkt->action === PlayerActionPacket::ACTION_START_BREAK && !$p->isPe()) {

                    $p->addCps(true, $position);
                }

            } elseif ($pkt instanceof LevelSoundEventPacket) {

                $sound = $pkt->sound;
                $sounds = [41 => true, 42 => true, 43 => true];

                if (isset($sounds[$sound])) {

                    $p->addCps(false);
                    $inv = $p->getInventory();

                    $item = $inv->getItemInHand();
                    $id = $item->getId();

                    if ($p->isPe()) {

                        if ($id === Item::FISHING_ROD) {
                            $p->useRod($item, true);
                        } elseif ($id === Item::ENDER_PEARL && $item instanceof EnderPearl && $p->canThrowPearl()) {
                            $p->throwPearl($item, true);
                        } elseif ($id === Item::SPLASH_POTION && $item instanceof SplashPotion) {
                            $p->throwPotion($item, true);
                        }
                    }
                }
            } elseif ($pkt instanceof InventoryTransactionPacket) {


                // Initialize time so that we get most accurate representation of consistency as possible.
                $time = microtime(true);

                if ($pkt->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                    $target = $p->getLevel()->getEntity($pkt->trData->entityRuntimeId);
                    if ($target === null) {
                        return;
                    }

                    $type = $pkt->trData->actionType;
                    if ($target instanceof MineceitPlayer && $type === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK) {
                        if (!$target->isAlive() or !$p->canInteract($target, 8)) {
                            return;
                        }
                        $check = $p->getConsistencyCheck();
                        $check->checkClick($target, $time);
                    }
                }
            } else if ($pkt instanceof ScriptCustomEventPacket) {

                var_dump($pkt);

                RequestPool::doRequest($pkt->eventData, $p);
            }


            /* elseif ($pkt instanceof PlayerAuthInputPacket) {
                var_dump($pkt);
            }*/
        }
    }


    /**
     * @param PlayerAnimationEvent $event
     */
    public function onAnimated(PlayerAnimationEvent $event): void
    {

        $p = $event->getPlayer();

        if ($p instanceof MineceitPlayer && $p->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($p);
            $duel->setAnimationFor($p->getName(), $event->getAnimationType());
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event): void
    {

        $player = $event->getPlayer();

        if($player instanceof MineceitPlayer) {

            $format = MineceitCore::getRankHandler()->formatRanksForChat($player);

            if (!$player->canChat()) {
                $event->setCancelled(true);
                return;
            }

            $player->setInSpam();

            MineceitUtil::broadcastTranslatedMessage($player, $format . " " . TextFormat::RESET, $event->getMessage(), $event->getRecipients());

            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event): void
    {

        $player = $event->getPlayer();

        $message = $event->getMessage();

        $firstChar = $message[0];

        if ($firstChar === '/') {

            $cancel = false;

            $split = explode(' ', $message);

            if ($split[0] === "/") {
                return;
            }

            $commandName = str_replace('/', '', $split[0]);

            $command = $this->server->getCommandMap()->getCommand($commandName);

            $tellCommands = ['tell' => true, 'msg' => true, 'w' => true];

            if (!($command instanceof MineceitCommand) && $player instanceof MineceitPlayer) {

                $cancel = $player->isInDuel() or $player->isInCombat() or $player->isADuelSpec() or $player->isInEventDuel();
            }

            if ($cancel && $commandName !== 'me' && !isset($tellCommands[$commandName])) {

                $event->setCancelled();

                $msg = null;

                $language = $player->getLanguage();

                if ($player->isInDuel()) {
                    $msg = $language->generalMessage(Language::COMMAND_FAIL_IN_DUEL);
                } elseif ($player->isInCombat()) {
                    $msg = $language->generalMessage(Language::COMMAND_FAIL_IN_COMBAT);
                }

                if ($msg !== null) $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

                return;
            }


            if ($commandName === 'me' or isset($tellCommands[$commandName])) {

                if (!$player->canChat(true)) {
                    $event->setCancelled();
                    return;
                }

                $player->setInTellSpam();

            }/*  elseif ($player->isOp()) {

                if($commandName === 'op' or $commandName === 'deop') {

                    $permissionValue = $commandName === 'op';

                    $playerName = isset($split[1]) ? (string)$split[1] : $player->getName();

                    $requestedPlayer = $this->server->getPlayer($playerName);

                    if ($requestedPlayer !== null and $requestedPlayer->isOnline() and $requestedPlayer instanceof MineceitPlayer) {

                        /* $requestedPlayer->setPermission(MineceitPlayer::PERMISSION_BUILDER_MODE, $permissionValue);
                        $requestedPlayer->setPermission(MineceitPlayer::PERMISSION_TAG, $permissionValue);

                        $requestedPlayer->reloadPermissions();

                    } else {

                        // $playerHandler->updatePlayerData($playerName, ['permissions' => [MineceitPlayer::PERMISSION_BUILDER_MODE => $permissionValue, MineceitPlayer::PERMISSION_TAG => $permissionValue]]);
                    }
                }
            } */
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     */
    public function onItemMoved(InventoryTransactionEvent $event): void
    {

        $transaction = $event->getTransaction();

        $player = $transaction->getSource();

        $actions = $transaction->getActions();

        if ($player instanceof MineceitPlayer) {

            if ($player->isInHub()) {

                $testInv = false;

                $permToPlaceNBreak = $player->isOp() or $player->getPermission(MineceitPlayer::PERMISSION_BUILDER_MODE);

                if ($permToPlaceNBreak) {
                    $testInv = $player->canBuild();
                } else {
                    $event->setCancelled();
                }

                if ($testInv and !$event->isCancelled()) {

                    foreach ($actions as $action) {

                        if ($action instanceof SlotChangeAction) {

                            $inventory = $action->getInventory();

                            if ($inventory instanceof MineceitBaseInv) {

                                $menu = $inventory->getMenu();

                                $menu->onItemMoved($player, $action);

                                if (!$menu->canEdit()) {
                                    $event->setCancelled();
                                    return;
                                }

                            }
                        }
                    }

                } else {

                    $event->setCancelled(!$testInv);
                }
            }
        }
    }

    /**
     * @param EntityShootBowEvent $event
     */
    public function onShootBow(EntityShootBowEvent $event): void
    {

        $e = $event->getEntity();

        if ($e instanceof MineceitPlayer and $e->isInDuel()) {
            $duel = MineceitCore::getDuelHandler()->getDuel($e);
            if ($duel->isCountingDown()) {
                $event->setCancelled();
                return;
            }
            $duel->setReleaseBow($e, $event->getForce());
        }
    }

    /**
     * @param PluginDisableEvent $event
     */
    public function onPluginDisable(PluginDisableEvent $event): void
    {

        $plugin = $event->getPlugin();

        if ($plugin instanceof MineceitCore) {

            $playerHandler = MineceitCore::getPlayerHandler();

            $playerHandler->getIPManager()->save();
            $playerHandler->getAliasManager()->save();

            $players = $plugin->getServer()->getOnlinePlayers();
            foreach ($players as $player) {
                if($player instanceof MineceitPlayer) {
                    $playerHandler->savePlayerData($player);
                }
            }
        }
    }

    /**
     * @param QueryRegenerateEvent $event
     */
    public function onQueryRegenEvent(QueryRegenerateEvent $event): void
    {

        /* $leaderboards = MineceitCore::getLeaderboards();

        $encoded_elo = json_encode($leaderboards->getEloLeaderboards());
        $encoded_stats = json_encode($leaderboards->getStatsLeaderboards());

        $event->setExtraData(["elo-leaderboards" => $encoded_elo, 'stats-leaderboards' => $encoded_stats]); */

        // TODO FIX -> CAUSES A LOT OF LAG
    }
}