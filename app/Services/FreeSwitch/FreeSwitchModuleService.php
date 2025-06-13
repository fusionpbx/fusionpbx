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
        $status = false;

		$command = "api module_exists " . $module_name;

        try
        {
		    $response = FreeSwitch::execute($command);

            $status = ($response == "true");
        }
        catch(\Exception $e)
        {
            throw $e;

            if(App::hasDebugModeEnabled())
            {
                Log::error('[' . __CLASS__ . '][' . __METHOD__ . ']: ' . $e->getMessage());
            }
        }
        finally
        {
            return $status;
        }
	}
}
