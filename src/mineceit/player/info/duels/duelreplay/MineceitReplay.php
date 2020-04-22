<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-10-26
 * Time: 00:29
 */

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay;


use mineceit\game\entities\ReplayArrow;
use mineceit\game\entities\ReplayHuman;
use mineceit\game\entities\ReplayItemEntity;
use mineceit\kits\AbstractKit;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\duelreplay\data\BlockData;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitReplay
{
    /* @var string
     * The world name
     */
    private $worldId;

    /* @var WorldReplayData
     * The data of the world during the duel.
     */
    private $worldData;

    /* @var ReplayHuman
     * The human that represents PlayerA.
     */
    private $humanA;

    /* @var ReplayHuman
     * The human that represents PlayerB.
     */
    private $humanB;

    /* @var PlayerReplayData
     * The data of playerA during the duel.
     */
    private $playerAData;

    /* @var PlayerReplayData
     * The data of playerB during the duel.
     */
    private $playerBData;

    /* @var int
     * The final tick of the duel.
     */
    private $endTick;

    /* @var int
     * The current tick of the replay.
     */
    private $currentTick;

    /* @var bool
     * Determines whether the replay is paused or not.
     */
    private $paused;

    /* @var MineceitPlayer
     * The player viewing the replay.
     */
    private $spectator;

    /* @var Position */
    private $centerPosition;

    /* @var Level */
    private $level;

    /* @var int
     * Tracks the current tick of the replay itself.
     */
    private $currentReplayTick;

    /* @var bool
     * Determines when the replay starts counting.
     */
    private $startReplayCount;

    /* @var AbstractKit */
    private $duelKit;


    public function __construct(MineceitPlayer $spectator, string $level, DuelReplayInfo $info)
    {
        $this->paused = false;
        $this->spectator = $spectator;
        $this->currentTick = 0;
        $this->currentReplayTick = 0;
        $this->worldId = $level;
        $server = Server::getInstance();
        $this->level = $server->getLevelByName($level);
        $this->playerBData = $info->getPlayerBData();
        $this->playerAData = $info->getPlayerAData();
        $this->endTick = $info->getEndTick();
        $this->worldData = $info->getWorldData();
        $this->centerPosition = null;
        $this->startReplayCount = false;
        $this->duelKit = $info->getKit();
    }

    /**
     * Teleports the viewer to the spectator map.
     */
    private function teleportViewer() : void {

        $worldType = $this->worldData->getWorldType();

        $isSumo = $worldType === WorldReplayData::TYPE_SUMO;

        $this->spectator->setInSpectatorMode(true, true);

        MineceitCore::getItemHandler()->spawnReplayItems($this->spectator);

        if($this->spectator->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) {
            $this->spectator->setScoreboard(Scoreboard::SCOREBOARD_REPLAY);
        }

        $p1x = $isSumo ? 9 : 24;
        $p1z = $isSumo ? 5 : 40;

        $p2x = $isSumo ? 1 : $p1x;
        $p2z = $isSumo ? $p1z : 10;

        $y = 100;

        $this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), $y, intval((($p2z + $p1z) / 2)), $this->level);

        $center = $this->centerPosition;

        $centerX = intval($this->centerPosition->x);
        $centerZ = intval($this->centerPosition->z);

        MineceitUtil::onChunkGenerated($this->level, $centerX >> 4, $centerZ >> 4, function () use($center) {
            $this->spectator->teleport($center);
        });

        if ($this->spectator->getLevel()->getName() !== $this->worldId)
            $this->spectator->teleport($center);
    }

    private function loadEntities() : void {

        $humanANBT = MineceitUtil::getHumanNBT($this->playerAData);
        $humanBNBT = MineceitUtil::getHumanNBT($this->playerBData);

        $humanAStartPosition = $this->playerAData->getStartPosition();
        $humanBStartPosition = $this->playerBData->getStartPosition();

        if(!$this->level->isInLoadedTerrain($humanAStartPosition))
            $this->level->loadChunk($humanAStartPosition->x >> 4, $humanAStartPosition->z >> 4);

        if(!$this->level->isInLoadedTerrain($humanBStartPosition))
            $this->level->loadChunk($humanBStartPosition->x >> 4, $humanBStartPosition->z >> 4);

        $this->humanA = Entity::createEntity("ReplayHuman", $this->level, $humanANBT);
        $this->humanA->spawnToAll();
        $humanASkin = $this->playerAData->getSkin();

        if($humanASkin instanceof Skin)
            $this->humanA->setSkin($humanASkin);

        $this->humanB = Entity::createEntity("ReplayHuman", $this->level, $humanBNBT);
        $this->humanB->spawnToAll();
        $humanBSkin = $this->playerAData->getSkin();

        if($humanBSkin instanceof Skin)
            $this->humanB->setSkin($humanBSkin);

        $this->initInventories();
    }

    private function initInventories() : void {

        $inv = $this->humanA->getInventory();
        $startInv = $this->playerAData->getStartInventory();
        $armorInv = $this->humanA->getArmorInventory();
        $startArmorInv = $this->playerAData->getArmorInventory();

        $count = 0;
        $len = count($startInv);
        while($count < $len) {
            $i = $startInv[$count];
            $inv->setItem($count, $i);
            $count++;
        }

        $count = 0;
        $len = count($startArmorInv);
        while($count < $len) {
            $i = $startArmorInv[$count];
            $armorInv->setItem($count, $i);
            $count++;
        }

        $armorInv->sendContents($this->humanA->getViewers());
        $inv->sendContents($this->humanA->getViewers());

        $inv = $this->humanB->getInventory();
        $startInv = $this->playerBData->getStartInventory();
        $armorInv = $this->humanB->getArmorInventory();
        $startArmorInv = $this->playerBData->getArmorInventory();

        $count = 0;
        $len = count($startInv);
        while($count < $len) {
            $i = $startInv[$count];
            $inv->setItem($count, $i);
            $count++;
        }

        $count = 0;
        $len = count($startArmorInv);
        while($count < $len) {
            $i = $startArmorInv[$count];
            $armorInv->setItem($count, $i);
            $count++;
        }
        $armorInv->sendContents($this->humanB->getViewers());
        $inv->sendContents($this->humanB->getViewers());
    }


    public function update() : void {

        $this->currentTick++;

        if($this->startReplayCount and !$this->paused and $this->currentReplayTick <= $this->endTick)
            $this->currentReplayTick++;

        if(!$this->spectator->isOnline()) {
            $this->endReplay();
            return;
        }

        if ($this->currentTick === 5)
            $this->teleportViewer();
        elseif ($this->currentTick === 20) {
            $this->loadEntities();
            $this->startReplayCount = true;
        }

        if($this->currentReplayTick % 20) $this->updateSpectator();

        if($this->startReplayCount and $this->currentReplayTick <= $this->endTick) {

            if(!$this->paused) {

                $this->updateHuman($this->humanA, $this->playerAData);
                $this->updateHuman($this->humanB, $this->playerBData);

                $this->updateWorld(false);
            }
        }

        if($this->startReplayCount) {
            $this->humanA->setPaused($this->currentReplayTick >= $this->endTick);
            $this->humanB->setPaused($this->currentReplayTick >= $this->endTick);
        }
    }


    private function updateSpectator() : void {

        $color = MineceitUtil::getThemeColor();

        $durationBottom = $color . ' ' . $this->getDuration() . TextFormat::WHITE . ' | ' . $color . $this->getMaxDuration() . ' ';

        $this->spectator->updateLineOfScoreboard(2, $durationBottom);
    }


    /**
     * @param ReplayHuman $human
     * @param PlayerReplayData $data
     *
     * Updates the human according to current replay time.
     */
    private function updateHuman(ReplayHuman $human, PlayerReplayData $data) : void {

        $attributes = $data->getAttributesAt($this->currentReplayTick);

        $deathTime = $data->getDeathTime();

        if($data->didDie()) {
            if ($this->currentReplayTick >= $deathTime or $this->currentReplayTick >= $this->endTick) {
                $human->setInvisible(true);
                if ($human->isOnFire())
                    $human->extinguish();
            } else {
                if ($human->isInvisible())
                    $human->setInvisible(false);
            }
        }

        if(isset($attributes['bow']))
            $human->setReleaseBow($attributes['bow']);

        if(isset($attributes['thrown'])) {
            $value = $attributes['thrown'];
            MineceitUtil::onClickAir($value, $human, $human->getDirectionVector());
        }

        if(isset($attributes['sneak'])) {
            $value = $attributes['sneak'];
            $human->setSneaking($value);
        }

        if(isset($attributes['drop'])) {
            $drops = $attributes['drop'];
            $item = $drops['item'];
            $motion = $drops['motion'];
            $pickup = isset($drops['pickup']) ? $drops['pickup'] : null;
            MineceitUtil::dropItem($this->level, $human->add(0, 1.3, 0), $item, $motion, $this->currentReplayTick, $human, $pickup);
        }

        if(isset($attributes['jump']))
            $human->jump();

        if(isset($attributes['rotation'])){
            $rotation = $attributes['rotation'];
            $human->setRotation($rotation['yaw'], $rotation['pitch']);
        }

        if(isset($attributes['nameTag'])) {
            $tag = $attributes['nameTag'];
            $human->setNameTag($tag . "\n" . TextFormat::GOLD . "CPS: " . 0);
        }

        if(isset($attributes['item'])) {
            $inv = $human->getInventory();
            $item = $attributes['item'];
            $inv->setItemInHand($item);
            $inv->sendHeldItem($human->getViewers());
        }

        if(isset($attributes['animation'])) {
            $animation = $attributes['animation'];
            switch($animation) {
                case AnimatePacket::ACTION_SWING_ARM:
                    $human->broadcastEntityEvent(ActorEventPacket::ARM_SWING);
                    break;
            }
        }

        if(isset($attributes['cps'])) {
            $cps = $attributes['cps'];
            $tag = $human->getNameTag();
            if(strpos($tag, 'CPS') !== false)
                $tag = explode("\n", $tag)[0];
            $tag = $tag . "\n" . TextFormat::GOLD . 'CPS: ' . $cps;
            $human->setNameTag($tag);
        }

        if(isset($attributes['fire'])) {
            $fire = $attributes['fire'];
            if(is_int($fire))
                $human->setOnFire($fire);
            elseif (is_bool($fire))
                $human->extinguish();
        } else {
            $fire = $data->getLastAttributeUpdate($this->currentReplayTick, 'fire');
            if($fire !== null) {
                if(is_int($fire))
                    $human->setOnFire($fire);
                elseif (is_bool($fire))
                    $human->extinguish();
            } else {
                if($human->isOnFire()) $human->extinguish();
            }
        }

        if(isset($attributes['damaged'])) {
            $human->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
            $this->level->broadcastLevelSoundEvent($human->asVector3(), LevelSoundEventPacket::SOUND_HURT);
        }

        if(isset($attributes['consumed'])) {
            $drank = $attributes['consumed'];
            $sound = $drank ? LevelSoundEventPacket::SOUND_DRINK : LevelSoundEventPacket::SOUND_EAT;
            $this->level->broadcastLevelSoundEvent($human->asVector3(), $sound);
        }

        if(isset($attributes['effects'])) {
            $effects = $attributes['effects'];
            $keys = array_keys($effects);
            foreach($keys as $key) {
                /* @var \pocketmine\entity\EffectInstance $effect */
                $effect = $effects[$key];
                $human->addEffect($effect);
            }
        }

        if(isset($attributes['armor'])) {
            $armor = $attributes['armor'];
            $armorInv = $human->getArmorInventory();
            if (isset($armor['helmet']))
                $armorInv->setHelmet($armor['helmet']);
            if (isset($armor['chest']))
                $armorInv->setChestplate($armor['chest']);
            if (isset($armor['pants']))
                $armorInv->setLeggings($armor['pants']);
            if (isset($armor['boots']))
                $armorInv->setBoots($armor['boots']);
        }

        if(isset($attributes['fishing'])) {
            $fishing = $attributes['fishing'];
            MineceitUtil::useRod($human, $fishing);
        }

        if(isset($attributes['sprinting'])) {
            $sprinting = $attributes['sprinting'];
            $human->setSprinting($sprinting);
        }

        if(isset($attributes['position'])) {
            $position = $attributes['position'];
            $human->setTargetPosition($position);
        }
    }


    /**
     * @param bool $resetPlayer
     *
     * Ends the replay.
     */
    public function endReplay(bool $resetPlayer = true) : void {

        if($resetPlayer and $this->spectator !== null and $this->spectator->isOnline()) {
            $this->spectator->reset(true, true);
            MineceitCore::getItemHandler()->spawnHubItems($this->spectator);
            $msg = $this->spectator->getLanguage()->generalMessage(Language::IN_HUB);
            $this->spectator->sendMessage(MineceitUtil::getPrefix() . TextFormat::RESET . ' ' . $msg);
            $this->spectator->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
        }

        $this->humanA->close();
        $this->humanB->close();

        MineceitUtil::deleteLevel($this->level);

        MineceitCore::getReplayManager()->deleteReplay($this->worldId);
    }


    /**
     * @var bool $updatePause
     * Updates the world the player is in.
     */
    public function updateWorld(bool $updatePause = true) : void {

        $worldAttributes = $this->worldData->getAttributesAt($this->currentReplayTick);

        if(isset($worldAttributes['blocks'])) {

            /* @var BlockData[] $blocks */
            $blocks = $worldAttributes['blocks'];

            $keys = array_keys($blocks);

            foreach ($keys as $key) {
                $block = $blocks[$key];
                $position = $block->getPosition();
                $currentBlock = $this->level->getBlock($position);
                $bl = $block->getBlock();
                if($currentBlock->getId() !== $bl->getId() or $currentBlock->getDamage() !== $bl->getDamage())
                    $this->level->setBlock($position, $bl);
            }
        }

        $entities = $this->level->getEntities();

        if($updatePause) {
            foreach ($entities as $e) {

                if ($e instanceof ReplayArrow) {

                    if($e->isOnGround()) {
                        $e->close();
                        continue;
                    }

                    $e->setPaused($this->paused);

                } elseif ($e instanceof ReplayItemEntity) {
                    if($e->shouldDespawn($this->currentReplayTick)) {
                        $e->close();
                        continue;
                    }
                    $e->setPaused($this->paused);
                }
            }
        } else {
            foreach($entities as $e) {
                if($e instanceof ReplayItemEntity) {
                    $e->setPaused($this->paused);
                    $e->updatePickup($this->currentReplayTick);
                } elseif ($e instanceof ReplayArrow)
                    $e->setPaused($this->paused);
            }
        }
    }

    /**
     * @return MineceitPlayer
     *
     * The spectator.
     */
    public function getSpectator() : MineceitPlayer {
        return $this->spectator;
    }


    /**
     * @param int $seconds
     *
     * Rewinds the time to a specific point.
     */
    public function rewind(int $seconds) : void {
        $this->setSeconds(-$seconds);
    }

    /**
     * @param int $seconds
     *
     * Fast forwards the time to a specific point.
     */
    public function fastForward(int $seconds) : void {
        $this->setSeconds($seconds);
    }


    /**
     * @param int $seconds
     *
     * Sets the replay time to a specific point based in seconds.
     */
    private function setSeconds(int $seconds) : void {

        $ticks = $seconds * 20;

        $nextReplayTick = $this->currentReplayTick + $ticks;

        if($nextReplayTick < 0) $nextReplayTick = 0;
        elseif ($nextReplayTick > $this->endTick) $nextReplayTick = $this->endTick;

        $this->currentReplayTick = $nextReplayTick;

        if ($this->humanA !== null and $this->humanB !== null) {

            if($this->spectator !== null and $this->spectator->isOnline())
                $this->updateSpectator();

            $this->updateHumanAfterTickChange($this->humanA, false);
            $this->updateHumanAfterTickChange($this->humanB, true);

            $this->updateWorld();
        }
    }

    /**
     * @param bool $paused
     *
     * Sets the rewind to pause.
     */
    public function setPaused(bool $paused) : void {

        $itemHandler = MineceitCore::getItemHandler();

        if($this->paused) {
            $itemHandler->givePauseItem($this->spectator);
            $this->spectator->removePausedFromScoreboard();
        } else {
            $itemHandler->givePlayItem($this->spectator);
            $this->spectator->addPausedToScoreboard();
        }

        $this->humanA->setPaused($paused);
        $this->humanB->setPaused($paused);

        $this->paused = $paused;
    }


    private function updateHumanAfterTickChange(ReplayHuman $human, bool $isHumanB = false) : void {

        $replayPlayerData = $isHumanB ? $this->playerBData : $this->playerAData;

        $attributes = $replayPlayerData->getAttributesAt($this->currentReplayTick);

        $deathTime = $replayPlayerData->getDeathTime();

        if($replayPlayerData->didDie()) {
            if ($this->currentReplayTick >= $deathTime or $this->currentReplayTick >= $this->endTick)
                $human->setInvisible(true);
            else {
                if ($human->isInvisible())
                    $human->setInvisible(false);
            }
        }

        if(isset($attributes['position'])) {
            $position = $attributes['position'];
            $human->teleport($position);
            $human->setTargetPosition($position);
        }

        if(isset($attributes['rotation'])) {
            $rotation = $attributes['rotation'];
            $human->setRotation($rotation['yaw'], $rotation['pitch']);
        } else {
            $lastRotation = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'rotation');
            if($lastRotation !== null)
                $human->setRotation($lastRotation['yaw'], $lastRotation['pitch']);
        }

        $inv = $human->getInventory();

        if(isset($attributes['item'])) {
            $item = $attributes['item'];
            $inv->setItemInHand($item);
            $inv->sendHeldItem($human->getViewers());
        } else {
            $lastItem = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'item');
            if($lastItem !== null) {
                $inv->setItemInHand($lastItem);
                $inv->sendHeldItem($human->getViewers());
            }
        }

        if(isset($attributes['fishing'])) {
            $fishing = $attributes['fishing'];
            MineceitUtil::useRod($human, $fishing);
        } else {
            $fishing = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'fishing');
            if($fishing !== null)
                MineceitUtil::useRod($human, $fishing);
        }

        if(isset($attributes['nameTag'])) {
            $tag = $attributes['nameTag'];
            $human->setNameTag($tag . "\n" . TextFormat::GOLD . "CPS: " . 0);
        } else {
            $lastNameTag = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'nameTag');
            if($lastNameTag !== null)
                $human->setNameTag($lastNameTag);
        }

        if(isset($attributes['cps'])) {
            $cps = $attributes['cps'];
            $tag = $human->getNameTag();
            if(strpos($tag, 'CPS') !== false)
                $tag = explode("\n", $tag)[0];
            $tag = $tag . "\n" . TextFormat::GOLD . 'CPS: ' . $cps;
            $human->setNameTag($tag);
        } else {
            $cps = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'cps');
            $tag = $human->getNameTag();
            if(strpos($tag, 'CPS') !== false)
                $tag = explode("\n", $tag)[0];
            $tag = $tag . "\n" . TextFormat::GOLD . 'CPS: ' . $cps;
            $human->setNameTag($tag);
        }

        if(isset($attributes['fire'])) {
            $fire = $attributes['fire'];
            if(is_int($fire))
                $human->setOnFire($fire);
            elseif (is_bool($fire))
                $human->extinguish();
        } else {
            $fire = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'fire');
            if($fire !== null) {
                if(is_int($fire))
                    $human->setOnFire($fire);
                elseif (is_bool($fire))
                    $human->extinguish();
            } else {
                if($human->isOnFire()) $human->extinguish();
            }
        }

        $armorInv = $human->getArmorInventory();

        if(isset($attributes['armor'])) {
            $armor = $attributes['armor'];
            if (isset($armor['helmet']))
                $armorInv->setHelmet($armor['helmet']);
            if (isset($armor['chest']))
                $armorInv->setChestplate($armor['chest']);
            if (isset($armor['pants']))
                $armorInv->setLeggings($armor['pants']);
            if (isset($armor['boots']))
                $armorInv->setBoots($armor['boots']);
        } else {
            $armor = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'armor');
            if($armor !== null) {
                if (isset($armor['helmet']))
                    $armorInv->setHelmet($armor['helmet']);
                if (isset($armor['chest']))
                    $armorInv->setChestplate($armor['chest']);
                if (isset($armor['pants']))
                    $armorInv->setLeggings($armor['pants']);
                if (isset($armor['boots']))
                    $armorInv->setBoots($armor['boots']);
            }
        }

        if(isset($attributes['effects'])) {
            $effects = $attributes['effects'];
            $keys = array_keys($effects);
            foreach($keys as $key) {
                /* @var \pocketmine\entity\EffectInstance $effect */
                $effect = $effects[$key];
                $human->addEffect($effect);
            }
        } else {
            $effects = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'effects');
            if ($effects !== null) {
                $keys = array_keys($effects);
                foreach ($keys as $key) {
                    /* @var \pocketmine\entity\EffectInstance $effect */
                    $effect = $effects[$key];
                    $human->addEffect($effect);
                }
            }
        }

        if(isset($attributes['sneak'])) {
            $sneak = $attributes['sneak'];
            $human->setSneaking($sneak);
        } else {
            $sneak = $replayPlayerData->getLastAttributeUpdate($this->currentReplayTick, 'sneak');
            if($sneak !== null)
                $human->setSneaking($sneak);
        }
    }

    /**
     * @return string
     */
    public function getQueue() : string {
        return $this->duelKit->getName();
    }


    /**
     * @return bool
     */
    public function isRanked() : bool {
        return $this->worldData->isRanked();
    }


    /**
     * @return string
     */
    public function getDuration() : string {

        $durationSeconds = intval($this->currentReplayTick / 20) - 5;

        if($durationSeconds < 0) $durationSeconds = 0;

        $seconds = $durationSeconds % 60;
        $minutes = intval($durationSeconds / 60);

        $color = MineceitUtil::getThemeColor();

        $result = $color . '%min%:%sec%';

        $secStr = "$seconds";
        $minStr = "$minutes";

        if ($seconds < 10)
            $secStr = '0' . $seconds;

        if ($minutes < 10)
            $minStr = '0' . $minutes;

        return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
    }

    /**
     * @return string
     */
    public function getMaxDuration() : string {

        $durationSeconds = intval($this->endTick / 20) - 5;

        if($durationSeconds < 0) $durationSeconds = 0;

        $seconds = $durationSeconds % 60;
        $minutes = intval($durationSeconds / 60);

        $color = MineceitUtil::getThemeColor();

        $result = $color . '%min%:%sec%';

        $secStr = "$seconds";
        $minStr = "$minutes";

        if ($seconds < 10)
            $secStr = '0' . $seconds;

        if ($minutes < 10)
            $minStr = '0' . $minutes;

        return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
    }


    /**
     * @return bool
     */
    public function isPaused() : bool {
        return $this->paused;
    }
}