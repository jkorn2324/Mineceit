<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-02-19
 * Time: 11:44
 */

namespace mineceit\network\requests;


use mineceit\player\MineceitPlayer;
use Closure;

class RequestPool
{

    /** @var Request[]|array */
    private static $requests = [];

    /**
     * @param int $type
     * @param MineceitPlayer $player
     * @param string $buffer
     * @param Closure $closure
     *
     * Adds a new request.
     */
    public static function addRequest(int $type, MineceitPlayer $player, string $buffer, Closure $closure) : void {
        $request = new Request($type, $buffer, $player, $closure);
        self::$requests[$player->getUniqueId()->toString()] = $request;
    }

    /**
     * @param string $eventData
     * @param MineceitPlayer $player
     *
     * Gets the request from the player.
     */
    public static function doRequest(string $eventData, MineceitPlayer $player) : void {

        try {

            $uuid = $player->getUniqueId()->toString();

            //var_dump($uuid);

            if (isset(self::$requests[$uuid])) {

                $request = self::$requests[$uuid];
                //var_dump($request);
                $request->update($eventData);

                unset(self::$requests[$uuid]);
            }

        } catch (\Exception $e) {
            $trace = $e->getTraceAsString();
        }
    }
}