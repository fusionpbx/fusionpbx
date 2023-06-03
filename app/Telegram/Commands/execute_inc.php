<?php
$message = $this->getMessage();
$text = $message->getText(true);
$from_user = $message->getFrom();
$from_user_username = $from_user->getUsername();
$from_user_id = $from_user->getId();
$from_user_language = $from_user->getLanguageCode() ?? 'en';
App::setLocale($from_user_language);
$session_id = md5($from_user_id);

