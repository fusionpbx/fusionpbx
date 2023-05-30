<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use LitEmoji\LitEmoji;

class AuthCommand extends UserCommand
{

	/** @var string Command name */
	protected $name = 'auth';
	/** @var string Command description */
	protected $description = 'Link the current Telegram user to the CoolPBX user';
	/** @var string Usage description */
	protected $usage = '/auth {user@domain password}';
	/** @var string Version */
	protected $version = '1.0.0';

	public function execute(): ServerResponse
	{
		$message = $this->getMessage();
		$text = $message->getText(true);
		$from_user = $message->getFrom();
		$from_user_username = $from_user->getUsername();
		$from_user_id = $from_user->getId();

	$tokens = explode(' ', $text, 2); $answer = '';
        if (	(filter_var($tokens[0], FILTER_VALIDATE_EMAIL)) && 
		(!empty($tokens[1]))
	) {
		//first sintax
		$sub_tokens = explode('@', $tokens[0], 2);
		$coolpbx_user = $sub_tokens[0];
		$coolpbx_domain = $sub_tokens[1];
		$coolpbx_password = $tokens[2] ?? '';
	}
	else{
		$answer = '';
	}

	 return $this->replyToChat(
		LitEmoji::encodeUnicode($answer),
		[
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => 'true'
		]
	);
    }

}
