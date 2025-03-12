<?php

namespace App\Http\Controllers;

use App\Http\EventSocketBufferController;
use App\Models\DefaultSetting;
use App\Models\Setting;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FreeSWITCHAPIController extends Controller
{
    private $fp = null;
    private $buffer;

    public function __construct(){
        parent::__construct();
        switch (env('FS_API_TYPE', 'XML_RPC')){
            case 'EVENT_SOCKET':
                $this->buffer = new EventSocketBufferController;

                break;
            default:
                break;
        }
    }
    public function __destruct(){

        switch (env('FS_API_TYPE', 'XML_RPC')){
            case 'EVENT_SOCKET'
                $this->es_close();
                break;
            default:
                break;
        }

        parent::__destruct();
    }

    private function es_connect($host, $port, $password){
        $errorn = null; $errordesc = null;
        $this->$fp = @fsockopen($host, $port, $errorn, $errordesc, 3);
        socket_set_timeout($this->fp, 30000);
        socket_set_blocking($this->fp, true);

        while (!feof($fp)) {
            $event = $this->es_read_event();
            if(@$event['Content-Type'] == 'auth/request'){
                    fputs($fp, 'auth '.($password)."\n\n");
                    break;
            }
        }

        while (!feof($fp)) {
            $event = $this->es_read_event();
            if (@$event['Content-Type'] == 'command/reply') {
                if (@$event['Reply-Text'] == '+OK accepted') {
                    return $fp;
                }
                $this->fp = false;
                fclose($fp);
                return false;
            }
        }
    }

    private function es_connected(): bool {
        if (!$this->fp) {
            return false;
        }
        if (feof($this->fp) === true) {
            return false;
        }
        return true;
    }

    private function es_read_event() {
        if (!$this->fp) {
            return false;
        }

        $b = $this->buffer;
        $content_length = 0;
        $content = Array();

        while (true) {
            while(($line = $b->read_line()) !== false ) {
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

    private function es_request($cmd) {
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

    private function es_reset_fp($fp = false){
        $tmp = $this->fp;
        $this->fp = $fp;
        return $tmp;
    }

    private function es_close() {
        if ($this->fp) {
                fclose($this->fp);
                $this->fp = false;
        }
    }

    public function __call($name, $arguments){
        switch ($name){
            case 'execute':
                if ((count($arguments) == 2) && (env('FS_API_TYPE', 'XML_RPC') == 'EVENT_SOCKET')){
                    return $this->es_execute($arguments[0], $arguments[1] ?? null);
                }
                elseif((count($arguments) == 3) && (env('FS_API_TYPE', 'XML_RPC') == 'XML_RPC')){
                    return $this->rpc_execute($arguments[0], $arguments[1], $arguments[2] ?? null);
                }
                return null;
        }
    }

    private function es_execute(string $command, ?string $param = null): ?string{

        if (!$this->es_connected()){
            $event_socket = Setting::first();
            $this->fp = $this->es_connect($event_socket->event_socket_ip_address ?? '127.0.0.1', $event_socket->event_socket_port ?? 8021, $event_socket->event_socket_password ?? 'ClueCon');
            if ($this->fp === false){
                $answer = null;
            }
            else{
                $cmd = 'api '$command . ' ' . $param;
                $answer = $this->es_request($cmd);
            }
        }

        return $answer;
    }

    private function rpc_execute(string $host, string $command, ?string $param = null): ?string{
        $default_settings = new DefaultSettingController;
        $http_port = $default_settings->get('config', 'xml_rpc.http_port', 'numeric') ?? 8080;
        $auth_user = $default_settings->get('config', 'xml_rpc.auth_user', 'text') ?? 'freeswitch';
        $auth_pass = $default_settings->get('config', 'xml_rpc.auth_pass', 'text') ?? 'works';

        if (isset($param)){
            // In case command has spaces
            // XML-RPC expects command to be one word only
            $full_command = $command . ' ' . $param;
            list($command, $param) = explode(' ', $full_command, 2);
        }
        $url = 'http://'.$host.':'.$http_port.'/webapi/'.$command.(isset($param)?urlencode($param):'');
        $response = Http::withBasicAuth($auth_user, $auth_pass)->get($url);
        if ($response->ok())
            return $response->body() ? null;
        return null;
    }
}
