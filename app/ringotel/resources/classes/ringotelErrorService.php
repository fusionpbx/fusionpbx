<?php 

class RingotelErrorService {

    function errorCodes($code) {
    }

    function check($data) {
        if (strlen($data->error) > 0) {
            exit($data->error);
        }
    }

}