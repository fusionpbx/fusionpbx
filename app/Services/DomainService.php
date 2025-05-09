<?php

namespace App\Services;

use App\Facades\DefaultSetting;
use App\Facades\Setting;
use App\Http\Controllers\DomainSettingController;
use App\Repositories\DomainRepository;
use Illuminate\Support\Facades\Session;

class DomainService
{
    protected $domainRepository;
    protected $settingService;

    public function __construct(DomainRepository $domainRepository)
    {
        $this->domainRepository = $domainRepository;
    }

    public function switchByUuid(string $domain_uuid)
    {
        if ($this->domainRepository->existsByUuid($domain_uuid)) {
            $domain = $this->domainRepository->findByUuid($domain_uuid);

            if (Session::get('domain_uuid') != $domain->domain_uuid) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                };

                Session::put('domain_uuid', $domain->domain_uuid);
                Session::put('domain_name', $domain->domain_name);
                Session::put('domain_description', !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name);

                $_SESSION["domain_name"] = $domain->domain_name;
                $_SESSION["domain_uuid"] = $domain->domain_uuid;
                $_SESSION["domain_description"] = !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name;

                // Set the context
                Session::put('context', $_SESSION["domain_name"]);
                $_SESSION["context"] = $_SESSION["domain_name"];

                // Unset destinations belonging to old domain
                unset($_SESSION["destinations"]["array"]);
            }

            return true;
        }

        return false;
    }


    public function get(string $category, string $subcategory, ?string $name = null)
    {
        return Setting::getSetting($category, $subcategory, $name);
    }

    public function selectControl()
    {
        return $this->domainRepository->getForSelectControl();
    }
}
