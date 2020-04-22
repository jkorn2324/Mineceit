<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-23
 * Time: 18:30
 */

declare(strict_types=1);

namespace mineceit\game;


use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mineceit\arenas\FFAArena;
use mineceit\duels\groups\MineceitDuel;
use mineceit\duels\requests\DuelRequest;
use mineceit\game\inventories\menus\PostMatchInv;
use mineceit\maitenance\reports\data\BugReport;
use mineceit\maitenance\reports\data\HackReport;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\maitenance\reports\data\StaffReport;
use mineceit\maitenance\reports\data\TranslateReport;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\MineceitParty;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\player\ranks\Rank;
use mineceit\scoreboard\Scoreboard;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FormUtil
{

    /**
     * @param string $locale
     *
     * @return SimpleForm
     */
    public static function getLanguageForm(string $locale): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer){

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $locale = (string)$formData["locale"];

                    $id = intval($data);

                    if (isset($formData[$id])) {

                        $text = TextFormat::clean(strval($formData[$id]['text']));
                        if (strpos($text, "\n") !== false) {
                            $text = strval(explode("\n", $text)[0]);
                        }

                        $lang = trim($text);

                        $language = MineceitCore::getPlayerHandler()->getLanguageFromName($lang, $locale);

                        if ($language !== null and ($newLocale = $language->getLocale()) !== $locale) {

                            $event->setLanguage($newLocale);
                            $event->reloadScoreboard();

                            $itemHandler = MineceitCore::getItemHandler();

                            if ($event->isInEvent()) {
                                $itemHandler->spawnEventItems($event);
                            } elseif ($event->isInHub()) {
                                $itemHandler->spawnHubItems($event);
                            }
                        }
                    }
                }
            }
        });

        $olayerManager = MineceitCore::getPlayerHandler();

        $playerLanguage = $olayerManager->getLanguage($locale);

        $form->setTitle(TextFormat::BOLD . $playerLanguage->formWindow(Language::LANGUAGE_FORM_TITLE));

        $languages = $olayerManager->getLanguages();

        foreach ($languages as $lang) {

            $texture = "textures/blocks/glass_white.png";
            $name = TextFormat::BOLD . $lang->getNameFromLocale($locale);

            if ($lang->getLocale() === $locale) {
                $texture = "textures/blocks/glass_light_blue.png";
                $name = TextFormat::BLUE . $name;
            }

            if ($lang->hasCredit()) {
                $languageCredit = $lang->getCredit();
                $creditMessage = $playerLanguage->getMessage(Language::LANGUAGE_CREDIT);
                $credit = TextFormat::RESET . TextFormat::DARK_GRAY . "{$creditMessage}: {$languageCredit}";
                if ($playerLanguage->getLocale() === Language::ARABIC) {
                    $credit = TextFormat::RESET . TextFormat::DARK_GRAY . "{$languageCredit} :{$creditMessage}";
                }

                $name .= "\n{$credit}";
            }

            $form->addButton($name, 0, $texture);
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getFFAForm(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $arenaName = (string)$formData[$data]['text'];
                    $arenaName = TextFormat::clean(explode("\n", $arenaName)[0]);
                    $arena = MineceitCore::getArenas()->getArena($arenaName);

                    if ($arena !== null && $arena instanceof FFAArena && !$event->isInDuel()) {

                        if ($event->isInQueue()) {
                            MineceitCore::getDuelHandler()->removeFromQueue($event, false);
                        }

                        $event->teleportToFFAArena($arena);
                    }
                }
            }
        });

        $language = $player->getLanguage();

        $numPlayersStr = $language->formWindow(Language::FFA_FORM_NUMPLAYERS);

        $form->setTitle($language->formWindow(Language::FFA_FORM_TITLE));

        $form->setContent($language->formWindow(Language::FFA_FORM_DESC));

        $arenaHandler = MineceitCore::getArenas();

        $arenas = $arenaHandler->getFFAArenas();

        $size = count($arenas);

        if ($size <= 0) {
            $form->addButton($language->generalMessage(Language::NONE));
            return $form;
        }

        foreach ($arenas as $arena) {

            $name = $arena->getName();

            $players = $arenaHandler->getPlayersInArena($arena);

            $str = $name . "\n" . $numPlayersStr . TextFormat::BOLD . TextFormat::DARK_GRAY . ": " . TextFormat::WHITE . $players;

            if ($language->getLocale() === Language::ARABIC) {
                $str = $name . "\n" . TextFormat::BOLD . TextFormat::WHITE . $players . TextFormat::DARK_GRAY . ' :' . $numPlayersStr;
            }

            $texture = $arena->getTexture();
            $form->addButton($str, 0, $texture);
        }

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getPlayForm(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $language = $event->getLanguage();

                    switch ($data) {
                        case 0:
                            $form = self::getFFAForm($event);
                            $event->sendFormWindow($form);
                            break;
                        case 1:
                            $form = FormUtil::getDuelForm($event, $language->formWindow(Language::DUELS_UNRANKED_FORM_TITLE), false);
                            $event->sendFormWindow($form, ['ranked' => false]);
                            break;
                        case 2:
                            $form = FormUtil::getDuelForm($event, $language->formWindow(Language::DUELS_RANKED_FORM_TITLE), true);
                            $event->sendFormWindow($form, ['ranked' => true]);
                            break;
                        case 3:
                            $form = self::getEventsForm($event);
                            $event->sendFormWindow($form);
                            break;
                    }
                }
            }
        });

        $lang = $player->getLanguage();

        $form->setTitle($lang->formWindow(Language::HUB_PLAY_FORM_TITLE));

        $form->setContent($lang->formWindow(Language::HUB_PLAY_FORM_DESC));

        $form->addButton(
            TextFormat::BOLD . $lang->formWindow(Language::HUB_PLAY_FORM_FFA),
            0,
            "textures/items/iron_axe.png"
        );

        $form->addButton(
            TextFormat::BOLD . $lang->formWindow(Language::HUB_PLAY_FORM_UNRANKED_DUELS),
            0,
            "textures/items/gold_sword.png"
        );

        $form->addButton(
            TextFormat::BOLD . $lang->formWindow(Language::HUB_PLAY_FORM_RANKED_DUELS),
            0,
            "textures/items/diamond_sword.png"
        );

        $form->addButton(
            TextFormat::BOLD . $lang->formWindow(Language::HUB_PLAY_FORM_EVENTS),
            0,
            "textures/items/totem.png"
        );

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getEventsForm(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {
                    $theEvent = MineceitCore::getEventManager()->getEventFromIndex((int)$data);
                    if ($theEvent !== null) {
                        $theEvent->addPlayer($event);
                    }
                }
            }
        });

        $lang = $player->getLanguage();

        $form->setTitle($lang->getMessage(Language::EVENT_FORM_TITLE));
        $form->setContent($lang->getMessage(Language::EVENT_FORM_DESC));

        $events = MineceitCore::getEventManager()->getEvents();
        if (count($events) <= 0) {
            $form->addButton($lang->getMessage(Language::NONE));
            return $form;
        }

        foreach ($events as $event) {

            $form->addButton(
                $event->formatForForm($lang),
                0,
                $event->getArena()->getTexture()
            );
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getSettingsMenu(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    switch ((int)$data) {

                        case 0:
                            $form = self::getChangeSettingsForm($event);
                            $event->sendFormWindow($form);
                            break;
                        case 1:
                            $perm = $event->getPermission(MineceitPlayer::PERMISSION_TAG);
                            if (!$perm) {
                                $msg = MineceitUtil::getPrefix() . TextFormat::RESET . ' ' . TextFormat::RED . $event->getLanguage()->generalMessage(Language::SET_TAG_MSG_NO_PERMS);
                                $event->sendMessage($msg);
                            } else {
                                $form = self::getSetTagForm($event);
                                $event->sendFormWindow($form);
                            }
                            break;
                        case 2:
                            $form = self::getLanguageForm($event->getLanguage()->getLocale());
                            $event->sendFormWindow($form, ["locale" => $event->getLanguage()->getLocale()]);
                            break;
                        case 3:
                            $event->updateBuilderLevels();
                            $builderLevels = $event->getBuilderLevels();
                            $form = self::getBuilderModeForm($event, $builderLevels);
                            $event->sendFormWindow($form, ['builder-levels' => array_keys($builderLevels)]);
                            break;
                    }
                }
            }
        });

        // $player->reloadPermissions();

        $lang = $player->getLanguage();

        $title = TextFormat::BOLD . TextFormat::AQUA . '» ' . TextFormat::DARK_PURPLE . $lang->formWindow(Language::SETTINGS_FORM_TITLE) . TextFormat::AQUA . ' «';

        $form->setTitle($title);

        $form->addButton(
            TextFormat::DARK_GRAY . TextFormat::BOLD . $lang->formWindow(Language::SETTINGS_FORM_CHANGE_SETTINGS),
            0,
            'textures/items/comparator.png'
        );

        $form->addButton(
            TextFormat::DARK_GRAY . TextFormat::BOLD . $lang->formWindow(Language::SETTINGS_FORM_SET_TAG),
            0,
            'textures/items/book_writable.png'
        );

        $form->addButton(
            $lang->formWindow(Language::CHANGE_LANGUAGE_FORM),
            0,
            'textures/items/paper.png'
        );

        if ($player->getPermission(MineceitPlayer::PERMISSION_BUILDER_MODE)) {

            $form->addButton(
                TextFormat::DARK_GRAY . TextFormat::BOLD . $lang->generalMessage(Language::BUILDER_MODE_FORM_ENABLE),
                0,
                'textures/items/diamond_pickaxe.png'
            );
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     */
    public static function getChangeSettingsForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $resultScoreboard = boolval($data[0]);

                    $resultPeOnly = $event->isPe() ? boolval($data[2]) : false;

                    if ($event->isPeOnly() !== $resultPeOnly) {
                        $event->setPeOnly($resultPeOnly);
                    }

                    if ($event->isScoreboardEnabled() !== $resultScoreboard) {
                        $event->setScoreboardEnabled($resultScoreboard);
                        if (!$resultScoreboard) {
                            $event->removeScoreboard();
                        } else {
                            $event->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                        }
                    }

                    $resultDisplay = boolval($data[1]);

                    if ($resultDisplay) {
                        $event->showPlayers();
                    } else {
                        $event->hidePlayers();
                    }

                    $swishSounds = (bool)$data[3];
                    if ($event->isSwishEnabled() !== $swishSounds) {
                        $event->setSwishEnabled($swishSounds);
                    }

                    $translate = (bool)$data[4];

                    if ($event->doesTranslateMessages() !== $translate) {
                        $event->setTranslateMessages($translate);
                    }
                }
            }
        });

        $language = $player->getLanguage();

        $form->setTitle($language->formWindow(Language::CHANGE_SETTINGS_FORM_TITLE));

        $form->addToggle(
            $language->formWindow(Language::CHANGE_SETTINGS_FORM_SCOREBOARD),
            $player->isScoreboardEnabled()
        );

        $form->addToggle(
            $language->formWindow(Language::CHANGE_SETTINGS_FORM_DISPLAYPLAYERS),
            $player->isDisplayPlayers()
        );

        $form->addToggle(
            $language->formWindow(Language::CHANGE_SETTINGS_FORM_PEONLY),
            $player->isPeOnly()
        );

        $form->addToggle(
            $language->getMessage(Language::CHANGE_SETTINGS_FORM_HIT_SOUNDS),
            $player->isSwishEnabled()
        );

        $form->addToggle(
            $language->formWindow(Language::TRANSLATE_MESSAGES) . " " . TextFormat::AQUA . TextFormat::BOLD . "[BETA]"
            , $player->doesTranslateMessages()
        );

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @param array|null $builderLevels
     * @return CustomForm
     */
    public static function getBuilderModeForm(MineceitPlayer $player, array $builderLevels = null): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    if (!$event->getPermission(MineceitPlayer::PERMISSION_BUILDER_MODE)) {
                        return;
                    }

                    $event->setBuilderMode((bool)$data[1]);

                    $builderLevels = $formData['builder-levels'];

                    if (($size = count($data)) > 2) {

                        $start = 3;

                        while ($start < $size) {

                            $level = $builderLevels[$start - 3];

                            $event->setBuildInLevel(strval($level), (bool)$data[$start]);

                            $start++;
                        }
                    }
                }
            }

        });

        $language = $player->getLanguage();

        $levels = $builderLevels ?? $player->getBuilderLevels();

        $form->setTitle(TextFormat::BOLD . $language->getMessage(Language::BUILDER_MODE_FORM_TITLE));

        $form->addLabel($language->getMessage(Language::BUILDER_MODE_FORM_DESC));

        $enabled = $player->isBuilderModeEnabled();

        $enableNDisableToggle = $enabled ? Language::BUILDER_MODE_FORM_DISABLE : Language::BUILDER_MODE_FORM_ENABLE;

        $enableNDisableToggle = $language->getMessage($enableNDisableToggle);

        $form->addToggle($enableNDisableToggle, $enabled);

        if (count($levels) <= 0) {

            $form->addLabel($language->formWindow(Language::BUILDER_MODE_FORM_LEVEL_NONE));

            return $form;
        }

        $form->addLabel($language->formWindow(Language::BUILDER_MODE_FORM_LEVEL));

        foreach ($levels as $levelName => $value) {

            $form->addToggle(strval($levelName), $value);
        }

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     */
    public static function getSetTagForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $result = $event->setCustomTag((string)$data[1]);

                    $lang = $event->getLanguage();

                    if (!$result) {
                        $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $lang->generalMessage(Language::SET_TAG_MSG_FAILED);
                    } else {
                        $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . $lang->generalMessage(Language::SET_TAG_MSG_SUCCESS);
                        $event->updateNameTag();
                    }

                    $event->sendMessage($msg);
                }
            }
        });

        $lang = $player->getLanguage();

        $form->setTitle(TextFormat::DARK_GRAY . TextFormat::BOLD . $lang->formWindow(Language::SETTINGS_FORM_SET_TAG));

        $labelText = $lang->formWindow(Language::SET_TAG_FORM_DESC);

        if ($lang->getLocale() === Language::ARABIC) {
            $labelText .= "\n";
        }

        $form->addLabel($labelText);

        $form->addInput($lang->formWindow(Language::FIFTEEN_CHARACTERS_MAX), $player->getCustomTag());

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param string $title
     * @param bool $ranked
     * @return SimpleForm
     */
    public static function getDuelForm(MineceitPlayer $player, string $title, bool $ranked): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $queue = TextFormat::clean(explode("\n", $formData[$data]['text'])[0]);

                    if ($event->isInHub()) {
                        MineceitCore::getDuelHandler()->placeInQueue($event, $queue, (bool)$formData['ranked']);
                    }
                }
            }
        });

        $lang = $player->getLanguage();

        $form->setTitle(TextFormat::BOLD . $title);
        $form->setContent($lang->formWindow(Language::DUELS_FORM_DESC));

        $list = MineceitCore::getKits()->getKits();

        $format = TextFormat::GOLD . '» ' . $lang->formWindow(Language::DUELS_FORM_INQUEUES) . TextFormat::GRAY . ': ' . TextFormat::WHITE . '%iq% ' . TextFormat::GOLD . ' «';

        if ($lang->getLocale() === Language::ARABIC) {
            $format = TextFormat::GOLD . '» ' . TextFormat::WHITE . '%iq% ' . TextFormat::GRAY . ':' . $lang->formWindow(Language::DUELS_FORM_INQUEUES) . TextFormat::GOLD . ' «';
        } elseif ($lang->getLocale() === Language::FRENCH_FR || $lang->getLocale() === Language::FRENCH_CA) {
            $format = $lang->formWindow(Language::DUELS_FORM_INQUEUES) . TextFormat::GRAY . ': ' . TextFormat::WHITE . '%iq%';
        }

        foreach ($list as $kit) {

            $name = $kit->getName();
            $numInQueue = MineceitCore::getDuelHandler()->getPlayersInQueue($ranked, $name);

            $form->addButton(
                $name . "\n" . str_replace('%iq%', $numInQueue, $format),
                0,
                $kit->getTexture()
            );
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getDuelHistoryForm(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $length = count($event->getDuelHistory()) - 1;

                    $index = $length - (int)$data;

                    $info = $event->getDuelInfo($index);

                    if ($info !== null) {

                        $form = self::getDuelInfoForm($event, $info);
                        $event->sendFormWindow($form, [
                            'info-index' => $index,
                            'size' => isset($info['replay']) ? 4 : 3
                        ]);
                    }
                }
            }
        });

        $language = $player->getLanguage();

        $form->setTitle($language->formWindow(Language::DUEL_HISTORY_FORM_TITLE));
        $form->setContent($language->formWindow(Language::DUEL_HISTORY_FORM_DESC));

        $duelHistory = $player->getDuelHistory();

        $size = count($duelHistory);

        if ($size <= 0) {
            $form->addButton($language->generalMessage(Language::NONE));
            return $form;
        }

        $end = $size - 20 >= 0 ? $size - 20 : 0;

        for ($index = $size - 1; $index >= $end; $index--) {

            $resultingDuel = $duelHistory[$index];
            $winnerInfo = $resultingDuel['winner'];
            $loserInfo = $resultingDuel['loser'];
            $drawBool = (bool)$resultingDuel['draw'];

            if ($winnerInfo instanceof DuelInfo and $loserInfo instanceof DuelInfo) {

                $winnerDisplayName = $winnerInfo->getDisplayName();
                $loserDisplayName = $loserInfo->getDisplayName();

                $playerVsString = TextFormat::BLUE . '%player%' . TextFormat::DARK_GRAY . ' vs ' . TextFormat::BLUE . '%opponent%';

                $queue = $language->getRankedStr($winnerInfo->isRanked()) . ' ' . $winnerInfo->getQueue();

                $resultStr = TextFormat::DARK_GRAY . 'D';

                if ($drawBool) {
                    $playerVsString = str_replace('%player%', $winnerDisplayName, str_replace('%opponent%', $loserDisplayName, $playerVsString));
                } else {

                    if ($winnerInfo->getPlayerName() === $player->getName()) {
                        $playerVsString = str_replace('%player%', $winnerDisplayName, str_replace('%opponent%', $loserDisplayName, $playerVsString));
                        $resultStr = TextFormat::GREEN . 'W';
                    } elseif ($loserInfo->getPlayerName() === $player->getName()) {
                        $playerVsString = str_replace('%player%', $loserDisplayName, str_replace('%opponent%', $winnerDisplayName, $playerVsString));
                        $resultStr = TextFormat::RED . 'L';
                    }
                }

                $queueStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);

                $theQueueStr = TextFormat::BLUE . $queueStr . TextFormat::GRAY . ': ' . TextFormat::GOLD . $queue;

                if ($language->getLocale() === Language::ARABIC) {
                    $theQueueStr = TextFormat::GOLD . $queue . TextFormat::GRAY . ' :' . TextFormat::BLUE . $queueStr;
                }

                $form->addButton(
                    $playerVsString . "\n" . $resultStr . TextFormat::DARK_GRAY . ' | ' . $theQueueStr,
                    0,
                    $winnerInfo->getTexture()
                );
            }
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param array $info
     * @return SimpleForm
     */
    public static function getDuelInfoForm(MineceitPlayer $player, $info = []): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $index = (int)$data;

                    $size = (int)$formData['size'];

                    $inventoryInfoIndex = (int)$formData['info-index'];

                    $info = $event->getDuelInfo($inventoryInfoIndex);

                    $goBackIndex = $size - 1;

                    if ($info !== null and $index !== $goBackIndex) {

                        $language = $event->getLanguage();

                        $i = $index === 0 ? 'winner' : 'loser';

                        if ($size === 4) {

                            if ($index != 2) {

                                $inventory = new PostMatchInv($info[$i], $language);

                                $inventory->sendTo($event);

                            } else {

                                /** @var DuelReplayInfo $duelReplayInfo */
                                $duelReplayInfo = $info['replay'];

                                $replayManager = MineceitCore::getReplayManager();

                                $replayManager->startReplay($event, $duelReplayInfo);
                            }

                        } else {

                            $inventory = new PostMatchInv($info[$i], $language);

                            $inventory->sendTo($event);
                        }
                    } else {

                        if ($index === $goBackIndex) {
                            $form = self::getDuelHistoryForm($event);
                            $event->sendFormWindow($form);
                        }
                    }
                }
            }
        });

        /* @var DuelInfo $winnerInfo */
        $winnerInfo = $info['winner'];
        /* @var DuelInfo $loserInfo */
        $loserInfo = $info['loser'];

        $draw = (bool)$info['draw'];

        $language = $player->getLanguage();

        $winnerDisplayName = $winnerInfo->getDisplayName();
        $loserDisplayName = $loserInfo->getDisplayName();

        $playerVsString = TextFormat::BLUE . '%player%' . TextFormat::DARK_GRAY . ' vs ' . TextFormat::BLUE . '%opponent%';

        $isWinner = !$draw and $winnerInfo->getPlayerName() === $player->getName();

        if ($isWinner or $draw) {
            $playerVsString = str_replace('%player%', $winnerDisplayName, str_replace('%opponent%', $loserDisplayName, $playerVsString));
        } elseif ($loserInfo->getPlayerName() === $player->getName()) {
            $playerVsString = str_replace('%player%', $loserDisplayName, str_replace('%opponent%', $winnerDisplayName, $playerVsString));
        }

        $form->setTitle(TextFormat::YELLOW . TextFormat::BOLD . '» ' . $playerVsString . TextFormat::YELLOW . ' «');

        $winnerColor = ($draw) ? TextFormat::GOLD : TextFormat::GREEN;
        $loserColor = ($draw) ? TextFormat::GOLD : TextFormat::RED;

        $winnerDesc = $language->formWindow(Language::DUEL_INVENTORY_FORM_VIEW, [
                "name" => TextFormat::BOLD . $winnerColor . $winnerDisplayName . TextFormat::DARK_GRAY]
        );

        $loserDesc = $language->formWindow(Language::DUEL_INVENTORY_FORM_VIEW, [
            "name" => TextFormat::BOLD . $loserColor . $loserDisplayName . TextFormat::DARK_GRAY
        ]);

        $form->addButton(
            $winnerDesc,
            0,
            'textures/blocks/trapped_chest_front.png'
        );

        $form->addButton(
            $loserDesc,
            0,
            'textures/blocks/trapped_chest_front.png'
        );

        if (isset($info['replay'])) {
            $form->addButton(TextFormat::BOLD . $language->formWindow(Language::FORM_VIEW_REPLAY), 0, 'textures/items/compass_item.png');
        }

        $form->addButton($language->formWindow(Language::GO_BACK), 0, 'textures/gui/newgui/XPress.png');

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param array|string[] $onlinePlayers
     * @return CustomForm
     */
    public static function getGamemodeForm(MineceitPlayer $player, $onlinePlayers = []): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $senderLang = $event->getLanguage();

                    $playerNameIndex = (int)$data[0];
                    $gamemodeIndex = (int)$data[1];

                    $name = $formData['online-players'][$playerNameIndex];

                    if (($player = MineceitUtil::getPlayer($name)) !== null and $player instanceof MineceitPlayer) {

                        $lang = $player->getLanguage();

                        $gamemodes = [
                            0 => $lang->getMessage(Language::GAMEMODE_SURVIVAL) ?? "Survival",
                            1 => $lang->getMessage(Language::GAMEMODE_CREATIVE) ?? "Creative",
                            2 => $lang->getMessage(Language::GAMEMODE_ADVENTURE) ?? "Adventure",
                            3 => $lang->getMessage(Language::GAMEMODE_SPECTATOR) ?? "Spectator"
                        ];

                        $msg = $lang->generalMessage(Language::GAMEMODE_CHANGE, [
                            "gamemode" => $gamemodes[$gamemodeIndex]
                        ]);

                        if ($player->getGamemode() !== $gamemodeIndex) {
                            $player->sendMessage(MineceitUtil::getPrefix() . " " . TextFormat::RESET . $msg);
                        }

                        $player->setGamemode($gamemodeIndex);

                    } else {
                        $event->sendMessage(MineceitUtil::getPrefix() . " " . TextFormat::RED . $senderLang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]));
                    }
                }
            }
        });

        $language = $player->getLanguage();

        $form->setTitle(TextFormat::BOLD . TextFormat::GOLD . '» ' . TextFormat::DARK_PURPLE . 'GamemodeUI' . TextFormat::GOLD . ' «');

        $playerLabel = TextFormat::GOLD . $language->formWindow(Language::PLAYERS_LABEL) . TextFormat::GRAY . ':';

        if ($language->getLocale() === Language::ARABIC) {
            $playerLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $language->formWindow(Language::PLAYERS_LABEL);
        }

        $form->addDropdown($playerLabel, $onlinePlayers, array_search($player->getName(), $onlinePlayers));

        $menuLabel = TextFormat::GOLD . $language->formWindow(Language::GAMEMODE_FORM_MENU_LABEL) . TextFormat::GRAY . ':';

        if ($language->getLocale() === Language::ARABIC)
            $menuLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $language->formWindow(Language::GAMEMODE_FORM_MENU_LABEL);

        $gray = TextFormat::DARK_GRAY;

        $survival = $language->getMessage(Language::GAMEMODE_SURVIVAL) ?? "Survival";
        $creative = $language->getMessage(Language::GAMEMODE_CREATIVE) ?? "Creative";
        $adventure = $language->getMessage(Language::GAMEMODE_ADVENTURE) ?? "Adventure";
        $spectator = $language->getMessage(Language::GAMEMODE_SPECTATOR) ?? "Spectator";

        $form->addDropdown($menuLabel, [$gray . "{$survival} [Gm = 0]", $gray . "{$creative} [Gm = 1]", $gray . "{$adventure} [Gm = 2]", $gray . "{$spectator} [Gm = 3]"], $player->getGamemode());

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param array|string[] $onlinePlayers
     * @return CustomForm
     */
    public static function getFreezeForm(MineceitPlayer $player, $onlinePlayers = []): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $frozenIndex = (int)$data[1];

                    $name = $formData['online-players'][(int)$data[0]];

                    if (($player = MineceitUtil::getPlayer($name)) !== null and $player instanceof MineceitPlayer) {

                        $frozenVal = $frozenIndex === 0;

                        if ($frozenVal !== $player->isFrozen()) {

                            $language = $player->getLanguage();

                            switch ($frozenIndex) {

                                case 0:
                                    $title = $language->generalMessage(Language::FREEZE_TITLE);
                                    $subtitle = $language->generalMessage(Language::FREEZE_SUBTITLE);
                                    $player->setFrozenNameTag();
                                    $player->addTitle(TextFormat::GOLD . $title, TextFormat::BLUE . $subtitle, 5, 20, 5);
                                    break;
                                case 1:
                                    $title = $language->generalMessage(Language::UNFREEZE_TITLE);
                                    $subtitle = $language->generalMessage(Language::UNFREEZE_SUBTITLE);
                                    $player->setSpawnNameTag();
                                    $player->addTitle(TextFormat::GOLD . $title, TextFormat::BLUE . $subtitle, 5, 20, 5);
                                    break;
                            }
                        }

                        $player->setFrozen($frozenVal);

                    } else {
                        $event->sendMessage(MineceitUtil::getPrefix() . " " . $event->getLanguage()->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]));
                    }
                }
            }

        });

        $language = $player->getLanguage();

        $form->setTitle(TextFormat::BOLD . TextFormat::GOLD . '» ' . TextFormat::DARK_PURPLE . 'FreezeUI' . TextFormat::GOLD . ' «');

        $playerLabel = TextFormat::GOLD . $language->formWindow(Language::PLAYERS_LABEL) . TextFormat::GRAY . ':';

        if ($language->getLocale() === Language::ARABIC) {
            $playerLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $language->formWindow(Language::PLAYERS_LABEL);
        }

        $form->addDropdown($playerLabel, $onlinePlayers, array_search($player->getName(), $onlinePlayers));

        $menuLabel = TextFormat::GOLD . $language->formWindow(Language::GAMEMODE_FORM_MENU_LABEL) . TextFormat::GRAY . ':';

        if ($language->getLocale() === Language::ARABIC) {
            $menuLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $language->formWindow(Language::GAMEMODE_FORM_MENU_LABEL);
        }

        $form->addDropdown($menuLabel, [
            TextFormat::DARK_GRAY . $language->formWindow(Language::FREEZE_FORM_ENABLE),
            TextFormat::DARK_GRAY . $language->formWindow(Language::FREEZE_FORM_DISABLE)
        ]);

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param string $name
     * @param DuelRequest[]|array $requestInbox
     * @return SimpleForm
     */
    public static function getDuelInbox(MineceitPlayer $player, string $name, $requestInbox = []): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                $language = $event->getLanguage();

                $duelHandler = MineceitCore::getDuelHandler();

                if ($data !== null) {

                    $index = (int)$data;
                    $text = (string)$formData[$index]['text'];

                    if ($text !== $language->generalMessage(Language::NONE)) {

                        $requests = $formData['requests'];
                        $keys = array_keys($requests);
                        $name = $keys[$index];
                        $request = $requests[$name];

                        if ($request instanceof DuelRequest) {

                            $pName = $event->getName();

                            $opponentName = ($pName === $request->getToName()) ? $request->getFromName() : $request->getToName();

                            if (($opponent = MineceitUtil::getPlayer($opponentName)) !== null && $opponent instanceof MineceitPlayer && $opponent->isOnline()
                                && ($opponent->isInHub() || $opponent->isADuelSpec()) && !$opponent->isInParty() && !$opponent->isWatchingReplay() && !$opponent->isInEvent()) {

                                if ($event->isInHub()) {

                                    if ($opponent->isADuelSpec()) {
                                        $duel = $duelHandler->getDuelFromSpec($opponent);
                                        $duel->removeSpectator($opponent, false, false);
                                    } elseif ($opponent->isWatchingReplay()) {
                                        $replay = MineceitCore::getReplayManager()->getReplayFrom($opponent);
                                        $replay->endReplay(false);
                                    }

                                    $duelHandler->getRequestHandler()->acceptRequest($request);
                                    $duelHandler->placeInDuel($event, $opponent, $request->getQueue(), $request->isRanked(), false, $request->getGeneratorName());

                                } else $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::ACCEPT_FAIL_NOT_IN_LOBBY));

                            } else {

                                $message = null;

                                if ($opponent === null || !$opponent->isOnline())
                                    $message = $language->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $opponentName]);
                                elseif ($opponent->isInDuel())
                                    $message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_DUEL, ["name" => $opponentName]);
                                elseif ($opponent->isInArena())
                                    $message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_ARENA, ["name" => $opponentName]);
                                elseif ($opponent->isInParty()) ;
                                // TODO ADD A MESSAGE SAYING OPPONENT IS IN A PARTY
                                elseif ($opponent->isWatchingReplay())
                                    $message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_WATCH_REPLAY, ["name" => $opponentName]);
                                elseif ($opponent->isInEvent()) {
                                    // TODO LANGUAGE
                                    $message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_EVENT, ["name" => $opponentName]);
                                }


                                if ($message !== null) $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                            }
                        }
                    }
                }
            }
        });

        $language = $player->getLanguage();

        $count = count($requestInbox);

        $desc = $language->formWindow(Language::FORM_CLICK_REQUEST_ACCEPT);

        $clean = trim(TextFormat::clean(str_replace("»", "", str_replace("«", "", $name))));

        $len = strlen($clean);
        if ($len > 30 and $language->shortenString()) {
            $clean = substr($clean, 0, 25) . "...";
        }

        $name = TextFormat::BOLD . TextFormat::YELLOW . '» ' . TextFormat::DARK_PURPLE . $clean . TextFormat::YELLOW . ' «';

        $form->setTitle($name);
        $form->setContent($desc);

        if ($count <= 0) {
            $form->addButton($language->generalMessage(Language::NONE));
            return $form;
        }

        $keys = array_keys($requestInbox);

        $queueStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);

        foreach ($keys as $name) {

            $name = (string)$name;

            $request = $requestInbox[$name];

            $ranked = $language->getRankedStr($request->isRanked());
            $queue = $ranked . ' ' . $request->getQueue();

            $theQueueStr = TextFormat::BLUE . $queueStr . TextFormat::GRAY . ': ' . TextFormat::GOLD . $queue;

            $sentBy = $language->formWindow(Language::FORM_SENT_BY, [
                "name" => $name
            ]);

            $text = $sentBy . "\n" . $theQueueStr;

            $form->addButton($text, 0, $request->getTexture());
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     */
    public static function getRequestForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {


            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $firstIndex = $formData[0];

                    if ($firstIndex['type'] !== 'label') {

                        $senderLang = $event->getLanguage();

                        $playerName = $firstIndex['options'][(int)$data[0]];

                        $queue = $formData[1]['options'][(int)$data[1]];

                        $ranked = (int)$data[2] !== 0;

                        $requestHandler = MineceitCore::getDuelHandler()->getRequestHandler();

                        if (($to = $event->getServer()->getPlayer($playerName)) !== null and $to instanceof MineceitPlayer) {

                            // TODO IMPLEMENT GENERATOR

                            $requestHandler->sendRequest($event, $to, $queue, $ranked);

                        } else {

                            $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RED . $senderLang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $playerName]));
                        }
                    }
                }
            }
        });

        $onlinePlayers = $player->getServer()->getOnlinePlayers();

        $language = $player->getLanguage();

        $requestADuel = $language->formWindow(Language::REQUEST_FORM_TITLE);

        $form->setTitle(TextFormat::GOLD . TextFormat::BOLD . '» ' . TextFormat::DARK_PURPLE . $requestADuel . TextFormat::GOLD . ' «');

        $ranked = $language->getRankedStr(true);
        $unranked = $language->getRankedStr(false);

        $dropdownArr = [];

        $name = $player->getName();

        $size = count($onlinePlayers);

        foreach ($onlinePlayers as $p) {
            $pName = $p->getName();
            if ($pName !== $name)
                $dropdownArr[] = $pName;
        }

        $sendRequest = $language->formWindow(Language::REQUEST_FORM_SEND_TO);
        $selectQueue = $language->formWindow(Language::REQUEST_FORM_SELECT_QUEUE);
        $setDuelRankedOrUnranked = $language->formWindow(Language::REQUEST_FORM_RANKED_OR_UNRANKED);

        if (($size - 1) > 0) {
            $form->addDropdown($sendRequest, $dropdownArr);
            $form->addDropdown($selectQueue, MineceitCore::getKits()->getKits(true));
            $form->addDropdown($setDuelRankedOrUnranked, [$unranked, $ranked]);
        } else
            $form->addLabel($language->formWindow(Language::REQUEST_FORM_NOBODY_ONLINE));

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param MineceitDuel|array $duels
     * @return SimpleForm
     */
    public static function getSpectateForm(MineceitPlayer $player, $duels = []): SimpleForm
    {
        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                $lang = $event->getLanguage();

                if ($data !== null) {

                    $index = (int)$data;
                    $firstText = $formData[$index]['text'];

                    if ($lang->generalMessage(Language::NONE) !== $firstText) {

                        $duel = $formData['duels'][$index];

                        if ($duel instanceof MineceitDuel) {

                            $duelHandler = MineceitCore::getDuelHandler();

                            $worldId = $duel->getWorldId();

                            $currentDuels = $duelHandler->getDuels();

                            if (isset($currentDuels[$worldId]) && $currentDuels[$worldId]->equals($duel)) {

                                $duel = $currentDuels[$worldId];
                                $duelHandler->addSpectatorTo($event, $duel);

                            } else {

                                $msg = $lang->generalMessage(Language::DUEL_ALREADY_ENDED);
                                $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $msg);
                            }
                        }
                    }
                }
            }
        });

        $lang = $player->getLanguage();

        $title = TextFormat::BOLD . TextFormat::LIGHT_PURPLE . '» ' . $lang->formWindow(Language::SPECTATE_FORM_TITLE) . TextFormat::LIGHT_PURPLE . ' «';

        $form->setTitle($title);

        $form->setContent(TextFormat::GOLD . TextFormat::BOLD . $lang->formWindow(Language::SPECTATE_FORM_DESC));

        $size = count($duels);

        if ($size <= 0) {
            $form->addButton($lang->generalMessage(Language::NONE));
            return $form;
        }

        foreach ($duels as $duel) {

            $texture = $duel->getTexture();

            $name = TextFormat::BLUE . $duel->getP1Name() . TextFormat::DARK_GRAY . ' vs ' . TextFormat::BLUE . $duel->getP2Name();

            $form->addButton($name, 0, $texture);
        }

        return $form;
    }

    /** ---------------------------------- PARTY FORMS ---------------------------------------- */

    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getDefaultPartyForm(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null and $event->isInHub()) {

                    $partyManager = MineceitCore::getPartyManager();

                    $option = $formData['party-option'];

                    $index = (int)$data;

                    if ($index === 0) {

                        if ($option === 'join') {

                            $form = FormUtil::getJoinPartyForm($event);
                            $event->sendFormWindow($form, ['parties' => $partyManager->getParties()]);

                        } else {
                            $party = $partyManager->getPartyFromPlayer($event);
                            if ($party !== null)
                                $party->removePlayer($event);
                            else;
                        }
                    } elseif ($index === 1) {
                        // TODO CHECK FOR PERMISSION TO CREATE A NEW PARTY
                        if (!$event->isInParty()) {
                            $form = FormUtil::getCreatePartyForm($event);
                            $event->sendFormWindow($form);
                        }
                    }
                }
            }
        });

        $lang = $player->getLanguage();

        $title = $lang->formWindow(Language::FORM_PARTIES_DEFAULT_TITLE);

        $form->setTitle($title);

        $content = $lang->formWindow(Language::FORM_PARTIES_DEFAULT_CONTENT);

        $form->setContent($content);

        $partyManager = MineceitCore::getPartyManager();

        $p = $partyManager->getPartyFromPlayer($player);

        $leave = TextFormat::BOLD . TextFormat::RED . $lang->formWindow(Language::FORM_PARTIES_DEFAULT_LEAVE);
        $join = TextFormat::BOLD . TextFormat::GREEN . $lang->formWindow(Language::FORM_PARTIES_DEFAULT_JOIN);
        $create = TextFormat::BLUE . TextFormat::BOLD . $lang->formWindow(Language::FORM_PARTIES_CREATE);

        $text = ($p !== null) ? $leave : $join;

        $form->addButton($text);

        // TODO SET A PERMISSION TO CREATE A NEW PARTY
        if (!$player->isInParty())
            $form->addButton($create);

        return $form;

    }

    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     */
    public static function getCreatePartyForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null and $event->isInHub()) {

                    $partyManager = MineceitCore::getPartyManager();

                    $partyName = (string)$data[0];
                    $maxPlayers = (int)$data[1];
                    $inviteOnly = (bool)$data[2];

                    $partyManager->createParty($event, $partyName, $maxPlayers, !$inviteOnly);
                }
            }
        });

        $lang = $player->getLanguage();

        $name = $player->getName();

        $title = TextFormat::BOLD . TextFormat::BLUE . '» ' . TextFormat::DARK_PURPLE . $lang->formWindow(Language::FORM_PARTIES_CREATE) . TextFormat::BLUE . ' «';

        $form->setTitle($title);

        $default = "$name's Party";

        $partyName = TextFormat::GOLD . $lang->formWindow(Language::FORM_PARTIES_PARTYNAME) . TextFormat::RESET . ':';

        $maxPlayers = TextFormat::GOLD . $lang->formWindow(Language::FORM_PARTIES_MAX_PLAYERS) . TextFormat::RESET;

        $inviteOnly = TextFormat::GOLD . $lang->formWindow(Language::FORM_PARTIES_INVITE_ONLY);

        $form->addInput($partyName, $default, $default);

        $form->addSlider($maxPlayers, 5, MineceitParty::MAX_PLAYERS, 1, 5);

        $form->addToggle($inviteOnly, false);

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @return SimpleForm
     */
    public static function getJoinPartyForm(MineceitPlayer $player): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null and $event->isInHub()) {

                    $index = (int)$data;

                    $text = (string)$formData[$index]['text'];

                    $lang = $event->getLanguage();

                    if ($text !== $lang->generalMessage(Language::NONE)) {

                        /* @var MineceitParty[] $parties */
                        $parties = array_values($formData['parties']);

                        if (!isset($parties[$index]))
                            return;

                        $partyManager = MineceitCore::getPartyManager();


                        $party = $parties[$index];
                        $name = $party->getName();

                        $party = $partyManager->getPartyFromName($name);

                        if ($party === null) {
                            // TODO SEND MESSAGE THAT PARTY NO LONGER EXISTS
                            return;
                        }

                        $maxPlayers = $party->getMaxPlayers();

                        $currentPlayers = (int)$party->getPlayers(true);

                        $blacklisted = $party->isBlackListed($event);

                        if ($party->isOpen() and $currentPlayers < $maxPlayers and !$blacklisted)
                            $party->addPlayer($event);
                        else {
                            // TODO MESSAGES
                            if ($currentPlayers >= $maxPlayers) {
                                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . 'That party is full!';
                                $event->sendMessage($msg);
                            } elseif (!$party->isOpen()) {
                                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . 'That party is now invite only.';
                                $event->sendMessage($msg);
                            } elseif ($blacklisted) {
                                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . 'You are blacklisted from that party.';
                                $event->sendMessage($msg);
                            }
                        }


                        /*$firstLine = TextFormat::clean((string)explode("\n", $text)[0]);

                        $name = $firstLine;

                        $blackListedStr = '[' . $lang->formWindow(Language::BLACKLISTED) . ']';

                        if (strpos($firstLine, $blackListedStr) !== false) {

                            $arr = explode(' ', $firstLine);
                            $name = '';
                            $size = count($arr) - 1;

                            for ($i = 1; $i <= $size; $i++) {

                                $string = (string)$arr[$i];

                                $space = $i !== $size ? ' ' : '';

                                $name .= $string . $space;
                            }
                        }

                        $partyManager = MineceitCore::getPartyManager();

                        $party = $partyManager->getPartyFromName($name);

                        if ($party === null) return;

                        $maxPlayers = $party->getMaxPlayers();

                        $currentPlayers = (int)$party->getPlayers(true);

                        $blackListed = $party->isBlackListed($event);

                        if ($party->isOpen() and $currentPlayers < $maxPlayers and !$blackListed)
                            $party->addPlayer($event);

                        else {
                            // TODO MESSAGES
                            if ($currentPlayers >= $maxPlayers) {
                                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . 'That party is full!';
                                $event->sendMessage($msg);
                            } elseif (!$party->isOpen()) {
                                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . 'That party is now invite only.';
                                $event->sendMessage($msg);
                            } elseif ($blackListed) {
                                $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . 'You are blacklisted from that party.';
                                $event->sendMessage($msg);
                            }
                        } */
                    }
                }
            }

        });

        $lang = $player->getLanguage();

        $joinParty = TextFormat::GREEN . TextFormat::BOLD . '» ' . TextFormat::DARK_PURPLE . $lang->formWindow(Language::FORM_PARTIES_DEFAULT_JOIN) . TextFormat::GREEN . ' «';

        $form->setTitle($joinParty);

        $listOfParties = $lang->formWindow(Language::FORM_PARTIES_JOIN_LIST);

        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . $listOfParties);

        $partyManager = MineceitCore::getPartyManager();

        $parties = $partyManager->getParties();

        $size = count($parties);

        if ($size <= 0) {
            $none = $lang->generalMessage(Language::NONE);
            $form->addButton($none);
            return $form;
        }

        $openStr = $lang->formWindow(Language::OPEN);
        $closedStr = $lang->formWindow(Language::CLOSED);
        $blacklistedStr = $lang->formWindow(Language::BLACKLISTED);

        foreach ($parties as $party) {
            $name = TextFormat::BOLD . TextFormat::GOLD . $party->getName();
            $numPlayers = $party->getPlayers(true);
            $maxPlayers = $party->getMaxPlayers();
            $isBlacklisted = $party->isBlackListed($player);
            $blacklisted = ($isBlacklisted) ? TextFormat::DARK_GRAY . '[' . TextFormat::RED . $blacklistedStr . TextFormat::DARK_GRAY . '] ' : '';
            $open = $party->isOpen() ? TextFormat::GREEN . $openStr : TextFormat::RED . $closedStr;
            $text = $blacklisted . $name . "\n" . TextFormat::RESET . TextFormat::BLUE . $numPlayers . '/' . $maxPlayers . TextFormat::DARK_GRAY . ' | ' . $open;
            $form->addButton($text);
        }

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param bool $isOwner
     * @return SimpleForm
     */
    public static function getLeavePartyForm(MineceitPlayer $player, bool $isOwner): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $partyManager = MineceitCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($event);

                    $index = (int)$data;

                    if ($index === 0)
                        $party->removePlayer($event);
                }
            }

        });

        $lang = $player->getLanguage();

        $title = TextFormat::BOLD . TextFormat::RED . '» ' . TextFormat::DARK_PURPLE . $lang->formWindow(Language::FORM_PARTIES_DEFAULT_LEAVE) . TextFormat::RED . ' «';

        $content = ($isOwner ? $lang->formWindow(Language::FORM_QUESTION_LEAVE_OWNER) : $lang->formWindow(Language::FORM_QUESTION_LEAVE));

        $form->setTitle($title);
        $form->setContent($content);

        $form->addButton($lang->formWindow(Language::YES));
        $form->addButton($lang->formWindow(Language::NO));

        return $form;
    }

    /**
     * @param MineceitParty $party
     * @return CustomForm
     */
    public static function getPartySettingsForm(MineceitParty $party): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $inviteOnly = (bool)$data[1];

                    $maxPlayers = (int)$data[2];

                    $partyManager = MineceitCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($event);

                    $party->setOpen(!$inviteOnly);

                    $party->setMaxPlayers($maxPlayers);

                    // TODO SEND MESSAGE
                }
            }
        });

        $owner = $party->getOwner();

        $lang = $owner->getLanguage();

        $inviteOnly = !$party->isOpen();

        $partySettings = TextFormat::GOLD . TextFormat::BOLD . '» ' . TextFormat::DARK_PURPLE . $lang->formWindow(Language::FORM_PARTY_SETTINGS) . TextFormat::GOLD . ' «';

        $partyName = TextFormat::GOLD . $lang->formWindow(Language::FORM_PARTIES_PARTYNAME) . TextFormat::RESET . ":\n";

        $inviteOnlyStr = TextFormat::GOLD . $lang->formWindow(Language::FORM_PARTIES_INVITE_ONLY);

        $maxPlayersStr = TextFormat::GOLD . $lang->formWindow(Language::FORM_PARTIES_MAX_PLAYERS) . TextFormat::RESET;

        $form->setTitle($partySettings);

        $form->addLabel($partyName . TextFormat::RESET . " " . $party->getName());

        $form->addToggle($inviteOnlyStr, $inviteOnly);

        $form->addSlider($maxPlayersStr, 5, MineceitParty::MAX_PLAYERS, 1, $party->getMaxPlayers());

        return $form;
    }

    /**
     * @param MineceitParty $party
     * @param string[]|array $players
     * @return CustomForm
     */
    public static function getKickPlayerForm(MineceitParty $party, $players = []): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null and isset($data[0], $data[1], $data[2])) {

                    $index = (int)$data[0];

                    $player = $formData['players'][$index];

                    $blackList = (bool)$data[2];

                    $partyManager = MineceitCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($event);

                    $lang = $event->getLanguage();

                    if ($party->isPlayer($player)) {

                        $reason = (string)$data[1];

                        $p = $party->getPlayer($player);

                        $party->removePlayer($p, $reason, $blackList);

                    } else {

                        // TODO SEND MESSAGE SAYING PLAYER IS NO LONGER IN YOUR PARTY

                    }
                }
            }
        });

        $owner = $party->getOwner();

        $lang = $owner->getLanguage();

        //$title = $lang->formWindow(Language::Ki)

        $form->setTitle('Kick a Player');

        $size = count($players);

        // TODO ADD MESSAGES

        if ($size <= 0) {
            $form->addLabel("You don't have any players in your party.");
            return $form;
        }

        $playerLabel = TextFormat::GOLD . $lang->formWindow(Language::PLAYERS_LABEL) . TextFormat::GRAY . ':';

        if ($lang->getLocale() === Language::ARABIC)
            $playerLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $lang->formWindow(Language::PLAYERS_LABEL);

        $form->addDropdown($playerLabel, $players);

        $form->addInput('Reason:');

        $form->addToggle('Add to Blacklist:', false);

        return $form;
    }

    /**
     * @param MineceitParty $party
     * @return SimpleForm
     */
    public static function getPartyOptionsForm(MineceitParty $party): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $index = (int)$data;

                    $partyManager = MineceitCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($event);

                    $name = $event->getName();

                    switch ($index) {

                        case 0:

                            $form = FormUtil::getPartySettingsForm($party);
                            $event->sendFormWindow($form);
                            break;

                        case 1:

                            $players = $party->getPlayers();
                            $listPlayers = [];

                            foreach ($players as $p) {
                                $pName = $p->getName();
                                if ($pName !== $name)
                                    $listPlayers[] = $pName;
                            }

                            $form = FormUtil::getKickPlayerForm($party, $listPlayers);
                            $event->sendFormWindow($form, ['players' => $listPlayers]);
                            break;

                        case 2:

                            $blacklisted = $party->getBlacklisted();

                            $form = FormUtil::getBlackListedForm($event, $blacklisted);
                            $event->sendFormWindow($form);
                            break;

                        case 3:

                            $players = $party->getPlayers();

                            $possiblePromotions = [];

                            $ownerName = $event->getName();

                            foreach ($players as $p) {
                                $name = $p->getName();
                                if ($ownerName !== $name)
                                    $possiblePromotions[] = $name;
                            }

                            $form = FormUtil::getPartyPromotePlayerForm($event, $possiblePromotions);
                            $event->sendFormWindow($form, ['players' => $possiblePromotions]);
                            break;

                    }
                }

            }

        });

        // TODO ADD MESSAGES

        $owner = $party->getOwner();

        $lang = $owner->getLanguage();

        $form->setTitle('Party Options');

        $form->setContent("Edit the party's settings or kick a player.");

        $form->addButton('Edit Party Settings');

        $form->addButton('Kick a Player');

        $form->addButton('Edit Blacklisted Players');

        $form->addButton('Promote player to Owner');

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param array|string[] $blacklisted
     * @return SimpleForm
     */
    public static function getBlackListedForm(MineceitPlayer $player, $blacklisted = []): SimpleForm
    {
        $form = new SimpleForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $partyManager = MineceitCore::getPartyManager();
                    $party = $partyManager->getPartyFromPlayer($event);

                    $server = $event->getServer();

                    $index = (int)$data;

                    switch ($index) {

                        case 0:

                            $players = [];

                            $onlinePlayers = $server->getOnlinePlayers();

                            foreach ($onlinePlayers as $p) {
                                $name = $p->getName();
                                if (!$party->isBlackListed($name) and !$party->isPlayer($name))
                                    $players[] = $name;
                            }

                            $form = FormUtil::getEditBlackListForm($event, 'add', $players);
                            $event->sendFormWindow($form, ['option' => 'add', 'players' => $players]);
                            break;

                        case 1:

                            $players = $party->getBlacklisted();

                            $form = FormUtil::getEditBlackListForm($event, 'remove', $players);
                            $event->sendFormWindow($form, ['option' => 'remove', 'players' => $players]);

                            break;
                    }
                }
            }

        });

        // TODO ADD MESSAGE

        $lang = $player->getLanguage();

        $form->setTitle('List of Blacklisted Players');

        $blacklistedStr = $lang->formWindow(Language::BLACKLISTED);

        $content = "$blacklistedStr: ";

        $size = count($blacklisted);

        if ($size <= 0) {

            $none = $lang->generalMessage(Language::NONE);
            $content .= $none;

        } else {

            $size = count($blacklisted) - 1;
            $count = 0;

            foreach ($blacklisted as $player) {
                $comma = $count === $size ? '' : ', ';
                $content .= $player . $comma;
                $count++;
            }
        }

        $form->setContent($content);

        $form->addButton('Add to Blacklist');
        $form->addButton('Remove from Blacklist');

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param string $type
     * @param array|string[] $players
     * @return CustomForm
     */
    public static function getEditBlackListForm(MineceitPlayer $player, string $type, array $players): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null and isset($data[0])) {

                    $index = (int)$data[0];

                    $name = (string)$formData['players'][$index];

                    $option = (string)$formData['option'];

                    $partyManager = MineceitCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($event);

                    $lang = $event->getLanguage();

                    // TODO ADD MESSAGES

                    if ($option === 'add') {
                        $party->addToBlacklist($name);
                    } elseif ($option === 'remove') {
                        $party->removeFromBlacklist($name);
                    }
                }
            }
        });

        // TODO ADD MESSAGES

        $lang = $player->getLanguage();

        $size = count($players);

        $title = ($type === 'add') ? 'Add to Blacklist' : 'Remove From Blacklist';

        $form->setTitle($title);

        $playerLabel = TextFormat::GOLD . $lang->formWindow(Language::PLAYERS_LABEL) . TextFormat::GRAY . ':';

        if ($lang->getLocale() === Language::ARABIC)
            $playerLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $lang->formWindow(Language::PLAYERS_LABEL);

        if ($size <= 0) {
            $label = ($type === 'add') ? "There aren't any players to add to the blacklist." : "There isn't anyone blacklisted to your party.";
            $form->addLabel($label);
            return $form;
        }

        $form->addDropdown($playerLabel, $players);

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param array|string[] $possiblePromotions
     * @return CustomForm
     */
    public static function getPartyPromotePlayerForm(MineceitPlayer $player, $possiblePromotions = []): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null and isset($data[0])) {

                    $index = $data[0];

                    $name = (string)$formData['players'][$index];

                    $partyManager = MineceitCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($event);

                    if ($party->isPlayer($name)) {

                        $player = $party->getPlayer($name);

                        $party->promoteToOwner($player);

                        // TODO SEND MESSAGE

                    } else {

                        // TODO SEND MESSAGE SAYING PLAYER IS NO LONGER IN YOUR PARTY

                    }
                }
            }

        });

        // TODO ADD MESSAGES

        $form->setTitle('Promote to Owner');

        $lang = $player->getLanguage();

        $playerLabel = TextFormat::GOLD . $lang->formWindow(Language::PLAYERS_LABEL) . TextFormat::GRAY . ':';

        if ($lang->getLocale() === Language::ARABIC)
            $playerLabel = TextFormat::GRAY . ' :' . TextFormat::GOLD . $lang->formWindow(Language::PLAYERS_LABEL);

        $size = count($possiblePromotions);

        if ($size <= 0) {
            $form->addLabel("There aren't any players in your party.");
            return $form;
        }

        $form->addDropdown($playerLabel, $possiblePromotions);

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     *
     * Gets the form for creating ranks.
     *
     * TODO: UPDATE WITH NEW PERMISSIONS -> LATER
     */
    public static function getCreateRankForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                $language = $event->getLanguage();

                if ($data !== null) {

                    $name = $data[1];
                    $localName = strtolower($name);
                    $format = $data[2];
                    $fly = (bool)$data[3];
                    $edit = (bool)$data[4];
                    $permissionIndex = (int)$data[5];

                    $permission = Rank::PERMISSION_INDEXES[$permissionIndex];

                    $rankHandler = MineceitCore::getRankHandler();

                    $rank = $rankHandler->getRank($localName);
                    if ($rank === null) {
                        $rank = $rankHandler->getRank($name);
                    }

                    if ($rank !== null) {
                        $msg = MineceitUtil::getPrefix() . " " . TextFormat::RESET . $language->rankMessage($rank->getName(), Language::RANK_EXISTS);
                        $event->sendMessage($msg);
                        return;
                    }

                    $created = $rankHandler->createRank($name, $format, $fly, $edit, $permission);

                    if ($created) {
                        $msg = $language->rankMessage($name, Language::RANK_CREATE_SUCCESS);
                    } else {
                        $msg = $language->rankMessage($name, Language::RANK_CREATE_FAIL);
                    }

                    $event->sendMessage(MineceitUtil::getPrefix() . " " . TextFormat::RESET . $msg);
                }
            }
        });

        $language = $player->getLanguage();

        $form->setTitle(TextFormat::BOLD . $language->getMessage(Language::CREATE_RANK_TITLE));

        $form->addLabel($language->getMessage(Language::CREATE_RANK_DESC));

        $form->addInput($language->getMessage(Language::CREATE_RANK_NAME));

        $form->addInput($language->getMessage(Language::CREATE_RANK_FORMAT));

        $form->addToggle($language->getMessage(Language::CREATE_RANK_FLY), false);

        $form->addToggle($language->getMessage(Language::CREATE_RANK_BUILDER_MODE), false);

        $form->addDropdown($language->getMessage(Language::CREATE_RANK_PERMS), [
            "Owner",
            "Admin",
            "Mod",
            "Trial-Mod",
            "Content Creator",
            "VIP+",
            "VIP",
            "Normal"
        ], 4);

        return $form;
    }

    /**
     * @param MineceitPlayer $player
     * @param int $perms
     * @return SimpleForm
     *
     * The general report menu form.
     */
    public static function getReportMenuForm(MineceitPlayer $player, int $perms = 0): SimpleForm
    {

        $form = new SimpleForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                if ($data !== null) {

                    $perms = (int)$formData["perms"];
                    $index = (int)$data;

                    switch ($index) {
                        case 0:
                            $staffMembers = MineceitCore::getPlayerHandler()->getStaffOnline(true, $event);
                            $form = self::getReportStaffForm($event, $staffMembers);
                            if ($form !== null) {
                                $event->sendFormWindow($form, ['staff' => $staffMembers]);
                            }
                            break;
                        case 1:
                            $form = self::getReportBugForm($event);
                            $event->sendFormWindow($form);
                            break;
                        case 2:
                            $playerManager = MineceitCore::getPlayerHandler();
                            $languages = array_values($playerManager->getLanguages());
                            $form = self::getReportMisTranslationForm($event, $languages);
                            $outputLanguages = array_map(function (Language $lang) {
                                return $lang->getLocale();
                            }, $languages);
                            $event->sendFormWindow($form, ["languages" => $outputLanguages]);
                            break;
                        case 3:

                            $onlinePlayers = $event->getServer()->getOnlinePlayers();
                            $players = array_diff(array_map(function (Player $player) {
                                return $player->getName();
                            }, $onlinePlayers), [$event->getName()]);
                            $players = array_values($players);

                            $form = self::getReportHackerForm($event, $players);
                            if ($form !== null) {
                                $event->sendFormWindow($form, ["players" => $players]);
                            }
                            break;

                        case 4:

                            $reports = array_values($event->getReportHistory());
                            $form = self::getListOfReports($event, $reports);

                            if ($perms !== ReportInfo::PERMISSION_NORMAL) {
                                $form = self::getViewReportsSearchForm($event);
                            }

                            if ($form !== null) {
                                $event->sendFormWindow($form, ['reports' => $reports, 'history' => true, 'perm' => $event->getReportPermissions()]);
                            }

                            break;
                    }
                }
            }

        });

        $lang = $player->getLanguage();

        $title = $lang->getMessage(Language::REPORTS_MENU_FORM_TITLE);
        $form->setTitle($title);

        $desc = $lang->getMessage(Language::REPORTS_MENU_FORM_DESC);
        $form->setContent($desc);

        $types = [
            $lang->getMessage(Language::REPORTS_MENU_FORM_STAFF) => "textures/blocks/glass_red.png",
            $lang->getMessage(Language::REPORTS_MENU_FORM_BUG) => "textures/blocks/glass_magenta.png",
            $lang->getMessage(Language::REPORTS_MENU_FORM_TRANSLATION) => "textures/blocks/glass_purple.png",
            $lang->getMessage(Language::REPORTS_MENU_FORM_HACKER) => "textures/blocks/glass_blue.png"
        ];

        foreach ($types as $type => $texture) {
            $form->addButton($type, 0, $texture);
        }

        $text = $lang->getMessage(Language::REPORTS_MENU_FORM_YOUR_HISTORY);

        switch ($perms) {
            case ReportInfo::PERMISSION_VIEW_ALL_REPORTS:
                $text = $lang->getMessage(Language::REPORTS_MENU_FORM_VIEW_REPORTS);
                break;
            case ReportInfo::PERMISSION_MANAGE_REPORTS:
                $text = $lang->getMessage(Language::REPORTS_MENU_FORM_MANAGE_REPORTS);
                break;
        }

        $form->addButton($text, 0, "textures/items/book_written.png");

        return $form;
    }


    /**
     * @param array|null $staffMembers
     * @param MineceitPlayer $player
     * @return CustomForm|null
     *
     * Gets the report staff form.
     */
    public static function getReportStaffForm(MineceitPlayer $player, array $staffMembers = null)
    {

        /** @var string[] $onlineStaff */
        $onlineStaff = $staffMembers ?? MineceitCore::getPlayerHandler()->getStaffOnline(true, $player);

        $lang = $player->getLanguage();

        if (count($onlineStaff) <= 0) {
            $message = $lang->getMessage(Language::NO_STAFF_MEMBERS_ONLINE);
            $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
            return null;
        }

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                $staffMembers = array_values($formData['staff']);

                if ($data !== null) {

                    $lang = $event->getLanguage();

                    $dropdownResultIndex = (int)$data[1];
                    $reason = (string)$data[2];

                    if ($reason === "") {
                        $message = $lang->getMessage(Language::REPORT_NO_REASON);
                        $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                        return;
                    }

                    $resultingStaff = $staffMembers[$dropdownResultIndex];
                    $server = $event->getServer();

                    $name = (string)$resultingStaff;
                    $report = new StaffReport($event, $name, $reason);
                    $reportType = $report->getReportType();

                    if (($player = $server->getPlayer($name)) !== null and $player instanceof MineceitPlayer) {

                        if ($player->hasReport($event, $reportType)) {
                            $message = $lang->getMessage(Language::REPORT_PLAYER_ALREADY);
                            $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                            return;
                        }

                        $player->addReport($report);

                        if ($player->getOnlineReportsCount($reportType) > ReportInfo::MAX_REPORT_NUM) {
                            // TODO SUSPEND THE PLAYER'S PERMISSIONS
                        }
                    }

                    $reportManager = MineceitCore::getReportManager();
                    $result = $reportManager->createReport($report);

                    if ($result) {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
                    } else {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
                    }

                    $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                }
            }

        });

        $title = $lang->getMessage(Language::STAFF_REPORT_FORM_TITLE);
        $desc = $lang->getMessage(Language::STAFF_REPORT_FORM_DESC);

        $form->setTitle($title);
        $form->addLabel($desc);

        $form->addDropdown($lang->getMessage(Language::STAFF_REPORT_FORM_MEMBERS), $onlineStaff);

        $form->addInput($lang->getMessage(Language::FORM_LABEL_REASON_FOR_REPORTING), "");

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     *
     * The bug reports form.
     */
    public static function getReportBugForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $lang = $event->getLanguage();

                    $description = (string)$data[2];
                    $reproduce = (string)$data[4];

                    $descWordCount = MineceitUtil::getWordCount($description);

                    if ($descWordCount < 5) {
                        $message = $lang->getMessage(Language::REPORT_FIVE_WORDS);
                        $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                        return;
                    }

                    $report = new BugReport($event, $description, $reproduce);
                    $reportManager = MineceitCore::getReportManager();
                    $result = $reportManager->createReport($report);

                    if ($result) {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
                    } else {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
                    }

                    $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                }
            }
        });

        $lang = $player->getLanguage();

        $title = $lang->getMessage(Language::BUG_REPORT_FORM_TITLE);
        $desc = $lang->getMessage(Language::BUG_REPORT_FORM_DESC);

        $form->setTitle($title);
        $form->addLabel($desc . "\n");

        $descriptionLabel = TextFormat::BOLD . $lang->getMessage(Language::BUG_REPORT_FORM_DESC_LABEL_HEADER);
        $form->addLabel($descriptionLabel);

        $description = $lang->getMessage(Language::BUG_REPORT_FORM_DESC_LABEL_FOOTER);
        $form->addInput($description, "");

        $reproduceLabel = TextFormat::BOLD . $lang->getMessage(Language::BUG_REPORT_FORM_REPROD_LABEL_HEADER);
        $form->addLabel($reproduceLabel);

        $reproduce = $lang->getMessage(Language::BUG_REPORT_FORM_REPROD_LABEL_FOOTER);
        $form->addInput($reproduce, "");

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @param Language[]|array $languages
     * @return CustomForm
     */
    public static function getReportMisTranslationForm(MineceitPlayer $player, array $languages): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {


            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();
                $languages = (array)$formData["languages"];

                if ($data !== null) {

                    $lang = $event->getLanguage();

                    $index = (int)$data[1];
                    $language = $languages[$index];

                    $originalPhrase = (string)$data[2];
                    $newPhrase = (string)$data[3];

                    if ($originalPhrase === "") {
                        $message = $lang->getMessage(Language::REPORT_TRANSLATE_ORIG_PHRASE);
                        $event->sendMessage(MineceitUtil::getPrefix() . " " . TextFormat::RESET . $message);
                        return;
                    }

                    if ($newPhrase === "") {
                        $message = $lang->getMessage(Language::REPORT_TRANSLATE_NEW_PHRASE);
                        $event->sendMessage(MineceitUtil::getPrefix() . " " . TextFormat::RESET . $message);
                        return;
                    }

                    $reportManager = MineceitCore::getReportManager();
                    $report = new TranslateReport($event, $language, $originalPhrase, $newPhrase);
                    $result = $reportManager->createReport($report);

                    if ($result) {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
                    } else {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
                    }

                    $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                }
            }

        });

        $lang = $player->getLanguage();

        $title = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_TITLE);
        $desc = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_DESC);

        $form->setTitle($title);
        $form->addLabel($desc);

        $languages = array_values($languages);

        $dropdownArray = [];

        $defaultIndex = null;

        foreach ($languages as $key => $language) {

            $name = $language->getNameFromLocale($lang->getLocale());

            if ($language->getLocale() === $lang->getLocale()) {
                $defaultIndex = $name;
            }

            $dropdownArray[] = $name;
        }

        $form->addDropdown($lang->getMessage(Language::TRANSLATION_REPORT_FORM_DROPDOWN), $dropdownArray, array_search($defaultIndex, $dropdownArray));

        $original = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_LABEL_ORIGINAL);
        $form->addInput($original, "");

        $new = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_LABEL_NEW_TOP) . "\n\n" . $lang->getMessage(Language::TRANSLATION_REPORT_FORM_LABEL_NEW_BOTTOM);
        $form->addInput($new, "");

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @param array|null $players
     * @return null
     *
     * Gets the hacker report form.
     */
    public static function getReportHackerForm(MineceitPlayer $player, array $players = null)
    {

        $server = $player->getServer();

        $onlinePlayers = $server->getOnlinePlayers();

        $players = $players ?? array_diff(array_map(function (Player $player) {
                return $player->getName();
            }, $onlinePlayers), [$player->getName()]);

        $lang = $player->getLanguage();

        if (count($players) <= 0) {
            $message = $lang->getMessage(Language::NO_OTHER_PLAYERS_ONLINE);
            $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
            return null;
        }

        $form = new CustomForm(function (Player $event, $data = null) {


            if($event instanceof MineceitPlayer) {
                $formData = $event->removeFormData();
                $players = $formData['players'];

                if ($data !== null) {

                    $lang = $event->getLanguage();

                    $playerIndex = (int)$data[1];
                    $reason = (string)$data[2];

                    if ($reason === "") {
                        $message = $lang->getMessage(Language::REPORT_NO_REASON);
                        $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                        return;
                    }

                    $resultingStaff = $players[$playerIndex];
                    $server = $event->getServer();

                    $name = (string)$resultingStaff;
                    $report = new HackReport($event, $name, $reason);
                    $reportType = $report->getReportType();

                    if (($player = $server->getPlayer($name)) !== null and $player instanceof MineceitPlayer) {

                        if ($player->hasReport($event, $reportType)) {
                            $message = $lang->getMessage(Language::REPORT_PLAYER_ALREADY);
                            $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                            return;
                        }

                        $player->addReport($report);

                        if ($player->getOnlineReportsCount($reportType) > ReportInfo::MAX_REPORT_NUM) {
                            // TODO BAN THE PLAYER FOR HACKING
                            // $player->kick("Hacking...");
                        }
                    }

                    $reportManager = MineceitCore::getReportManager();
                    $result = $reportManager->createReport($report);

                    if ($result) {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
                    } else {
                        $message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
                    }

                    $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                }
            }
        });

        $lang = $player->getLanguage();

        $title = $lang->getMessage(Language::HACK_REPORT_FORM_TITLE);
        $desc = $lang->getMessage(Language::HACK_REPORT_FORM_DESC);

        $form->setTitle($title);
        $form->addLabel($desc);

        $playersDropdownTitle = $lang->getMessage(Language::PLAYERS_LABEL) . ":";
        if ($lang->getLocale() === Language::ARABIC) {
            $playersDropdownTitle = ":" . $lang->getMessage(Language::PLAYERS_LABEL);
        }

        $form->addDropdown($playersDropdownTitle, $players);

        $form->addInput($lang->getMessage(Language::FORM_LABEL_REASON_FOR_REPORTING), "");

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @param $reports
     *
     * @return SimpleForm
     */
    public
    static function getListOfReports(MineceitPlayer $player, $reports = null): SimpleForm
    {

        $thehistory = $player->getReportPermissions() === 0;

        $form = new SimpleForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();
                /** @var ReportInfo[]|array $reports */
                $reports = $formData['reports'];

                $history = (bool)$formData['history'];

                $perm = (int)$formData['perm'];

                if ($data !== null) {

                    $index = (int)$data;

                    if (count($reports) > 0) {

                        /** @var ReportInfo $report */
                        $report = $reports[$index];

                        $type = $report->getReportType();

                        if ($type === ReportInfo::TYPE_TRANSLATE) {

                            $form = self::getInfoOfReportForm($report, $event);

                            $event->sendFormWindow($form, ['history' => $history, 'perm' => $perm, 'report' => $report]);

                        } else {

                            MineceitCore::getReportManager()->sendTranslatedReport($event, $report, $history);

                        }
                    }
                }
            }

        });

        $lang = $player->getLanguage();

        $history = $reports ?? $player->getReportHistory();

        $timezone = $player->getTimeZone();

        $title = $thehistory ? $lang->getMessage(Language::REPORT_HISTORY_FORM_TITLE) : $lang->getMessage(Language::SEARCH_RESULTS_REPORTS_FORM_TITLE);
        $desc = $thehistory ? $lang->getMessage(Language::REPORT_HISTORY_FORM_DESC) : $lang->getMessage(Language::SEARCH_RESULTS_REPORTS_FORM_DESC);

        $form->setTitle($title);
        $form->setContent($desc);

        if (count($history) <= 0) {
            $none = $lang->getMessage(Language::NONE);
            $form->addButton($none);
            return $form;
        }

        $langFormat = Language::REPORT_HISTORY_FORM_FORMAT;
        $dateFormat = "";
        if (!$thehistory) {
            $langFormat = Language::SEARCH_RESULTS_REPORTS_FORM_FORMAT;
            $dateFormat = '%m%/%d%';
        }

        $author = $lang->getMessage(Language::FORM_LABEL_AUTHOR);

        foreach ($history as $reportInfo) {

            $time = $reportInfo->getTime($timezone, $lang, "", false);
            $date = $reportInfo->getDate($timezone, $lang, $dateFormat);
            $reporter = $reportInfo->getReporter();

            if (strlen($reporter) > 8) {
                $reporter = substr($reporter, 0, 5) . '...';
                if ($lang->getLocale() === Language::ARABIC) {
                    $reporter = '...' . substr($reporter, 0, 5);
                }
            }

            $type = $reportInfo->getReportType(true, $lang);

            $result = $lang->getMessage($langFormat, [
                'type' => $type,
                'name' => $reporter,
                'time' => $time,
                'date' => $date,
                'author' => $author
            ]);

            $form->addButton($result);
        }

        return $form;
    }


    /**
     * @param ReportInfo $info
     * @param MineceitPlayer $player
     * @param array $translatedValues
     * @return CustomForm
     */
    public
    static function getInfoOfReportForm(ReportInfo $info, MineceitPlayer $player, $translatedValues = []): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if($event instanceof MineceitPlayer) {

                $formData = $event->removeFormData();

                $history = (bool)$formData['history'];

                /** @var ReportInfo $report */
                $report = $formData['report'];

                $reports = $history ? array_values($event->getReportHistory()) : null;

                if ($reports !== null) {

                    $form = self::getListOfReports($event, $reports);

                    $event->sendFormWindow($form, ['reports' => $reports, 'history' => $history, 'perm' => $event->getReportPermissions()]);

                } else {

                    $saved = $event->getLastSearchReportHistory();

                    MineceitCore::getReportManager()->searchReports($event, $saved['searched'], $saved['timespan'], $saved['report-data'], $saved['resolved']);
                }

                if ($data !== null) {

                    $resolved = $data[0];

                    if ($resolved !== null) {
                        $report->setResolved(boolval($resolved));
                    }
                }
            }
        });

        $perm = $player->getReportPermissions();

        $lang = $player->getLanguage();

        $title = $info->getReportType(true, $lang);

        $form->setTitle(TextFormat::BOLD . $title);

        $tz = $player->getTimeZone();

        $infoResolved = $info->isResolved();

        $r = $lang->getMessage(Language::FORM_LABEL_RESOLVED);
        $ur = $lang->getMessage(Language::FORM_LABEL_UNRESOLVED);

        $resolved = $infoResolved ? $r : $ur;

        $information = [
            $lang->getMessage(Language::FORM_LABEL_STATUS) => TextFormat::BOLD . $resolved,
            "",
            $lang->getMessage(Language::FORM_LABEL_AUTHOR) => $info->getReporter(),
            "",
            $lang->getMessage(Language::FORM_LABEL_DATE) => $info->getDate($tz, $lang),
            $lang->getMessage(Language::FORM_LABEL_TIME) => $info->getTime($tz, $lang),
            ""
        ];

        if ($perm === ReportInfo::PERMISSION_MANAGE_REPORTS) {

            $information = [
                $lang->getMessage(Language::FORM_LABEL_AUTHOR) => $info->getReporter(),
                "",
                $lang->getMessage(Language::FORM_LABEL_DATE) => $info->getDate($tz, $lang),
                $lang->getMessage(Language::FORM_LABEL_TIME) => $info->getTime($tz, $lang),
                ""
            ];

            // Change the report's status:
            $form->addDropdown($lang->getMessage(Language::REPORT_INFO_FORM_CHANGE_STATUS), [TextFormat::BOLD . $ur, TextFormat::BOLD . $r], intval($infoResolved));
        }

        if ($info instanceof BugReport) {

            $description = $info->getDescription();

            if (isset($translatedValues['desc'])) {
                $description = (string)$translatedValues['desc'];
            }

            $reproduce = $info->getReproduceInfo();
            if (isset($translatedValues['reproduce'])) {
                $reproduce = (string)$translatedValues['reproduce'];
            }

            $information = array_merge($information, [
                $lang->getMessage(Language::FORM_LABEL_BUG) => $description,
                "",
                $lang->getMessage(Language::FORM_LABEL_REPRODUCED) => $reproduce,
                ""
            ]);

        } elseif ($info instanceof HackReport) {

            $reason = $info->getReason();
            if (isset($translatedValues['reason'])) {
                $reason = (string)$translatedValues['reason'];
            }

            $information = array_merge($information, [
                $lang->getMessage(Language::FORM_LABEL_PLAYER) => $info->getReported(),
                "",
                $lang->getMessage(Language::FORM_LABEL_REASON) => $reason,
                ""
            ]);

        } elseif ($info instanceof StaffReport) {

            $reason = $info->getReason();
            if (isset($translatedValues['reason'])) {
                $reason = (string)$translatedValues['reason'];
            }

            $information = array_merge($information, [

                $lang->getMessage(Language::FORM_LABEL_STAFF_MEMBER) => $info->getReported(),
                "",
                $lang->getMessage(Language::FORM_LABEL_REASON) => $reason,
                ""
            ]);

        } elseif ($info instanceof TranslateReport) {
            $information = array_merge($information, [
                $lang->getMessage(Language::FORM_LABEL_LANGUAGE) => $info->getLang(true, $lang),
                "",
                $lang->getMessage(Language::FORM_LABEL_ORIGINAL_MESSAGE) => "",
                $info->getOriginalMessage(),
                "",
                $lang->getMessage(Language::FORM_LABEL_NEW_MESSAGE) => "",
                $info->getNewMessage(),
                ""
            ]);
        }

        $array = [];

        foreach ($information as $key => $value) {

            $resultString = strval($key) . ': ' . strval($value);

            if ($lang->getLocale() === Language::ARABIC) {
                $resultString = strval($value) . ' :' . strval($key);
            }

            if (is_numeric($key)) {
                $resultString = strval($value);
            }

            $array[] = TextFormat::RESET . TextFormat::WHITE . $resultString;
        }

        $result = implode($array, "\n");

        $form->addLabel($result);

        return $form;
    }


    /**
     * @param MineceitPlayer $player
     * @return CustomForm
     *
     * The form that allows the players to toggle the reports.
     */
    public
    static function getViewReportsSearchForm(MineceitPlayer $player): CustomForm
    {

        $form = new CustomForm(function (Player $event, $data = null) {

            if ($event instanceof MineceitPlayer) {

                $event->removeFormData();

                if ($data !== null) {

                    $reportManager = MineceitCore::getReportManager();

                    $nameToSearch = $data[1];

                    $reportTypes = ReportInfo::REPORT_TYPES;

                    $reportData = [];

                    foreach ($reportTypes as $typeInt) {
                        $outputType = $typeInt + 3;
                        $reportData[$typeInt] = $data[$outputType];
                    }

                    $length = count($reportTypes) + 3;

                    $timeSpanOutput = $data[$length];

                    $resolvedOutput = $data[$length + 1];

                    $saved = [
                        "searched" => (string)$nameToSearch,
                        "timespan" => (int)$timeSpanOutput,
                        "report-data" => (array)$reportData,
                        'resolved' => (int)$resolvedOutput
                    ];

                    $event->setReportSearchHistory($saved);

                    // Searches the reports.
                    $reportManager->searchReports($event, $saved['searched'], $saved['timespan'], $saved['report-data'], $saved['resolved']);
                }
            }
        });

        $reportHistory = $player->getLastSearchReportHistory()
            ?? ['searched' => "", "timespan" => 0, 'report-data' => [true, true, true, true], 'resolved' => 0];

        $lang = $player->getLanguage();

        $title = TextFormat::BOLD . $lang->getMessage(Language::FORM_LABEL_REPORTS);

        $desc = $lang->getMessage(Language::SEARCH_REPORTS_FORM_DESC);

        $form->setTitle($title);

        $form->addLabel($desc);

        $searchByName = $lang->getMessage(Language::FORM_LABEL_SEARCH) . ":";
        if ($lang->getLocale() === Language::ARABIC) {
            $searchByName = ":" . $lang->getMessage(Language::FORM_LABEL_SEARCH);
        }

        $searchedDefault = (string)$reportHistory['searched'];
        $form->addInput($searchByName, $searchedDefault);

        $reportType = $lang->getMessage(Language::FORM_LABEL_REPORT_TYPES);
        $searchReportsByType = "{$reportType}:";
        if ($lang->getLocale() === Language::ARABIC) {
            $searchReportsByType = ":{$reportType}";
        }

        $reportTypes = ReportInfo::REPORT_TYPES;

        $form->addLabel($searchReportsByType);

        $defaultType = $reportHistory['report-data'];
        foreach ($reportTypes as $typeInt) {
            $type = ReportInfo::getReportsType($typeInt, $lang);
            $form->addToggle($type, $defaultType[$typeInt]);
        }

        $timespan = $lang->getMessage(Language::FORM_LABEL_TIMESPAN);
        $searchReportsTime = "{$timespan}:";
        if ($lang->getLocale() === Language::ARABIC) {
            $searchReportsTime = ":{$timespan}";
        }

        $options = [
            $lang->getMessage(Language::NONE),
            $lang->getMessage(Language::FORM_LABEL_LAST_HOUR),
            $lang->getMessage(Language::FORM_LABEL_LAST_12_HOURS),
            $lang->getMessage(Language::FORM_LABEL_LAST_24_HOURS),
            $lang->getMessage(Language::FORM_LABEL_LAST_WEEK),
            $lang->getMessage(Language::FORM_LABEL_LAST_MONTH)
        ];

        $timeSpanDefault = (int)$reportHistory['timespan'];
        $form->addDropdown($searchReportsTime, $options, $timeSpanDefault);

        $status = $lang->getMessage(Language::FORM_LABEL_STATUS);
        $statusReports = $lang->getLocale() === Language::ARABIC ? ":{$status}" : "{$status}:";

        $options = [$lang->getMessage(Language::NONE), TextFormat::BOLD . $lang->getMessage(Language::FORM_LABEL_UNRESOLVED), TextFormat::BOLD . $lang->getMessage(Language::FORM_LABEL_RESOLVED)];

        $statusDefault = (int)$reportHistory['resolved'];
        $form->addDropdown($statusReports, $options, $statusDefault);

        return $form;
    }
}