<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-17
 * Time: 17:10
 */

declare(strict_types=1);

namespace mineceit\player\language;

use mineceit\MineceitCore;

class LanguageHandler
{

    /* @var Language[] */
    private $languages;

    /** @var string */
    private $resourcesFolder;

    /** @var Language|null */
    private $defaultLanguage;

    public function __construct(MineceitCore $core)
    {

        $this->resourcesFolder = $core->getResourcesFolder() . "lang";

        $this->defaultLanguage = null;

        // Loads all of the languages within directory. Allows us to add languages easier.

        if(is_dir($this->resourcesFolder)) {

            $files = scandir($this->resourcesFolder);

            foreach($files as $file) {

                if(strpos($file, '.json') !== false) {

                    $locale = str_replace(".json", "", $file);
                    $path = $this->resourcesFolder . "/{$file}";

                    $data = file_get_contents($path);
                    $data = json_decode($data, true);

                    $languageData = $data["language_data"];

                    $messageData = $data["messages"];

                    $itemsNames = $data["item_data"];

                    $default = (bool)$languageData["default"];

                    $credit = (string)$languageData["credit"];

                    $shorten = (bool)$languageData["shorten"];

                    $shortenOrdinals = true;
                    if(isset($languageData['shorten-ordinals'])) {
                        $shortenOrdinals = (bool)$languageData['shorten-ordinals'];
                    }

                    // TODO ORDINALS FOR EACH LANG
                    $ordinals = ['other' => ""];
                    if(isset($data['ordinals'])) {
                        $ordinals = (array)$data['ordinals'];
                    }

                    $lang = new Language(
                        $locale,
                        $languageData["translate"],
                        $languageData["names"],
                        $messageData,
                        $itemsNames,
                        $ordinals,
                        $languageData["old-local"] ?? "",
                        $shorten,
                        $credit,
                        $shortenOrdinals
                    );

                    $this->languages[$locale] = $lang;

                    if($default) {
                        $this->defaultLanguage = $lang;
                    }
                }
            }
        }
    }

    /**
     * @param string $lang
     * @return Language|null
     */
    public function getLanguage(string $lang) {
        if(isset($this->languages[$lang]))
            return $this->languages[$lang];
        return $this->defaultLanguage;
    }

    /**
     * @return array|Language[]
     */
    public function getLanguages() {
        return $this->languages;
    }

    /**
     * @param string $oldName
     * @return Language|null
     */
    public function getLanguageFromOldName(string $oldName) {

        foreach($this->languages as $lang) {
            $oldLangName = $lang->getOldLocalName();
            if($oldLangName === $oldName) {
                return $lang;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @param string $locale
     * @return Language|null
     */
    public function getLanguageFromName(string $name, string $locale = "") {

        foreach($this->languages as $language) {
            if($language->hasName($name, $locale)) {
                return $language;
            }
        }

        return null;
    }
}