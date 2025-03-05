<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;

class DomainController extends Controller
{
    //

    public function switch(Request $request){
        return $this->switch_by_uuid($request->domain_uuid);

    }

    public function switch_by_uuid(string $domain_uuid){
         $domain = Domain::where('domain_uuid', $domain_uuid)->first();

        if (Session::get('domain_uuid') != $domain->uuid){
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            };
            Session::put('domain_uuid', $domain->domain_uuid);
            Session::put('domain_name', $domain->domain_name);
            Session::put('domain_description', !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name);
            $_SESSION["domain_name"] = $domain->domain_name;
            $_SESSION["domain_uuid"] = $domain->domain_uuid;
            $_SESSION["domain_description"] = !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name;

            //set the context
            Session::put('context', $_SESSION["domain_name"]);
            $_SESSION["context"] = $_SESSION["domain_name"];

            // unset destinations belonging to old domain
            unset($_SESSION["destinations"]["array"]);

            $url = getFusionPBXPreviousURL(url()->previous());
            return redirect($url);
        }
    }

    public function default_setting(string $category, string $subcategory, ?string $name = null){
        $dds = new DomainSettingController;
        $setting = $dds->get($category, $subcategory, $name);
        if (!isset($setting)){
            $ds = new DefaultSettingController;
            $setting = $ds->get($category, $subcategory, $name);
        }

        return $ds ?? null;
    }
}
