<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Auth;
use LitEmoji\LitEmoji;
use Illuminate\Support\Facades\App;
use App\Models\User;
use App\Models\Group;

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
		$message = $this->getMessage();
		$text = $message->getText(true);
		$from_user = $message->getFrom();
		$from_user_username = $from_user->getUsername();
		$from_user_id = $from_user->getId();
		$from_user_language = $from_user->getLanguageCode() ?? 'en';

		$group_permission = new \App\Http\Controllers\GroupPermissionController();
		$group_permission->setTelegramUser($from_user_id);

		App::setLocale($from_user_language);

		if ($group_permission->allowed($text) === true){
			$answer = 'Found';
		}
		else{
			$answer = 'Not Found';
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
