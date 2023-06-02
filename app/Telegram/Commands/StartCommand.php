<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Auth;

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
		$message = $this->getMessage();
		$from_user = $message->getFrom();
		$from_user_id = $from_user->getId();
		$session_id = md5($from_user_id);
		\OKayInc\StatelessSession::start($session_id);
		Auth::logout();
		\OKayInc\StatelessSession::reset();
		return $this->replyToChat('Hello world! 👋');
	}

}
