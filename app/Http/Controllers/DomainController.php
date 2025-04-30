<?php

namespace App\Http\Controllers;

use App\Facades\DefaultSetting;
use App\Models\Domain;
use App\Http\Controllers\DomainSettingController;
use App\Http\Requests\DomainRequest;
use App\Repositories\DomainRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DomainController extends Controller
{
	protected $domainRepository;

	public function __construct(DomainRepository $domainRepository)
	{
		$this->domainRepository = $domainRepository;
	}


	public function index()
	{
		return view("pages.domains.index");
	}

	public function create()
	{
		$domains = $this->domainRepository->all();
		return view("pages.domains.form", compact("domains"));
	}

	public function store(DomainRequest $request)
	{
		$this->domainRepository->create($request->validated());

		return redirect()->route("domains.index");
	}

	public function show(Domain $domain)
	{
		//
	}

	public function edit(Domain $domain)
	{
		$domains = $this->domainRepository->all();

		return view("pages.domains.form", compact("domain", "domains"));
	}

	public function update(DomainRequest $request, Domain $domain)
	{
		$this->domainRepository->update($domain, $request->validated());

		return redirect()->route("domains.index");
	}

	public function destroy(Domain $domain)
	{
		$this->domainRepository->delete($domain);

		return redirect()->route('domains.index');
	}

	public function switch(Request $request)
	{
		return $this->switchByUuid($request->domain_uuid);

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

	public function default_setting(string $category, string $subcategory, ?string $name = null)
	{
        $dds = new DomainSettingController;
        $setting = $dds->get($category, $subcategory, $name);
        if (!isset($setting)) {
            $setting = DefaultSetting::get($category, $subcategory, $name);
        }

        return $ds ?? null;
	}

	public function selectControl(): mixed
	{
		return $this->domainRepository->getForSelectControl();
	}
}
