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
use App\Models\Domain;
use App\Models\Group;

class GroupsCommand extends UserCommand
{

	/** @var string Command name */
	protected $name = 'groups';
	/** @var string Command description */
	protected $description = '';
	/** @var string Usage description */
	protected $usage = '/groups';
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
		$domain_uuid = \OKayInc\StatelessSession::get('domain_uuid');
		\Log::debug('$domain_uuid: ' .$domain_uuid);
		$answer = '';
		if (!empty($coolpbx_user) && !empty($coolpbx_domain) && !empty($user_uuid)) {
			$answer = __('telegram.accesible-groups') . PHP_EOL;
			$groups = Domain::find($domain_uuid)->groups();
			$global_groups = Group::findGlobals();
			if ($groups->count() > 0) {
				$domain_name = Domain::find($domain_uuid)->domain_name;
				\Log::debug('$domain_name: ' .$domain_name);
				foreach ($groups as $group){
					$answer .= $group->group_name . ' @ ' . $domain_name . PHP_EOL;
				}
			}
			else {
				$answer .= __('telegram.no-local-groups') . PHP_EOL;
			}

			$answer .= PHP_EOL . __('telegram.global-groups') . PHP_EOL;
			foreach ($global_groups as $group){
				$answer .= '<i>'.$group->group_name . '</i>' . PHP_EOL;
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
