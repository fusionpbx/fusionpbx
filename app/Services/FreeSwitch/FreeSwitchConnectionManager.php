<?php

namespace App\Services\FreeSwitch;

use App\Models\Setting;
use App\Contracts\FreeSwitchConnectionManagerInterface;
use App\Facades\DefaultSetting;
use App\Support\Freeswitch\EventSocketBuffer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class FreeSwitchConnectionManager implements FreeSwitchConnectionManagerInterface
{
    private mixed $fp = null;
    private ?EventSocketBuffer $buffer = null;
    private string $type;

    public function __construct()
    {
        $this->type = config('freeswitch.api_type', 'XML_RPC');

        if (App::hasDebugModeEnabled()) {
            Log::debug('[' . __CLASS__ . '][' . __METHOD__ . '] $this->type: ' . $this->type);
        }

        if ($this->type === 'EVENT_SOCKET') {
            $this->buffer = new EventSocketBuffer();
        }

        $this->connect();
    }

    public function getConnectionType(): string
    {
        return $this->type;
    }

    public function connect(): bool
    {
        if ($this->isConnected()) {
            return true;
        }

        $settings = Setting::first();
        $host = $settings->event_socket_ip_address ?? '127.0.0.1';
        $port = $settings->event_socket_port ?? 8021;
        $password = $settings->event_socket_password ?? 'ClueCon';

        $this->fp = $this->es_connect($host, $port, $password);

        return $this->fp !== false;
    }

    public function isConnected(): bool
    {
        if ($this->type !== 'EVENT_SOCKET') {
            return true;
        }

        return $this->es_connected();
    }

    public function executeCommand(string $command, ?string $param = null, string $host = '127.0.0.1'): ?string
    {
        if ($this->type === 'EVENT_SOCKET') {
            return $this->es_execute($command, $param);
        } else { // XML_RPC
            return $this->rpc_execute($host, $command, $param);
        }
    }

    public function close(): void
    {
        if ($this->fp) {
            fclose($this->fp);
            $this->fp = null;
        }
    }

    private function es_connect(string $host, int $port, string $password): mixed
    {
        $error_code = null; $error_message = null;
        $this->fp = @fsockopen($host, $port, $error_code, $error_message, 3);

        if (!$this->fp) {
            return false;
        }

        socket_set_timeout($this->fp, 30000);
        socket_set_blocking($this->fp, true);

        while (!feof($this->fp)) {
            $event = $this->es_read_event();
            if (@$event['Content-Type'] == 'auth/request') {
                fputs($this->fp, 'auth '.($password)."\n\n");
                break;
            }
        }

        while (!feof($this->fp)) {
            $event = $this->es_read_event();
            if (@$event['Content-Type'] == 'command/reply') {
                if (@$event['Reply-Text'] == '+OK accepted') {
                    return $this->fp;
                }
                fclose($this->fp);
                return false;
            }
        }

        return false;
    }

    private function es_connected(): bool
    {
        if (!$this->fp) {
            return false;
        }
        if (feof($this->fp) === true) {
            return false;
        }
        return true;
    }

    private function es_read_event(): array|false
    {
        if (!$this->fp) {
            return false;
        }

        $content = [];

        while (true) {
            while (($line = $this->buffer->read_line()) !== false) {
                if ($line == '') {
                    break 2;
                }
                $kv = explode(':', $line, 2);
                $content[trim($kv[0])] = trim($kv[1]);
            }

            if (feof($this->fp)) {
                break;
            }

            $buffer = fgets($this->fp, 1024);
            $this->buffer->append($buffer);
        }

        if (array_key_exists('Content-Length', $content)) {
            $str = $this->buffer->read_n($content['Content-Length']);
            if ($str === false) {
                while (!feof($this->fp)) {
                    $buffer = fgets($this->fp, 1024);
                    $this->buffer->append($buffer);
                    $str = $this->buffer->read_n($content['Content-Length']);
                    if ($str !== false) {
                        break;
                    }
                }
            }
            if ($str !== false) {
                $content['$'] = $str;
            }
        }

        return $content;
    }

    private function es_request(string $cmd): ?string
    {
        if (!$this->fp) {
            return false;
        }

        $cmd_array = explode("\n", $cmd);
        foreach ($cmd_array as &$value) {
            fputs($this->fp, $value."\n");
        }
        fputs($this->fp, "\n"); //second line feed to end the headers

        $event = $this->es_read_event();

        if (array_key_exists('$', $event)) {
            return $event['$'];
        }
        return $event;
    }

    private function es_execute(string $command, ?string $param = null): ?string
    {
        if (!$this->connect()) {
            return null;
        }

        $cmd = 'api ' . $command . ' ' . $param;
        return $this->es_request($cmd);
    }

    private function rpc_execute(string $host, string $command, ?string $param = null): ?string
    {
        $http_port = DefaultSetting::get('config', 'xml_rpc.http_port', 'numeric') ?? 8080;
        $auth_user = DefaultSetting::get('config', 'xml_rpc.auth_user', 'text') ?? 'freeswitch';
        $auth_pass = DefaultSetting::get('config', 'xml_rpc.auth_pass', 'text') ?? 'works';

        $url = 'http://'.$host.':'.$http_port.'/txtapi/'.$command.'?'.(isset($param) ? rawurlencode($param) : '');

        if (App::hasDebugModeEnabled()) {
            Log::debug('['.__CLASS__.']['.__METHOD__.'] $url: '. $url);
        }

        try {
            $response = Http::withBasicAuth($auth_user, $auth_pass)
                ->withOptions([
                    'debug' => App::hasDebugModeEnabled(),
                ])
                ->get($url);

        } catch (ConnectionException $e) {
            if (App::hasDebugModeEnabled()) {
                Log::error('['.__CLASS__.']['.__METHOD__.'] Error: ' . $e->getMessage());
            }
            return null;
        }

        if ($response->ok()) {
            return $response->body() ?? null;
        }

        return null;
    }
}
