<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class CheckPermissionCommand extends UserCommand
{

    /** @var string Command name */
    protected $name = 'check_permission';
    /** @var string Command description */
    protected $description = '';
    /** @var string Usage description */
    protected $usage = '/check_permission';
    /** @var string Version */
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {

    }

}
