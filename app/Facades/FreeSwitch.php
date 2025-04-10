<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null execute(string $command, string|null $param = null)
 * @method static bool isConnected()
 * @method static bool reconnect()
 * @method static string getConnectionType()
 * @method static string|null getGatewayStatus(string $gateway_uuid, string $result_type = 'xml')
 * @method static string|null getServerStatus()
 * @method static void closeConnection()
 * 
 * @see \App\Services\FreeSwitch\FreeSwitchService
 */
class FreeSwitch extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'freeswitch';
    }
}