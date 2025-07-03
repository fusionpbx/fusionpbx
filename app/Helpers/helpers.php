<?php

use App\Facades\Setting;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        if (!Auth::check()) return false;

        $user = Auth::user();
        foreach ($user->groups as $group) {
            if ($group->permissions->contains('permission_name', $permission)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('getModels')) {
    function getModels(): Collection
    {
        $models = collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                $class = sprintf(
                    '\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );

                return $class;
            })
            ->filter(function ($class) {
                $valid = false;

                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(Model::class) &&
                        !$reflection->isAbstract();
                }

                return $valid;
            });

        return $models->values();
    }
}

if (!function_exists('generatePassword')) {
    function generatePassword($length = 0, $strength = 0)
    {
        $password = '';
        $chars = '';
        if ($length === 0 && $strength === 0) { //set length and strenth if specified in default settings and strength isn't numeric-only
            $length = is_numeric(Setting::getSetting('users', 'password_length', 'numeric')) ? Setting::getSetting('users', 'password_length', 'numeric') : 20;
            $strength = is_numeric(Setting::getSetting('users', 'password_strength', 'numeric')) ? Setting::getSetting('users', 'password_strength', 'numeric') : 4;
        }
        if ($strength >= 1) {
            $chars .= "0123456789";
        }
        if ($strength >= 2) {
            $chars .= "abcdefghijkmnopqrstuvwxyz";
        }
        if ($strength >= 3) {
            $chars .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
        }
        if ($strength >= 4) {
            $chars .= "!^$%*?.()";
        }
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

if (!function_exists('getAccountCode')) {
    function getAccountCode(): ?string
    {
        $accountCode = Setting::getSetting('domain', 'accountcode', 'text');
        if (!empty($accountCode)) {
            if ($accountCode === 'none') {
                $accountCode = null;
            }
        } else {
            $accountCode = Session::get('domain_name');
        }
        return $accountCode;
    }
}

if(!function_exists('findInDirectory'))
{
	function findInDirectory($dir, $recursive)
	{
		$files = [];

		$tree = glob(rtrim($dir, '/') . '/*');

		if (is_array($tree))
		{
			foreach ($tree as $file)
			{
				if (is_dir($file) && $recursive)
				{
					$files = array_merge($files, findInDirectory($file, $recursive));
				}
				elseif (is_file($file))
				{
					$files[] = $file;
				}
			}
		}

		return $files;
	}
}

if(!function_exists('getSounds'))
{
	function getSounds($language = 'en', $dialect = 'us', $voice = 'callie', $rate = '8000'): array
	{
		//define an empty array
		$array = [];

		//set the variables
		$switchSoundsDir = Setting::getSetting('switch', 'sounds', 'dir');

		if (!empty($switchSoundsDir) && file_exists($switchSoundsDir))
		{
			$dir = $switchSoundsDir . '/' . $language . '/' . $dialect . '/' . $voice;

			$files = findInDirectory($dir . '/*/' . $rate, true);
		}

		//loop through the languages
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				$file = substr($file, strlen($dir) + 1);
				$file = str_replace("/" . $rate, "", $file);
				$array[] = $file;
			}
		}

		//return the list of sounds
		return $array;
	}
}

if (!function_exists('is_mac')) {
    function is_mac($str)
    {
        return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $str) == 1) ? true : false;
    }
}

if (!function_exists('format_mac')) {
    function format_mac($str, $delim = '-', $case = 'lower')
    {
        if (is_mac($str)) {
            $str = join($delim, str_split($str, 2));
            $str = ($case == 'upper') ? strtoupper($str) : strtolower($str);
        }
        return $str;
    }
}

if(!function_exists('array_find')) {
    function array_find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return null;
    }
}

if (!function_exists('currency_select')) {
	function currency_select($currency = '', $p100 = 0, $name='currency'){

        $billingCurrency = Setting::getSetting('billing', 'currency', 'text');

		if (strlen(trim($currency))== 0){
			$currency = (strlen($billingCurrency) ? $billingCurrency : 'USD');
		}

        $options = config('currencies');

        if ($p100) {
            $options[] = '%';
        }

        echo "<select class='form-select' name=\"$name\" id=\"$name\">";

        foreach ($options as $code) {
            $selected = $currency === $code ? ' selected="selected"' : '';

            echo "<option value=\"$code\"$selected>$code</option>";
        }

        echo '</select>';
	}
}
