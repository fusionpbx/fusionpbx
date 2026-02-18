<?php

namespace FusionPBX\Services\ESL;

use Exception;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;

/**
 * FreeSWITCH ESL (Event Socket Library) Manager
 * Provides real-time communication with FreeSWITCH event socket
 */
class ESLManager
{
    protected $host;
    protected $port;
    protected $password;
    protected $connection = null;
    protected $authenticated = false;
    protected $eventCallbacks = [];
    protected $loop;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 8021,
        string $password = 'ClueCon'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->loop = Loop::get();
    }

    /**
     * Connect to FreeSWITCH ESL
     */
    public function connect(): void
    {
        $connector = new Connector(['timeout' => 5]);
        
        $connector->connect("tcp://{$this->host}:{$this->port}")
            ->then(function (ConnectionInterface $connection) {
                $this->connection = $connection;
                
                $connection->on('data', function ($data) {
                    $this->handleData($data);
                });
                
                $connection->on('close', function () {
                    $this->authenticated = false;
                    $this->reconnect();
                });
                
                $connection->on('error', function (Exception $error) {
                    error_log("ESL Connection Error: " . $error->getMessage());
                });
            }, function (Exception $error) {
                error_log("ESL Connection Failed: " . $error->getMessage());
                $this->scheduleReconnect();
            });
    }

    /**
     * Authenticate with FreeSWITCH
     */
    protected function authenticate(): void
    {
        if ($this->connection && !$this->authenticated) {
            $this->connection->write("auth {$this->password}\n\n");
        }
    }

    /**
     * Subscribe to events
     */
    public function subscribeEvents(array $events = ['all']): void
    {
        if (!$this->authenticated) {
            return;
        }

        $eventList = implode(' ', $events);
        $this->send("event plain $eventList");
    }

    /**
     * Execute API command
     */
    public function api(string $command): void
    {
        if (!$this->authenticated) {
            throw new Exception('Not authenticated to ESL');
        }

        $this->send("api $command");
    }

    /**
     * Execute background job
     */
    public function bgapi(string $command, callable $callback = null): void
    {
        if (!$this->authenticated) {
            throw new Exception('Not authenticated to ESL');
        }

        if ($callback) {
            $this->eventCallbacks['BACKGROUND_JOB'][] = $callback;
        }

        $this->send("bgapi $command");
    }

    /**
     * Send raw command
     */
    protected function send(string $command): void
    {
        if ($this->connection) {
            $this->connection->write("$command\n\n");
        }
    }

    /**
     * Handle incoming data
     */
    protected function handleData(string $data): void
    {
        // Parse ESL event
        if (strpos($data, 'Content-Type: auth/request') !== false) {
            $this->authenticate();
        } elseif (strpos($data, 'Reply-Text: +OK accepted') !== false) {
            $this->authenticated = true;
            $this->onAuthenticated();
        } elseif (strpos($data, 'Event-Name:') !== false) {
            $event = $this->parseEvent($data);
            $this->dispatchEvent($event);
        }
    }

    /**
     * Parse ESL event
     */
    protected function parseEvent(string $data): array
    {
        $event = [];
        $lines = explode("\n", $data);
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $event[trim($key)] = trim($value);
            }
        }
        
        return $event;
    }

    /**
     * Dispatch event to callbacks
     */
    protected function dispatchEvent(array $event): void
    {
        $eventName = $event['Event-Name'] ?? null;
        
        if (!$eventName) {
            return;
        }

        // Call specific event callbacks
        if (isset($this->eventCallbacks[$eventName])) {
            foreach ($this->eventCallbacks[$eventName] as $callback) {
                call_user_func($callback, $event);
            }
        }

        // Call 'all' event callbacks
        if (isset($this->eventCallbacks['all'])) {
            foreach ($this->eventCallbacks['all'] as $callback) {
                call_user_func($callback, $event);
            }
        }
    }

    /**
     * Register event callback
     */
    public function on(string $eventName, callable $callback): void
    {
        $this->eventCallbacks[$eventName][] = $callback;
    }

    /**
     * Called when authenticated
     */
    protected function onAuthenticated(): void
    {
        // Subscribe to all events by default
        $this->subscribeEvents(['CHANNEL_CREATE', 'CHANNEL_ANSWER', 'CHANNEL_HANGUP', 
                               'CHANNEL_HANGUP_COMPLETE', 'HEARTBEAT']);
    }

    /**
     * Reconnect after disconnection
     */
    protected function reconnect(): void
    {
        $this->scheduleReconnect();
    }

    /**
     * Schedule reconnection
     */
    protected function scheduleReconnect(): void
    {
        $this->loop->addTimer(5, function () {
            $this->connect();
        });
    }

    /**
     * Get active channels
     */
    public function getActiveChannels(callable $callback): void
    {
        $this->bgapi('show channels as json', function ($event) use ($callback) {
            $body = $event['_body'] ?? '[]';
            $channels = json_decode($body, true);
            call_user_func($callback, $channels);
        });
    }

    /**
     * Originate call
     */
    public function originate(
        string $destination,
        string $extension,
        string $context = 'default',
        string $callerIdName = '',
        string $callerIdNumber = ''
    ): void {
        $command = "originate {$destination} &extension($extension XML $context)";
        
        if ($callerIdName || $callerIdNumber) {
            $command .= " {$callerIdNumber} {$callerIdName}";
        }
        
        $this->bgapi($command);
    }

    /**
     * Hangup channel
     */
    public function hangup(string $uuid, string $cause = 'NORMAL_CLEARING'): void
    {
        $this->api("uuid_kill $uuid $cause");
    }

    /**
     * Transfer channel
     */
    public function transfer(string $uuid, string $extension, string $context = 'default'): void
    {
        $this->api("uuid_transfer $uuid $extension XML $context");
    }

    /**
     * Start event loop
     */
    public function run(): void
    {
        $this->connect();
        $this->loop->run();
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
            $this->authenticated = false;
        }
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connection !== null && $this->authenticated;
    }
}
