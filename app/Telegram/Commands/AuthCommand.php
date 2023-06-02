<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use LitEmoji\LitEmoji;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

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
		$from_user_language = $from_user->getLanguageCode() ?? 'en';

		App::setLocale($from_user_language);
		$session_id = md5($from_user_id);
		\OKayInc\StatelessSession::start($session_id);
		Auth::logout();
		\OKayInc\StatelessSession::reset();

		$tokens = explode(' ', $text, 2); $answer = '';
		if (	(filter_var($tokens[0], FILTER_VALIDATE_EMAIL)) &&
			(!empty($tokens[1]))
		) {
			//first sintax
			$sub_tokens = explode('@', $tokens[0], 2);
			$coolpbx_user = $sub_tokens[0];
			$coolpbx_domain = $sub_tokens[1];
			$coolpbx_password = $tokens[1] ?? '';

			\Log::debug('$text: '.$text);
			\Log::debug('$coolpbx_user: ' .$coolpbx_user);
			\Log::debug('$coolpbx_domain: ' .$coolpbx_domain);
			\Log::debug('$coolpbx_password: ' .$coolpbx_password);
			// Let's find the user_uuid
			// select * from v_users inner join v_domains using(domain_uuid) where username='superadmin' and domain_name='to-call.me'\G

			$user_uuid = DB::table('v_users')
					->join('v_domains', 'v_users.domain_uuid', '=', 'v_domains.domain_uuid')
					->whereRaw('(username = ?) or (username = ? and domain_name = ?)',[$coolpbx_user.'@'.$coolpbx_domain, $coolpbx_user, $coolpbx_domain])
					->value('v_users.user_uuid');

			\Log::debug('$user_uuid: ' .$user_uuid);
			if(!empty($user_uuid)){
				if (Auth::attempt(['user_uuid' => $user_uuid, 'password' => $coolpbx_password], true)){
					$user = User::find($user_uuid);
					$domain_uuid = $user->domain_uuid;
					\Log::debug('$domain_uuid: ' .$domain_uuid);
					$answer = __('telegram.linked', ['telegram_user' => $from_user_username, 'coolpbx_user' => $coolpbx_user, 'coolpbx_domain' => $coolpbx_domain]);
					\OKayInc\StatelessSession::set('coolpbx_user', $coolpbx_user);
					\OKayInc\StatelessSession::set('coolpbx_domain', $coolpbx_domain);
					\OKayInc\StatelessSession::set('user_uuid', $user_uuid);
					\OKayInc\StatelessSession::set('domain_uuid', $domain_uuid);
				}
				else{
					$answer = __('auth.failed');
				}
			}
			else{
			}

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
