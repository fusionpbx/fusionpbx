<?php

namespace App\Http\Controllers;

use App\Facades\DefaultSetting;
use App\Facades\Domain as FacadesDomain;
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
		return FacadesDomain::switchByUuid($request->domain_uuid);

	}
}
