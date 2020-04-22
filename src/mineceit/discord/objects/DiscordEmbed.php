<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-21
 * Time: 10:21
 */

namespace mineceit\discord\objects;


use mineceit\discord\DiscordUtil;

class DiscordEmbed
{

    /** @var string */
    private $username = "Mineceit";

    /** @var string */
    private $avatar = DiscordUtil::MINECEIT_LOGO;

    /** @var bool => Text To Speech */
    private $tts;

    /** @var array */
    private $title;

    /** @var string */
    private $description;

    /** @var string */
    private $color;

    /** @var string */
    private $timestamp;

    /** @var array|null */
    private $footer = null;

    public function __construct(string $title, string $description, string $color = DiscordUtil::BLUE, bool $tts = false)
    {
        $this->title = $title;
        $this->tts = $tts;
        $this->description = $description;
        $this->color = $color;
        $this->timestamp = date(\DateTime::ISO8601);
    }

    /**
     * @param array $footer
     *
     * Sets the footer.
     */
    public function setFooter(array $footer) : void {
        $this->footer = $footer;
    }

    /**
     *
     * @param bool
     *
     * @return array
     *
     * Encodes the embed.
     */
    public function encode(bool $boldTitle = false) : array {

        $title = $boldTitle ? DiscordUtil::boldText($this->title) : $this->title;

        $embeds = [
            "title" => $title,
            "description" => $this->description,
            "type" => "rich",
            "timestamp" => $this->timestamp,
            "color" => hexdec($this->color),
        ];

        if($this->footer !== null) {
            $embeds["footer"] = $this->footer;
        }

        return [
            //"content" => "",
            "username" => $this->username,
            "avatar_url" => $this->avatar,
            "tts" => $this->tts,
            "embeds" => [$embeds],
        ];
    }
}