<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-07-29
 * Time: 18:26
 */

declare(strict_types=1);

namespace mineceit\game\level;


use pocketmine\plugin\PluginException;
use pocketmine\scheduler\AsyncTask;

class AsyncDeleteLevel extends AsyncTask
{

    /* @var string */
    private $directory;

    public function __construct(string $path)
    {
        $this->directory = $path;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $this->removeDirectory($this->directory);
    }

    private function removeDirectory(string $dir) : void {

        if(!is_dir($dir)){
            throw new PluginException("$dir must be a directory");
        }
        if(substr($dir, strlen($dir) - 1, 1) != '/'){
            $dir .= '/';
        }
        $files = glob($dir . '*', GLOB_MARK);
        foreach($files as $file){
            if(is_dir($file)){
                $this->removeDirectory($file);
            }else{
                unlink($file);
            }
        }
        rmdir($dir);
    }
}