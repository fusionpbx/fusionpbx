<?php


namespace App\Telegram\Commands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use LitEmoji\LitEmoji;
use Illuminate\Support\Facades\DB;

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
			$coolpbx_password = $tokens[2] ?? '';

			// Let's find the user_uuid
			// select * from v_users inner join v_domains using(domain_uuid) where username='superadmin' and domain_name='to-call.me'\G

			$user_uuid = DB::table('v_users')
					->join('v_domains', 'domain_uuid', '=', 'v_domains.domain_uuid')
					->where('username', '=', $coolpbx_user.'@'.$coolpbx_domain)
					->orWhere(function(Builder $query){
						$query->where('username', '=', $coolpbx_user)
							->where('domain_name', '=', $coolpbx_domain);
					})
					->value('user_uuid');

			if(!empty($user_uuid)){
				if (Auth::attempt(['user_uuid' => $user_uuid, 'password' => $password])){
				}
				else{
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
