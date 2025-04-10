<?php

namespace App\Services\FreeSwitch;

class EventSocketBuffer
{
    private $content;
    private $eol;

    public function __construct()
    {
        $this->content = '';
        $this->eol = "\n";
    }

    public function append($str)
    {
        $this->content .= $str;
    }

    public function read_line()
    {
        $ar = explode($this->eol, $this->content, 2);
        if (count($ar) != 2) {
            return false;
        }
        $this->content = $ar[1];
        return $ar[0];
    }

    public function read_n($n)
    {
        if (strlen($this->content) < $n) {
            return false;
        }
        $s = substr($this->content, 0, $n);
        $this->content = substr($this->content, $n);
        return $s;
    }

    public function read_all($n = null)
    {
        $tmp = $this->content;
        $this->content = '';
        return $tmp;
    }
}