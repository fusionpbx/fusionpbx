<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;
use App\Http\Requests\DomainRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DomainController extends Controller
{
	public function index()
	{
		$domains = Domain::all();

		return view("domain/index", compact("domains"));
	}

	public function create()
	{
		$domain = new Domain();

        $domains = Domain::all();

		return view("domain/form", compact("domain", "domains"));
	}

	public function store(DomainRequest $request)
	{
		$validated = $request->validated();

		Domain::create($validated);

		return redirect()->route("domain.index")->with("success", "Domain created successfully!");
	}

	public function edit($domain_uuid)
	{
		$domain = Domain::findOrFail($domain_uuid);

        $domains = Domain::all();

		return view("domain/form", compact("domain", "domains"));
	}

	public function update(Request $request, $domain_uuid)
	{
		$domain = Domain::findOrFail($domain_uuid);

        $validated = $request->validated();

		$domain->update($validated);

		return redirect()->route("domain.edit", $domain_uuid)->with("success", "Domain updated successfully!");
	}

	public function destroy($domain_uuid)
	{
		$domain = Domain::findOrFail($domain_uuid);

		$domain->delete();

		return redirect()->route("domain.index")->with("success", "Domain deleted successfully!");
	}

    public function switch(Request $request){
        return $this->switch_by_uuid($request->domain_uuid);

    }

    public function switch_by_uuid(string $domain_uuid){

         $domain_query = Domain::where('domain_uuid', $domain_uuid)
                                ->where('domain_enabled', 'true');

        if ($domain_query->count() > 0){
            $domain = $domain_query->first();
            if (Session::get('domain_uuid') != $domain->domain_uuid){

                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                };
                Session::put('domain_uuid', $domain->domain_uuid);
                Session::put('domain_name', $domain->domain_name);
                Session::put('domain_description', !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name);
                // TODO: Check if _SESSION is right
                $_SESSION["domain_name"] = $domain->domain_name;
                $_SESSION["domain_uuid"] = $domain->domain_uuid;
                $_SESSION["domain_description"] = !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name;

                //set the context
                Session::put('context', $_SESSION["domain_name"]);
                $_SESSION["context"] = $_SESSION["domain_name"];

                // unset destinations belonging to old domain
                unset($_SESSION["destinations"]["array"]);
            }
            $url = url()->previous();
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

    // returns all the available domains
    public function select_control(): mixed{
        // FIX ME
        $db_type = DB::getConfig("driver");
        $sql = "WITH RECURSIVE children AS (
                    SELECT d.domain_uuid, d.domain_parent_uuid, d.domain_name, ".($db_type == 'pgsql'?"CAST(d.domain_enabled AS text)":"d.domain_enabled").", d.domain_description, '' AS parent_domain_name, 1 AS depth, domain_name AS path, (SELECT COUNT(*) FROM ".Domain::getTableName()." d1 d1.domain_parent_uuid = d.domain_uuid) AS kids FROM ".Domain::getTableName()." d
                    WHERE ";

        if (can('domain_select')){
            // if permission domain_select
            $sql .= "d.domain_parent_uuid IS null OR NOT exists (SELECT 1 FROM ".Domain::getTableName()." t1 WHERE d.domain_parent_uuid = t1.domain_uuid) ";
        }
        else {
            // if NOT permission domain_select
            $sql .= "domain_uuid = '".Session::get('domain_uuid')."' ";
        }

        $sql .= "UNION
        SELECT tp.domain_uuid, tp.domain_parent_uuid, tp.domain_name, ".($db_type == 'pgsql'?"CAST(tp.domain_enabled AS text)":"tp.domain_enabled").", tp.domain_description, c.domain_name AS parent_domain_name, depth + 1, CONCAT(path,';',tp.domain_name), (SELECT count(*) from ".Domain::getTableName()." d1 where d1.domain_parent_uuid = tp.domain_uuid) AS kids FROM ".Domain::getTableName()." tp
                JOIN children c ON tp.domain_parent_uuid = c.domain_uuid ) SELECT * FROM children ";

        // Where

        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $sql: '.$sql);
        }

        $domains = DB::select($sql);
        return $domains;
    }
}
