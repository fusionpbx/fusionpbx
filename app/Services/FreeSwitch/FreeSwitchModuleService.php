<?php

namespace App\Services\FreeSwitch;

use App\Facades\FreeSwitch;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class FreeSwitchModuleService
{
    protected FreeSwitchService $freeSwitchService;

    public function __construct(FreeSwitchService $freeSwitchService)
    {
        $this->freeSwitchService = $freeSwitchService;
    }

    public function getModuleStatus(string $module_name): bool
    {
		$command = "api module_exists " . $module_name;

		$response = FreeSwitch::execute($command);

		return ($response == "true");
	}
}
