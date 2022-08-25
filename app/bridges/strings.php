<?php

/*
  FusionPBX
  Version: MPL 1.1

  The contents of this file are subject to the Mozilla Public License Version
  1.1 (the "License"); you may not use this file except in compliance with
  the License. You may obtain a copy of the License at
  http://www.mozilla.org/MPL/

  Software distributed under the License is distributed on an "AS IS" basis,
  WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
  for the specific language governing rights and limitations under the
  License.

  The Original Code is FusionPBX

  The Initial Developer of the Original Code is
  Mark J Crane <markjcrane@fusionpbx.com>
  Portions created by the Initial Developer are Copyright (C) 2018 - 2019
  the Initial Developer. All Rights Reserved.

  Contributor(s):
  Mark J Crane <markjcrane@fusionpbx.com>
  Tim Fry <tim@voipstratus.com>
 */

global $strings;
$basename = dirname($_SERVER['SCRIPT_FILENAME']) . '/resources/languages';
$language_code = $_SESSION['domain']['language']['code'];
if (file_exists("$basename/$language_code.ini")) {
    // Use custom ini reader to read raw files without sections to allow for True/False key value
    $strings = raw_ini_file("$basename/$language_code.ini");
}

function raw_ini_file(string $filename): array {
    $retVal = [];
    $h = fopen($filename, 'r');
    foreach (get_all_lines($h) as $line) {
        switch ($line{0}) {
            case '#':
            case '/':
            case ';':
            case '<':
            case '?':
                break;
            default:
                $kv = get_kv($line);
                $retVal[$kv[0]] = $kv[1];
        }
    }
    return $retVal;
}

function get_all_lines($hFile) {
    while (!feof($hFile)) {
        $line = fgets($hFile, 1024);
        if ($line == '') {
            yield ';';
        } else {
            yield $line;
        }
    }
}

function get_kv($line): array {
    $a = [];
    $char = '';
    $ignore = false;
    $pointer = 0;
    for ($i = 0; $i < strlen($line); $i++) {
        $char = $line[$i];
        switch ($char) {
            case '=':
                if (!$ignore) {
                    $pointer = 1;
                }
            case '\\':
                $ignore = true;
        }
        if (!$ignore) {
            $a[$pointer] .= $char;
        }
        $ignore = false;
    }
    $a[0] = trim($a[0]);
    $a[1] = trim($a[1]);
    return $a;
}
