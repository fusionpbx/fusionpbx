<?php

class curlRingotel
{

    function request($api, $method, $parameters, $headers) {    
        $mh = curl_multi_init();
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($parameters) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_multi_add_handle($mh, $ch);

        $active = null;
        do {
            curl_multi_exec($mh, $active);
        } while ($active);
        $server_output = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        curl_multi_close($mh);

        return $server_output;
    }
}
?>