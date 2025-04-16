<?php

namespace App\Http\Controllers;

use App\Facades\FreeSwitch;
use Illuminate\Http\Request;

class FreeSWITCHAPIController extends Controller
{
    /**
     * Execute a command on the FreeSWITCH server
     *
     * @param string $command The command to execute
     * @param string|null $param Optional parameters for the command
     * @return string|null The response from the FreeSWITCH server
     */
    public function execute(string $command, ?string $param = null): ?string
    {
        return FreeSwitch::execute($command, $param);
    }

    /**
     * Get gateway status
     *
     * @param string $gateway_uuid The UUID of the gateway
     * @param string $result_type The type of result (xml, json, etc)
     * @return string|null The gateway status
     */
    public function switchGatewayStatus(string $gateway_uuid, string $result_type = 'xml'): ?string
    {
        return FreeSwitch::getGatewayStatus($gateway_uuid, $result_type);
    }

    /**
     * Get server status
     *
     * @return string|null The server status
     */
    public function getServerStatus(): ?string
    {
        return FreeSwitch::getServerStatus();
    }
}
