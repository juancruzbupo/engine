<?php
/**
 * Minds FAQ
 *
 * @version 2
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2;

use DateTime;
use Minds\Core;
use Minds\Interfaces;
use Minds\Api\Factory;

class howmanyhours implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        $now = new DateTime('now');
        $user = Core\Session::getLoggedInUser();
        $seconds =  $now->getTimestamp() - intval($user->time_created);
        $response = ['seconds' => $seconds, 'user' => $user->time_created, 'now' => $now->getTimestamp()];

        return Factory::response($response);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
