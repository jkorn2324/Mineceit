<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-23
 * Time: 15:37
 */

declare(strict_types=1);

namespace mineceit\player\ranks;


use mineceit\player\particles\MineceitParticle;

class Rank
{

    public const PERMISSION_OWNER = "owner";
    public const PERMISSION_ADMIN = "admin";
    public const PERMISSION_MOD = "mod";
    public const PERMISSION_TRIAL_MOD = "trial-mod";
    public const PERMISSION_CONTENT_CREATOR = "content-creator";
    public const PERMISSION_VIP = "vip";
    public const PERMISSION_VIPPL = "vip+";
    public const PERMISSION_NONE = "none";

    public const PERMISSION_INDEXES = [
        self::PERMISSION_OWNER,
        self::PERMISSION_ADMIN,
        self::PERMISSION_MOD,
        self::PERMISSION_TRIAL_MOD,
        self::PERMISSION_CONTENT_CREATOR,
        self::PERMISSION_VIPPL,
        self::PERMISSION_VIP,
        self::PERMISSION_NONE
    ];

    /* @var string */
    private $name;

    /* @var string */
    private $formattedName;

    /* @var string */
    private $localName;

    /** @var string */
    private $permission;

    /** @var bool */
    private $fly;

    /** @var bool */
    private $placeBreak;

    /** @var MineceitParticle[]|array */
    private $particles;

    /** @var bool */
    private $reserveEvent;

    /** @var bool */
    private $lightningOnKill;

    /** @var bool */
    private $changeCustomTag;

    public function __construct(string $localName, string $name, string $formattedName, string $permission = self::PERMISSION_NONE, bool $fly = false, bool $placeBreak = false, array $particles = [], bool $reserveEvent = false, bool $lightningOnKill = false, bool $changeCustomTag = false)
    {
        $this->localName = $localName;
        $this->name = $name;
        $this->formattedName = $formattedName;
        $this->permission = $permission;
        $this->fly = $fly;
        $this->placeBreak = $placeBreak;
        $this->particles = $particles;
        $this->reserveEvent = $reserveEvent;
        $this->lightningOnKill = $lightningOnKill;
        $this->changeCustomTag = $changeCustomTag;
    }

    /**
     * @return string
     */
    public function getFormat() : string {
        return $this->formattedName;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLocalName() : string {
        return $this->localName;
    }

    /**
     * @return bool
     */
    public function canFly() : bool {
        return $this->fly;
    }

    /**
     * @return bool
     */
    public function canChangeTag() : bool {
        return $this->changeCustomTag;
    }

    /**
     * @return array
     */
    public function encode() : array {

        $particles = [];
        foreach($this->particles as $local => $p) {
            $particles[] = $local;
        }

        return [
            'name' => $this->name,
            'format' => $this->formattedName,
            'permission' => $this->permission,
            'fly' => $this->fly,
            'place-break' => $this->placeBreak,
            'reserve-event' => $this->reserveEvent,
            'lightning-kill' => $this->lightningOnKill,
            'tag' => $this->changeCustomTag,
            'particles' => $particles
        ];
    }

    /**
     * @return string
     */
    public function getPermission() : string {
        return $this->permission;
    }

    /**
     * @return bool
     */
    public function canEditWorld() : bool {
        return $this->placeBreak;
    }

    /**
     * @return bool
     */
    public function activateLightningOnKill() : bool {
        return $this->lightningOnKill;
    }


    /**
     * @return bool
     */
    public function reserveSpotInEvent() : bool {
        return $this->reserveEvent;
    }

    /**
     * @param string $localName
     * @param array $array
     * @return Rank|null
     */
    public static function parseRank(string $localName, $array) {

        $result = null;

        if(isset($array['name'], $array['format'])) {

            $name = (string)$array['name'];
            $format = (string)$array['format'];

            $permission = Rank::PERMISSION_NONE;
            if (isset($array['permission'])) {
                $permission = $array['permission'];
            }

            $fly = $permission === Rank::PERMISSION_ADMIN or $permission === Rank::PERMISSION_OWNER or $permission === self::PERMISSION_CONTENT_CREATOR or $permission === self::PERMISSION_VIPPL;
            if (isset($array['fly'])) {
                $fly = $array['fly'];
            }

            $placeBreak = $permission === Rank::PERMISSION_OWNER or $permission === Rank::PERMISSION_ADMIN;
            if(isset($array['place-break'])) {
                $placeBreak = (bool)$array['place-break'];
            }

            $lightning = $permission !== Rank::PERMISSION_NONE and $permission !== Rank::PERMISSION_TRIAL_MOD and $permission !== Rank::PERMISSION_CONTENT_CREATOR;
            if(isset($array['lightning-kill'])) {
                $lightning = (bool)$array['lightning-kill'];
            }

            $reserveEvent = $permission !== Rank::PERMISSION_NONE and $permission !== Rank::PERMISSION_TRIAL_MOD;
            if(isset($array['reserve-event'])) {
                $reserveEvent = (bool)$array['reserve-event'];
            }

            $changeTag = $permission === self::PERMISSION_MOD and $permission === self::PERMISSION_ADMIN and $permission === self::PERMISSION_OWNER;
            if(isset($array['tag'])) {
                $changeTag = (bool)$array['tag'];
            }

            $rankParticles = [];
            if(isset($array['particles'])) {
                $particles = $array['particles'];
                if(is_array($particles) and count($particles) > 0) {
                    $particleHandler = RankHandler::getParticlesHandler();
                    foreach ($particles as $particle) {
                        $p = $particleHandler->getParticle($particle);
                        if ($p !== null) {
                            $rankParticles[$p->getLocalName()] = $p;
                        }
                    }
                }
            }
            $result = new Rank($localName, $name, $format, $permission, $fly, $placeBreak, $rankParticles, $reserveEvent, $lightning, $changeTag);
        }
        return $result;
    }
}