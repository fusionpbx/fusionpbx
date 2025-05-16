<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Auth;
use LitEmoji\LitEmoji;
use Illuminate\Support\Facades\App;
use App\Models\User;

class WhoamiCommand extends UserCommand
{

	/** @var string Command name */
	protected $name = 'whoami';
	/** @var string Command description */
	protected $description = '';
	/** @var string Usage description */
	protected $usage = '/whoami';
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
		App::setLocale($from_user_language);
		$session_id = md5($from_user_id);

		\OKayInc\StatelessSession::start($session_id);
		$coolpbx_user = \OKayInc\StatelessSession::get('coolpbx_user');
		$coolpbx_domain = \OKayInc\StatelessSession::get('coolpbx_domain');
		$user_uuid = \OKayInc\StatelessSession::get('user_uuid');

		if (!empty($coolpbx_user) && !empty($coolpbx_domain) && !empty($user_uuid)) {
			$answer = __('telegram.linked', ['telegram_user' => $from_user_username, 'coolpbx_user' => $coolpbx_user, 'coolpbx_domain' => $coolpbx_domain]);

			$user = User::find($user_uuid);
			if (count($user->groups) > 0){
				$answer .= PHP_EOL . __('telegram.you-belong-to') . PHP_EOL;
				foreach ($user->groups as $group){
					$answer .= $group->group_name.PHP_EOL;
				}
			}
			else {
				$answer .= PHP_EOL . __('telegram.you-dont-belong-to') . __('telegram.any-group');
			}
		}
		else{
			$answer = __('telegram.notlinked');
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
