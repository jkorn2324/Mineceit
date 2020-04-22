<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-08
 * Time: 17:32
 */

declare(strict_types=1);

namespace mineceit;


use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\discord\DiscordUtil;
use mineceit\fixes\player\PMPlayer;
use mineceit\game\level\AsyncDeleteLevel;
use mineceit\game\level\MineceitChunkLoader;
use mineceit\game\entities\ReplayHuman;
use mineceit\game\entities\ReplayItemEntity;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ProjectileItem;
use pocketmine\item\SplashPotion;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\DesktopPlayer;

class MineceitUtil
{

    // TIME FUNCTIONS
    const ONE_MONTH_SECONDS = 2628000;
    const ONE_WEEK_SECONDS = 604800;

    const MINECEIT_SCRIPT_PKT_NAME = "mineceit:packet";

    const CLASSIC_DUEL_GEN = "duel_classic";
    const CLASSIC_SUMO_GEN = "sumo_classic";
    const CLASSIC_SPLEEF_GEN = "spleef_classic";

    const RIVER_DUEL_GEN = "duel_river";
    const BURNT_DUEL_GEN = "duel_burnt";

    /**
     * @param int $secs
     * @return int
     */
    public static function secondsToTicks(int $secs) : int {
        return $secs * 20;
    }

    /**
     * @param int $mins
     * @return int
     */
    public static function minutesToTicks(int $mins) : int {
        return $mins * 1200;
    }

    /**
     * @param int $hours
     * @return int
     */
    public static function hoursToTicks(int $hours) : int {
        return $hours * 72000;
    }

    /**
     * @param int $ticks
     * @return int
     */
    public static function ticksToMinutes(int $ticks) : int {
        return intval($ticks / 1200);
    }

    /**
     * @param int $ticks
     * @return int
     */
    public static function ticksToSeconds(int $ticks) : int {
        return intval($ticks / 20);
    }

    /**
     * @param int $ticks
     * @return int
     */
    public static function ticksToHours(int $ticks) : int {
        return intval($ticks / 72000);
    }

    // MESSAGE FUNCTIONS

    public static function getPrefix() : string {
        $serverName = self::getServerName(true);
        return TextFormat::BOLD . TextFormat::DARK_GRAY . '[' . $serverName . TextFormat::DARK_GRAY . ']';
    }

    /**
     * @param string $message
     */
    public static function broadcastMessage($message) : void {
        $server = Server::getInstance();
        $server->broadcastMessage(self::getPrefix() . ' ' . TextFormat::RESET . $message);
    }

    /**
     * @param bool $bold
     * @return string
     */
    public static function getServerName(bool $bold = false) : string {
        return self::getThemeColor() . ($bold === true ? TextFormat::BOLD : '') . 'Mineceit';
    }

    /**
     * @return string
     */
    public static function getThemeColor() : string {
        return TextFormat::GOLD;
    }


    /**
     * @param array|string[] $excludedColors
     * @return string
     */
    public static function randomColor($excludedColors = []) : string
    {
        $array = [
            TextFormat::DARK_PURPLE => true,
            TextFormat::GOLD => true,
            TextFormat::RED => true,
            TextFormat::GREEN => true,
            TextFormat::LIGHT_PURPLE => true,
            TextFormat::AQUA => true,
            TextFormat::DARK_RED => true,
            TextFormat::DARK_AQUA => true,
            TextFormat::BLUE => true,
            TextFormat::GRAY => true,
            TextFormat::DARK_GREEN => true
        ];

        foreach($excludedColors as $c) {
            if (isset($array[$c]))
                unset($array[$c]);
        }

        $size = count($array) - 1;

        $keys = array_keys($array);

        return (string)$keys[mt_rand(0, $size)];
    }

    // ITEM FUNCTIONS

    /**
     * @param int $id
     * @param int $meta
     * @param int $count
     * @param array|EnchantmentInstance[] $enchants
     * @return Item
     */
    public static function createItem(int $id, int $meta = 0, $count = 1, $enchants = []) : Item {

        $item = Item::get($id, $meta, $count);

        foreach($enchants as $e)
            $item->addEnchantment($e);

        return $item;
    }

    // POSITION FUNCTIONS

    /**
     * @param Location|Position|Vector3 $pos
     * @return array
     */
    public static function posToArray($pos) : array {
        return [
            'x' => (int)$pos->x,
            'y' => (int)$pos->y,
            'z' => (int)$pos->z
        ];
    }

    /**
     * @param Vector3 $vec3
     * @param Level|null $level
     * @return Position
     */
    public static function toPosition(Vector3 $vec3, Level $level = null) : Position {
        return new Position($vec3->x, $vec3->y, $vec3->z, $level);
    }

    // LEVEL FUNCTIONS

    /**
     * @param MineceitCore $core
     * @return array|string[]
     */
    public static function getLevelsFromFolder(MineceitCore $core) {

        $dataFolder = $core->getDataFolder();

        $index = strpos($dataFolder, '/plugin_data');

        $substr = substr($dataFolder, 0, $index);

        $worlds = $substr . "/worlds";

        if(!is_dir($worlds))
            return [];

        $files = scandir($worlds);

        return $files;
    }

    /**
     * @param Level $level
     * @param int $x
     * @param int $z
     * @param callable $callable
     */
    public static function onChunkGenerated(Level $level, int $x, int $z, callable $callable) : void {

        if($level->isChunkPopulated($x, $z)) {
            ($callable)();
            return;
        }
        $level->registerChunkLoader(new MineceitChunkLoader($level, $x, $z, $callable), $x, $z, true);
    }

    /**
     * @param string|Level $level
     */
    public static function deleteLevel($level) : void {

        $server = Server::getInstance();

        if (is_string($level)) {

            $path = $server->getDataPath() . "worlds/" . $level;

            $server->getAsyncPool()->submitTask(new AsyncDeleteLevel($path));

        } elseif ($level instanceof Level) {

            $server->unloadLevel($level);

            $path = Server::getInstance()->getDataPath() . 'worlds/' . $level->getFolderName();

            $server->getAsyncPool()->submitTask(new AsyncDeleteLevel($path));
        }
    }


    // MISC FUNCTIONS


    /**
     * @param MineceitPlayer...$players
     */
    public static function updateHiddenPlayers(MineceitPlayer...$players) : void {
        // TODO UPDATE
    }


    /**
     * @param PlayerReplayData $data
     * @return CompoundTag
     *
     * Generate nbt for the replay human.
     */
    public static function getHumanNBT(PlayerReplayData $data) : CompoundTag {

        $startPos = $data->getStartPosition();
        $startRot = $data->getStartRotation();
        //$invTag = $data->getStartInventoryTag();
        $startNameTag = $data->getStartTag();
        //assert($invTag !== null);
        $skin = $data->getSkin();

        $nbt = Entity::createBaseNBT($startPos, null, $startRot['yaw'], $startRot['pitch']);
        $nbt->setShort("Health", 20);
        $nbt->setString("CustomName", $startNameTag);

        $tag = new CompoundTag("Skin", [
            new StringTag("Name", $skin->getSkinId()),
            new ByteArrayTag("Data", $skin->getSkinData()),
            new ByteArrayTag("CapeData", $skin->getCapeData()),
            new StringTag("GeometryName", $skin->getGeometryName()),
            new ByteArrayTag("GeometryData", $skin->getGeometryData())
        ]);

        $nbt->setTag($tag);

        return $nbt;
    }


    /**
     * @param ProjectileItem $item
     * @param ReplayHuman $player
     * @param Vector3 $directionVector
     * @return bool
     *
     * Used for humans throwing items.
     */
    public static function onClickAir(ProjectileItem $item, ReplayHuman $player, Vector3 $directionVector) : bool {

        $nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
        if ($item instanceof SplashPotion)
            $nbt->setShort("PotionId", $item->getDamage());

        $projectile = Entity::createEntity($item->getProjectileEntityType(), $player->getLevel(), $nbt, $player);

        if($projectile !== null)
            $projectile->setMotion($projectile->getMotion()->multiply($item->getThrowForce()));

        $inv = $player->getInventory();
        $index = $inv->getHeldItemIndex();
        $count = $item->getCount();
        if($count > 1) $inv->setItem($index, Item::get($item->getId(), $item->getDamage(), $count));
        else $inv->setItem($index, Item::get(0));

        if($projectile instanceof Projectile) {
            $projectile->spawnToAll();
            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
        } else if ($projectile !== null)
            $projectile->spawnToAll();
        else return false;

        return true;
    }


    /**
     * @param ReplayHuman $human
     * @param bool $fishing
     */
    public static function useRod(ReplayHuman $human, bool $fishing) {

        if(!$fishing and $human->isFishing()) {
            $human->stopFishing();
        } elseif ($fishing and !$human->isFishing()) {
            $human->startFishing();
        }
    }

    /**
     * @param Level $level
     * @param Vector3 $source
     * @param Item $item
     * @param Vector3 $motion
     * @param int $droppedTick
     * @param ReplayHuman|null $pickup
     * @param int $pickupTime
     *
     * @return ReplayItemEntity|null
     */
    public static function dropItem(Level $level, Vector3 $source, Item $item, Vector3 $motion = null, int $droppedTick = null, ReplayHuman $pickup = null, int $pickupTime = null){

        $motion = $motion ?? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1);
        $itemTag = $item->nbtSerialize();
        $itemTag->setName("Item");

        if(!$item->isNull()){
            $nbt = Entity::createBaseNBT($source, $motion, lcg_value() * 360, 0);
            $nbt->setShort("Health", 5);
            $nbt->setShort("PickupDelay", 40);
            $nbt->setTag($itemTag);
            $itemEntity = Entity::createEntity("ReplayItem", $level, $nbt);
            if($itemEntity instanceof ReplayItemEntity){
                if($pickupTime !== null) $itemEntity->setPickupTick($pickupTime);
                if($pickup !== null) $itemEntity->setHumanPickup($pickup);
                if($droppedTick !== null) $itemEntity->setDroppedTick($droppedTick);
                $itemEntity->spawnToAll();
                return $itemEntity;
            }
        }
        return null;
    }


    /**
     * Returns the vanilla death message for the given death cause.
     * Copied from PlayerDeathEvent
     *
     * @param string                 $name
     * @param null|EntityDamageEvent $deathCause
     *
     * @return TranslationContainer
     */
    public static function getDeathMessage(string $name, ?EntityDamageEvent $deathCause) : TranslationContainer{

        $message = "death.attack.generic";
        $params = [$name];

        switch($deathCause === null ? EntityDamageEvent::CAUSE_CUSTOM : $deathCause->getCause()){
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                if($deathCause instanceof EntityDamageByEntityEvent){
                    $e = $deathCause->getDamager();
                    if($e instanceof Player){
                        $message = "death.attack.player";
                        $params[] = $e->getDisplayName();
                        break;
                    }elseif($e instanceof Living){
                        $message = "death.attack.mob";
                        $params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
                        break;
                    }else{
                        $params[] = "Unknown";
                    }
                }
                break;
            case EntityDamageEvent::CAUSE_PROJECTILE:
                if($deathCause instanceof EntityDamageByEntityEvent){
                    $e = $deathCause->getDamager();
                    if($e instanceof Player){
                        $message = "death.attack.arrow";
                        $params[] = $e->getDisplayName();
                    }elseif($e instanceof Living){
                        $message = "death.attack.arrow";
                        $params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
                        break;
                    }else{
                        $params[] = "Unknown";
                    }
                }
                break;
            case EntityDamageEvent::CAUSE_SUICIDE:
                $message = "death.attack.generic";
                break;
            case EntityDamageEvent::CAUSE_VOID:
                $message = "death.attack.outOfWorld";
                break;
            case EntityDamageEvent::CAUSE_FALL:
                if($deathCause instanceof EntityDamageEvent){
                    if($deathCause->getFinalDamage() > 2){
                        $message = "death.fell.accident.generic";
                        break;
                    }
                }
                $message = "death.attack.fall";
                break;

            case EntityDamageEvent::CAUSE_SUFFOCATION:
                $message = "death.attack.inWall";
                break;

            case EntityDamageEvent::CAUSE_LAVA:
                $message = "death.attack.lava";
                break;

            case EntityDamageEvent::CAUSE_FIRE:
                $message = "death.attack.onFire";
                break;

            case EntityDamageEvent::CAUSE_FIRE_TICK:
                $message = "death.attack.inFire";
                break;

            case EntityDamageEvent::CAUSE_DROWNING:
                $message = "death.attack.drown";
                break;

            case EntityDamageEvent::CAUSE_CONTACT:
                if($deathCause instanceof EntityDamageByBlockEvent){
                    if($deathCause->getDamager()->getId() === Block::CACTUS){
                        $message = "death.attack.cactus";
                    }
                }
                break;

            case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
            case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                if($deathCause instanceof EntityDamageByEntityEvent){
                    $e = $deathCause->getDamager();
                    if($e instanceof Player){
                        $message = "death.attack.explosion.player";
                        $params[] = $e->getDisplayName();
                    }elseif($e instanceof Living){
                        $message = "death.attack.explosion.player";
                        $params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
                        break;
                    }
                }else{
                    $message = "death.attack.explosion";
                }
                break;

            case EntityDamageEvent::CAUSE_MAGIC:
                $message = "death.attack.magic";
                break;

            case EntityDamageEvent::CAUSE_CUSTOM:
                break;

            default:
                break;
        }

        return new TranslationContainer($message, $params);
    }


    /**
     * @param MineceitPlayer $player
     * @param bool $update
     * @return MysqlStream
     *
     * Gets the default mysql stream that will be sent when saving/loading data.
     */
    public static function getMysqlStream(MineceitPlayer $player, bool $update = false) : MysqlStream {

        $mysqlStream = new MysqlStream();

        $lang = !$update ? Language::ENGLISH_US : $player->getLanguage()->getLocale();
        // $placeNBreak = !$update ? $player->isOp() : $player->getPermission(MineceitPlayer::PERMISSION_BUILDER_MODE);
        // $tag = !$update ? $player->isOp() : $player->getPermission(MineceitPlayer::PERMISSION_TAG);

        $playerSettings = new MysqlRow("PlayerSettings");
        $playerSettings->put("username", $player->getName());
        $playerSettings->put("language", $lang);

        $playerStats = new MysqlRow("PlayerStats");
        $playerStats->put("username", $player->getName());

        $ranks = new MysqlRow("PlayerRanks");
        $ranks->put("username", $player->getName());

        $elo = new MysqlRow("PlayerElo");
        $elo->put("username", $player->getName());

        $statement = "username = '{$player->getName()}'";

        if($update) {

            $playerSettings->put("muted", $player->isMuted());
            $playerSettings->put("scoreboardEnabled", $player->isScoreboardEnabled());
            $playerSettings->put("placeBreak", $player->isBuilderModeEnabled());
            $playerSettings->put("peOnly", $player->isPeOnly());
            $playerSettings->put("particles", $player->isParticlesEnabled());
            $playerSettings->put("tag", $player->getCustomTag());
            $playerSettings->put("translate", $player->doesTranslateMessages());
            $playerSettings->put("swishSound", $player->isSwishEnabled());
            $playerSettings->put("changeTag", $player->canChangeTag());
            $playerSettings->put("lightningEnabled", $player->lightningEnabled());
            /* $playerSettings->put("lastTimePlayed", time());
            $playerSettings->put("limitedFeatures", 0);  */

            $playerStats->put("kills", $player->getKills());
            $playerStats->put("deaths", $player->getDeaths());

            $playerRanks = $player->getRanks(true);

            $length = count($playerRanks);

            $count = 0;

            while($count < $length) {
                $index = $count + 1;
                $key = "rank{$index}";
                $value = true; // TODO FIX
                $ranks->put($key, $value);
                $count++;
            }

            $playerElo = $player->getElo();

            $keys = array_keys($playerElo);

            foreach($keys as $key) {
                $value = $playerElo[$key];
                $elo->put($key, $value);
            }

            $mysqlStream->updateRow($playerSettings, [$statement]);
            $mysqlStream->updateRow($playerStats, [$statement]);
            $mysqlStream->updateRow($ranks, [$statement]);
            $mysqlStream->updateRow($elo, [$statement]);

        } else {

            $mysqlStream->insertRow($playerSettings, [$statement]);
            $mysqlStream->insertRow($playerStats, [$statement]);
            // $mysqlStream->insertRow($permissions, [$statement]);
            $mysqlStream->insertRow($ranks, [$statement]);
            $mysqlStream->insertRow($elo, [$statement]);

        }

        return $mysqlStream;
    }


    /**
     * @param string $name
     * @param bool $displayName
     *
     * @return Player|null
     */
    public static function getPlayer(string $name, bool $displayName = false){

        $found = null;
        $name = strtolower($name);
        $delta = PHP_INT_MAX;
        $server = Server::getInstance();

        foreach($server->getOnlinePlayers() as $player){

            if(stripos($player->getDisplayName(), $name) === 0){
                $curDelta = strlen($player->getDisplayName()) - strlen($name);
                if($curDelta < $delta){
                    $found = $player;
                    $delta = $curDelta;
                }
                if($curDelta === 0){
                    break;
                }
            }
        }

        return $found;
    }


    /**
     *
     * @param string $name
     * @param bool $displayName
     *
     * @return Player|null
     */
    public static function getPlayerExact(string $name, bool $displayName = false){
        $name = strtolower($name);
        $server = Server::getInstance();
        foreach($server->getOnlinePlayers() as $player){
            $str = ($displayName ? strtolower($player->getDisplayName()) : $player->getLowerCaseName());
            if($str === $name){
                return $player;
            }
        }

        return null;
    }


    /**
     * @param string $permissions
     *
     * Gets recipients based on the permissions. -> Taken from Server in BroadcastMessage function.
     *
     * @return CommandSender[]
     */
    public static function getRecipients(string $permissions = Server::BROADCAST_CHANNEL_USERS) {

        /** @var CommandSender[] $recipients */
        $recipients = [];
        foreach(explode(";", $permissions) as $permission){
            foreach(PermissionManager::getInstance()->getPermissionSubscriptions($permission) as $permissible){
                if($permissible instanceof CommandSender and $permissible->hasPermission($permission)){
                    $recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
                }
            }
        }

        return $recipients;
    }


    /**
     * @param CommandSender $playerSent
     * @param string $format
     * @param string|TranslationContainer $message
     * @param array|CommandSender[]|null
     * @param int $indexMsg
     *
     * Broadcasts a message to each player. -> used for translating messages.
     */
    public static function broadcastTranslatedMessage(CommandSender $playerSent, string $format, $message, array $recipients = null, int $indexMsg = 0) : void {

        /** @var CommandSender[] $recipients */
        $recipients = $recipients ?? self::getRecipients();

        $lang = $playerSent instanceof MineceitPlayer ? $playerSent->getLanguage() : MineceitCore::getPlayerHandler()->getLanguage();

        foreach($recipients as $sender) {

            if ($sender instanceof MineceitPlayer and $sender->doesTranslateMessages() and !$sender->equalsPlayer($playerSent)) {

                TranslateUtil::client5_translate($message, $sender, $lang, $format, $indexMsg);

            } else {

                if($message instanceof TranslationContainer) {

                    $text = $message->getText();
                    $parameters = $message->getParameters();
                    $parameters[$indexMsg] = $format . $parameters[$indexMsg];
                    $message = new TranslationContainer($text, $parameters);
                    $sender->sendMessage($message);

                } else {

                    $sender->sendMessage($format . $message);
                }
            }
        }
    }


    /**
     * @param MineceitPlayer $player
     * @return string
     *
     * The join message.
     */
    public static function getJoinMessage(MineceitPlayer $player) : string {
        $name = $player->getDisplayName();
        $prefix = TextFormat::DARK_GRAY . '[' . TextFormat::GREEN . '+1' . TextFormat::DARK_GRAY . ']';
        return $prefix . TextFormat::GREEN . " {$name}";
    }

    /**
     * @param MineceitPlayer $player
     * @return string
     *
     * The join message.
     */
    public static function getLeaveMessage(MineceitPlayer $player) : string {
        $name = $player->getDisplayName();
        $prefix = TextFormat::DARK_GRAY . '[' . TextFormat::RED . '-1' . TextFormat::DARK_GRAY . ']';
        return $prefix . TextFormat::RED . " {$name}";
    }


    /**
     * @param $num
     * @param Language $lang
     * @return string
     *
     * Gets the ordinal for the number.
     */
    public static function getOrdinalPostfix($num, Language $lang = null) : string {

        if(!is_numeric($num)) {
            return "";
        }

        $num = strval($num);
        $length = strlen($num);
        if($length <= 0) {
            return "";
        }

        $lang = $lang ?? MineceitCore::getPlayerHandler()->getLanguage();

        $lastNum = intval($num);
        if($lang->doesShortenOrdinals()) {
            $lastNum = intval($num[$length - 1]);
        }

        return $lang->getOrdinalOf($lastNum);
    }


    /**
     * @param string $string
     * @return int
     *
     * Gets the word count of a string.
     */
    public static function getWordCount(string $string) : int {

        if($string === "") {
            return 0;
        }

        $exploded = explode(" ", trim($string));
        $count = 0;
        foreach($exploded as $word) {
            if($word !== "") {
                $count++;
            }
        }
        return $count;
    }


    /**
     * @param MineceitPlayer|PMPlayer|ReplayHuman $player
     * @param DataPacket $packet
     * @param callable $function
     * @param MineceitPlayer[]|array|null $viewers
     *
     * Broadcasts the swish sound.
     */
    public static function broadcastDataPacket($player, DataPacket $packet, callable $function, array $viewers = null) : void {

        $position = $player->asVector3();
        $level = $player->getLevel();

        /** @var Player[] $chunkPlayers */
        $chunkPlayers = $viewers ?? $level->getViewersForPosition($position);

        foreach($chunkPlayers as $key => $player) {
            if($player instanceof MineceitPlayer) {
                if ($player->isOnline() and $function($player)) {
                    $player->batchDataPacket($packet);
                }
            } elseif ($player instanceof DesktopPlayer) {
                if($player->isOnline()) {
                    $player->batchDataPacket($packet);
                }
            }
        }
    }


    /**
     * @param string $messageLocal
     *
     * Broadcasts a notice to all of the staff members.
     *
     * TODO IMPLEMENT THIS WHEN ANOTHER REPORT OCCURS
     */
    public static function broadcastNoticeToStaff(string $messageLocal) : void {

        $server = Server::getInstance();
        $players = $server->getOnlinePlayers();

        foreach($players as $player) {
            if($player instanceof MineceitPlayer) {
                if ($player->isOnline() and $player->hasTrialModPermissions()) {
                    $lang = $player->getLanguage();
                    $player->sendPopup($lang->getMessage($messageLocal));
                }
            }
        }
    }


    /**
     * @param MineceitPlayer $player
     *
     * Sends the arrow ding sound to a specific player.
     */
    public static function sendArrowDingSound(MineceitPlayer $player) : void {

        $packet = new LevelEventPacket();
        $packet->evid = LevelEventPacket::EVENT_SOUND_ORB;
        $packet->data = 1;
        $packet->position = $player->asVector3();

        $player->batchDataPacket($packet);
    }

    /**
     * @param MineceitPlayer|Location $pos
     * @param bool $isVip
     *
     * Spawns the lightning bolt at a given position.
     */
    public static function spawnLightningBolt($pos, bool $isVip = true) : void {

        $level = $pos->getLevel();
        if($level === null) {
            return;
        }

        $players = $level->getPlayers();
        if($isVip) {
            $broadcastedPlayers = [];
            foreach ($players as $player) {
                if($player instanceof MineceitPlayer) {
                    if ($player->getPermission(MineceitPlayer::PERMISSION_LIGHTNING_ON_DEATH) && $player->lightningEnabled()) {
                        $broadcastedPlayers[] = $player;
                    }
                }
            }
        } else {
            $broadcastedPlayers = $players;
        }

        $packet = new AddActorPacket();
        $packet->type = Entity::LIGHTNING_BOLT;
        $packet->entityRuntimeId = Entity::$entityCount++;
        $packet->metadata = [];
        $packet->position = $pos->asVector3();
        $packet->yaw = $pos->getYaw();
        $packet->pitch = $pos->getPitch();

        $level->getServer()->broadcastPacket($broadcastedPlayers, $packet);
    }



    /**
     * @param int|float $pos
     * @param int|float $max
     * @param int|float $min
     * @return bool
     *
     * Determines whether the player is within a set of bounds.
     */
    public static function isWithinBounds($pos, $max, $min) : bool {
        return $pos <= $max and $pos >= $min;
    }


    /**
     * @param MineceitPlayer $player
     * @param string $detector
     * @param string $discordReason
     * @param string $mcReason
     *
     * Bans the player automatically.
     *
     * // TODO FIX
     */
    public static function autoBan(MineceitPlayer $player, string $detector = "", string $discordReason = "", string $mcReason = "") : void {

        $ip = $player->getAddress();
        $name = $player->getName();

        $mcReason = $mcReason !== "" ? $mcReason : "Unfair advantage.";

        $server = $player->getServer();

        $type = "Kicked";

        // $server->getIPBans()->addBan($ip, $mcReason);
        // 259200 = 3 Days
        /* $threeDays = time() + 259200;

        $date = date_create($threeDays);
        $server->getNameBans()->addBan($name, $mcReason, $date); */

        $title = DiscordUtil::boldText($detector);

        $discordReason = $discordReason !== "" ? $discordReason : "Autoclicker detected.";

        $description = "  " . DiscordUtil::boldText("{$type}:") . " {$name}\n " . DiscordUtil::boldText("Reason:") . " {$discordReason}";

        DiscordUtil::sendLog($title, $description, DiscordUtil::DARK_RED);

        $reason = "{$type}: {$name}\nReason: {$mcReason}";

        $player->kick($reason, false);
        // $server->getNetwork()->blockAddress($ip);

        $recipients = self::getRecipients(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
        $detector = strtoupper($detector);

        $message = TextFormat::ITALIC . TextFormat::GRAY . "[{$detector}: {$type} player {$name}. Reason: {$mcReason}]";

        foreach($recipients as $recipient) {
            $recipient->sendMessage($message);
        }
    }


    /**
     * @param string $uuid
     * @return Player|null
     *
     * Gets the player based on their uuid.
     */
    public static function getPlayerFromUUID(string $uuid) {

        $server = Server::getInstance();
        /* var_dump($uuid);
        var_dump("original"); */
        $players = $server->getOnlinePlayers();
        foreach($players as $player) {
            $pUUID = $player->getUniqueId()->toString();
            if($pUUID === $uuid) {
                return $player;
            }
        }

        return null;
    }


    /**
     * @param bool $fps
     * @return string
     *
     * Provides a random duel arena determining whether it is fps or not.
     */
    public static function randomizeDuelArenas(bool $fps = false) : string {

        $genManager = MineceitCore::getGeneratorManager();

        $arenas = array_keys($genManager->listGenerators($fps, WorldReplayData::TYPE_DUEL));

        return $arenas[mt_rand(0, count($arenas) - 1)];
    }


    /**
     * @param bool $fps
     * @return string
     *
     * Provides a random sumo arena determined whether it is fps or not.
     */
    public static function randomizeSumoArenas(bool $fps = false) : string {

        $generatorManager = MineceitCore::getGeneratorManager();

        $arenas = array_keys($generatorManager->listGenerators($fps, WorldReplayData::TYPE_SUMO));

        return $arenas[mt_rand(0, count($arenas) - 1)];
    }


    /**
     * @param array $data
     * @return ScriptCustomEventPacket
     *
     * Constructs a custom packet.
     */
    public static function constructCustomPkt(array $data) : ScriptCustomEventPacket {

        $pk = new ScriptCustomEventPacket();
        $pk->eventName = self::MINECEIT_SCRIPT_PKT_NAME;
        $buffer = "";
        foreach($data as $key => $value) {
            if(is_string($value)) {
                $strlen = strlen($value);
                $buffer .= Binary::writeShort($strlen);
                $buffer .= $value;
            } elseif (is_bool($value)) {
                $buffer .= ($value ? "\x01" : "\x00");
            }
        }
        $pk->eventData = $buffer;

        return $pk;
    }
}
