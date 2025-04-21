<?php

namespace App\Services\FreeSwitch;

use App\Contracts\FreeSwitchConnectionManagerInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class FreeSwitchService
{
    protected FreeSwitchConnectionManagerInterface $connection;

    public function __construct(FreeSwitchConnectionManagerInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute a command on the FreeSWITCH server
     *
     * @param string $command The command to execute
     * @param string|null $param Optional parameters for the command
     * @return string|null The response from the FreeSWITCH server
     */
    public function execute(string $command, ?string $param = null): ?string
    {
        if (App::hasDebugModeEnabled()) {
            Log::debug('['.__CLASS__.']['.__METHOD__.'] Executing command: ' . $command . ' ' . $param );
        }

        return $this->connection->executeCommand($command, $param, $host = '127.0.0.1');
    }

    /**
     * Check if the service is connected to the FreeSWITCH server
     *
     * @return bool Connection status
     */
    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    /**
     * Force a reconnection to the FreeSWITCH server
     *
     * @return bool Success status
     */
    public function reconnect(): bool
    {
        $this->connection->close();
        return $this->connection->connect();
    }

    /**
     * Get the connection type (EVENT_SOCKET or XML_RPC)
     *
     * @return string Connection type
     */
    public function getConnectionType(): string
    {
        return $this->connection->getConnectionType();
    }

    /**
     * Get gateway status
     *
     * @param string $gateway_uuid The UUID of the gateway
     * @param string $result_type The type of result (xml, json, etc)
     * @return string|null The gateway status
     */
    public function getGatewayStatus(string $gateway_uuid, string $result_type = 'xml'): ?string
    {
        $cmd = 'sofia xmlstatus gateway ' . $gateway_uuid;
        $response = $this->execute($cmd);

        if ($response == "Invalid Gateway!") {
            $cmd = 'sofia xmlstatus gateway ' . strtoupper($gateway_uuid);
            $response = $this->execute($cmd);
        }

        return $response;
    }

    /**
     * Get server status
     *
     * @return string|null The server status
     */
    public function getServerStatus(): ?string
    {
        return $this->execute('status');
    }

    /**
     * Close the connection to the FreeSWITCH server
     */
    public function closeConnection(): void
    {
        $this->connection->close();
    }
}
