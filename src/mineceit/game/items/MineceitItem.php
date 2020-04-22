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
use mineceit\player\language\Language;
use pocketmine\item\Item;

class MineceitItem
{

    /* @var string */
    private $item;

    /* @var string */
    private $localName;

    /* @var string[]|array */
    private $names;

    /* @var int */
    private $slot;

    /**
     * MineceitItem constructor.
     * @param int $slot
     * @param string $localName
     * @param Item $item
     */
    public function __construct(int $slot, string $localName, Item $item)
    {
        $this->slot = $slot;
        $this->localName = $localName;
        $this->item = $item;
        $this->loadNames();
    }


    private function loadNames() : void {

        $languages = MineceitCore::getPlayerHandler()->getLanguages();

        $this->names = [];

        foreach($languages as $locale => $language) {

            $name = $language->getItemName($this->localName);

            // Lets me know that I need to add those items.
            if($name === null) {
                $name = "{$this->localName}:{$locale}";
            }

            $this->names[$locale] = $name;
        }
    }

    /**
     * @return Item
     */
    public function getItem() : Item {
        return $this->item;
    }

    /**
     * @return string
     */
    public function getLocalName() : string {
        return $this->localName;
    }

    /**
     * @return int
     */
    public function getSlot() : int {
        return $this->slot;
    }

    /**
     * @param string|Language $lang
     * @return string
     */
    public function getName($lang) : string {

        $locale = $lang instanceof Language ? $lang->getLocale() : $lang;

        $name = $this->names[Language::ENGLISH_US];
        if(isset($this->names[$locale]))
            $name = $this->names[$locale];

        return $name;
    }


    /**
     * @param string $name
     * @return bool
     */
    public function isName(string $name) : bool{
        return in_array($name, $this->names);
    }
}