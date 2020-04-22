<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-09
 * Time: 18:56
 */

declare(strict_types=1);

namespace mineceit\game\entities;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Living;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Color;

class SplashPotion extends \pocketmine\entity\projectile\SplashPotion
{

    /**
     * @param ProjectileHitEvent $event
     */
    protected function onHit(ProjectileHitEvent $event) : void{
        $effects = $this->getPotionEffects();
        $hasEffects = true;

        if(empty($effects)){
            $colors = [
                new Color(0x38, 0x5d, 0xc6) //Default colour for splash water bottle and similar with no effects.
            ];
            $hasEffects = false;
        }else{
            $colors = [];
            foreach($effects as $effect){
                $level = $effect->getEffectLevel();
                for($j = 0; $j < $level; ++$j){
                    $colors[] = $effect->getColor();
                }
            }
        }

        $this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_PARTICLE_SPLASH, Color::mix(...$colors)->toARGB());
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_GLASS);

        if($hasEffects){
            if(!$this->willLinger()){
                foreach($this->level->getNearbyEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity){
                    if($entity instanceof Living and $entity->isAlive()){
                        $distanceSquared = $entity->add(0, $entity->getEyeHeight(), 0)->distanceSquared($this);
                        if($distanceSquared > 16){ //4 blocks
                            continue;
                        }

                        $distanceMultiplier = 1.45 - (sqrt($distanceSquared) / 4);
                        if($event instanceof ProjectileHitEntityEvent and $entity === $event->getEntityHit()){
                            $distanceMultiplier = 1.0;
                        }

                        foreach($this->getPotionEffects() as $effect){
                            //getPotionEffects() is used to get COPIES to avoid accidentally modifying the same effect instance already applied to another entity

                            if(!$effect->getType()->isInstantEffect()){
                                $newDuration = (int) round($effect->getDuration() * 0.75 * $distanceMultiplier);
                                if($newDuration < 20){
                                    continue;
                                }
                                $effect->setDuration($newDuration);
                                $entity->addEffect($effect);
                            }else{
                                $effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this, $this->getOwningEntity());
                            }
                        }
                    }
                }
            }else;

        }elseif($event instanceof ProjectileHitBlockEvent and $this->getPotionId() === Potion::WATER){
            $blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

            if($blockIn->getId() === Block::FIRE){
                $this->level->setBlock($blockIn, BlockFactory::get(Block::AIR));
            }
            foreach($blockIn->getHorizontalSides() as $horizontalSide){
                if($horizontalSide->getId() === Block::FIRE){
                    $this->level->setBlock($horizontalSide, BlockFactory::get(Block::AIR));
                }
            }
        }
    }
}