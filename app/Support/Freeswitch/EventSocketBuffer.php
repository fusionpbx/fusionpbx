<?php

namespace App\Support\Freeswitch;

class EventSocketBuffer
{
    private string $content;
    private string $eol;

    public function __construct()
    {
        $this->content = '';
        $this->eol = "\n";
    }

    public function append(string $str): void
    {
        $this->content .= $str;
    }

    public function read_line(): string|false
    {
        $ar = explode($this->eol, $this->content, 2);
        if (count($ar) !== 2) {
            return false;
        }
        $this->content = $ar[1];
        return $ar[0];
    }

    public function read_n(int $n): string|false
    {
        if (strlen($this->content) < $n) {
            return false;
        }
        $s = substr($this->content, 0, $n);
        $this->content = substr($this->content, $n);
        return $s;
    }

    public function read_all(?int $n = null): string
    {
        $tmp = $this->content;
        $this->content = '';
        return $tmp;
    }
}
