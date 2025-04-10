<?php

namespace App\Services\FreeSwitch;

use App\Http\Controllers\EventSocketBufferController;
use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class FreeSwitchConnectionManager
{
    private static ?FreeSwitchConnectionManager $instance = null;
    private $fp = null;
    private $buffer;
    private string $type;

    private function __construct()
    {
        $this->type = env('FS_API_TYPE', 'XML_RPC');
        if (App::hasDebugModeEnabled()) {
            Log::debug('['.__CLASS__.']['.__METHOD__.'] $this->type: '.$this->type);
        }
        
        if ($this->type === 'EVENT_SOCKET') {
            $this->buffer = new EventSocketBufferController;
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
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

    public function executeCommand(string $command, ?string $param = null): ?string
    {
        if ($this->type === 'EVENT_SOCKET') {
            return $this->es_execute($command, $param);
        } else { // XML_RPC
            $settings = Setting::first();
            $host = $settings->event_socket_ip_address ?? '127.0.0.1';
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

    private function es_connect($host, $port, $password)
    {
        $errorn = null; $errordesc = null;
        $this->fp = @fsockopen($host, $port, $errorn, $errordesc, 3);
        
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

    private function es_read_event()
    {
        if (!$this->fp) {
            return false;
        }

        $b = $this->buffer;
        $content = [];

        while (true) {
            while (($line = $b->read_line()) !== false) {
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
            $b->append($buffer);
        }

        if (array_key_exists('Content-Length', $content)) {
            $str = $b->read_n($content['Content-Length']);
            if ($str === false) {
                while (!feof($this->fp)) {
                    $buffer = fgets($this->fp, 1024);
                    $b->append($buffer);
                    $str = $b->read_n($content['Content-Length']);
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

    private function es_request($cmd)
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
        $default_settings = app()->make('App\Http\Controllers\DefaultSettingController');
        $http_port = $default_settings->get('config', 'xml_rpc.http_port', 'numeric') ?? 8080;
        $auth_user = $default_settings->get('config', 'xml_rpc.auth_user', 'text') ?? 'freeswitch';
        $auth_pass = $default_settings->get('config', 'xml_rpc.auth_pass', 'text') ?? 'works';
        
        $url = 'http://'.$host.':'.$http_port.'/txtapi/'.$command.'?'.(isset($param) ? rawurlencode($param) : '');
        
        if (App::hasDebugModeEnabled()) {
            Log::debug('['.__CLASS__.']['.__METHOD__.'] $url: '. $url);
        }
        
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($auth_user, $auth_pass)
                    ->withOptions([
                        'debug' => App::hasDebugModeEnabled(),
                    ])
                    ->get($url);

        if ($response->ok()) {
            return $response->body() ?? null;
        }
        
        return null;
    }
    
    // Prevenir la clonación del objeto
    private function __clone() {}
    
    // Prevenir la deserialización
    public function __wakeup() {}
}