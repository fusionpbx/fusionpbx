<?php

namespace App\Contracts;

interface FreeSwitchConnectionManagerInterface
{
    public function connect(): bool;
    public function isConnected(): bool;
    public function executeCommand(string $command, ?string $param = null, string $host = '127.0.0.1' ): ?string;
    public function close(): void;
    public function getConnectionType(): string;
}
