<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-04
 * Time: 12:08
 */

declare(strict_types=1);

namespace mineceit\maitenance\reports\data;


use mineceit\MineceitCore;
use mineceit\player\language\Language;

class TranslateReport extends ReportInfo
{

    /** @var string */
    private $language;

    /** @var string */
    private $originalMessage;

    /** @var string */
    private $newMessage;

    /**
     * TranslateReport constructor.
     * @param $reporter
     * @param Language|string $language
     * @param string $originalMessage
     * @param string $newMessage
     * @param int|null $time
     * @param bool $resolved
     */
    public function __construct($reporter, $language, string $originalMessage, string $newMessage, ?int $time = null, bool $resolved = false)
    {
        parent::__construct(self::TYPE_TRANSLATE, $reporter, $time, $resolved);
        $this->originalMessage = $originalMessage;
        $this->newMessage = $newMessage;
        $this->language = $language instanceof Language ? $language->getLocale() : $language;
    }

    /**
     * @param bool $string
     * @param Language|null $lang
     * @return Language|string
     *
     * Gets the language of the report.
     */
    public function getLang(bool $string = false, Language $lang = null) {

        $playerManager = MineceitCore::getPlayerHandler();
        $lang = $lang ?? $playerManager->getLanguage();

        $theLanguage = $playerManager->getLanguage($this->language);

        return $string ? $theLanguage->getNameFromLocale($lang->getLocale()) : $theLanguage;
    }

    /**
     * @return string
     *
     * Gets the original message.
     */
    public function getOriginalMessage() : string {
        return $this->originalMessage;
    }

    /**
     * @return string
     *
     * Gets the new message.
     */
    public function getNewMessage() : string {
        return $this->newMessage;
    }

    /**
     * @return array
     *
     * Encodes the data to a csv format.
     */
    public function csv_encode(): array
    {
        return [
            $this->type,
            $this->reporter,
            $this->language,
            $this->originalMessage,
            $this->newMessage,
            $this->time,
            $this->resolved
        ];
    }

    /**
     * @return array
     *
     * Serializes so that we can use it to compare.
     */
    public function serialize(): array
    {
        return [
            'timestamp' => $this->getTimestamp(),
            'reporter' => $this->getReporter(),
            'lang' => array_merge([$this->language], $this->getLang()->getNames()),
            'type' => $this->type,
            'local' => $this->getLocalName(),
            'resolved' => $this->resolved
        ];
    }
}