<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class StartCommand extends UserCommand
{

    /** @var string Command name */
    protected $name = 'start';
    /** @var string Command description */
    protected $description = 'Start';
    /** @var string Usage description */
    protected $usage = '/start';
    /** @var string Version */
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        return $this->replyToChat('Hello world! 👋');
    }

}
