<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-01-01
 * Time: 14:55
 */

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;


use mineceit\game\leaderboard\Leaderboards;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class StatsHologram extends LeaderboardHologram
{

    public function __construct(Vector3 $vec3, Level $level, bool $build, Leaderboards $leaderboards = null)
    {
        parent::__construct($vec3, $level, $leaderboards);
        $this->leaderboardKeys = $leaderboards->getLeaderboardKeys(false);
        if($build) {
            $this->placeFloatingHologram(false);
        }
    }

    /**
     *
     * @param bool $updateKey
     *
     * Places the hologram down into the world.
     */
    protected function placeFloatingHologram(bool $updateKey = true): void
    {
        $len = count($this->leaderboardKeys) - 1;

        $key = $this->leaderboardKeys[$this->currentKey];

        $text = $this->leaderboards->getStatsLeaderboardOf($key);

        if($updateKey) {
            $this->currentKey++;
        }

        if($this->currentKey > $len)
            $this->currentKey = 0;

        $string = '';

        $count = 0;

        $players = array_keys($text);

        $size = count($text);

        if ($size > 0) {

            $keyName = $this->getNameFromKey($key);

            $title = TextFormat::BLUE . TextFormat::BOLD . $keyName . ' Leaderboards';

            foreach ($players as $name) {

                if ($count > 9) break;

                $line = $count === 9 ? "" : "\n";

                $stat = $text[$name];
                $place = $count + 1;
                $format = TextFormat::GRAY . $place . '. ' . TextFormat::GOLD . $name . ' ' . TextFormat::DARK_GRAY . '(' . TextFormat::WHITE . $stat . TextFormat::DARK_GRAY . ')' . $line;
                $string .= $format;

                $count++;
            }

            if ($this->floatingText === null)
                $this->floatingText = new FloatingTextParticle($this->vec3, $string, $title);
            else {
                $this->floatingText->setTitle($title);
                $this->floatingText->setText($string);
            }

            $this->level->addParticle($this->floatingText);
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function getNameFromKey(string $key) : string {

        $result = 'Unknown';

        switch($key) {
            case 'kdr':
                $result = "KDR";
                break;
            case 'kills':
                $result = 'Kills';
                break;
            case 'deaths':
                $result = 'Deaths';
                break;
        }

        return $result;
    }
}