<?php

namespace App\Http\Controllers\API;

use App\Facades\DomainService;
use App\Models\Domain;
use App\Http\Requests\DomainRequest;
use App\Repositories\DomainRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DomainAPIController extends Controller
{
	protected $domainRepository;

	public function __construct(DomainRepository $domainRepository)
	{
		$this->domainRepository = $domainRepository;
	}

	public function mine(){
        $domains = $this->domainRepository->mine();
        return response()->json($domains);
    }

	public function index()
	{
        $domains = $this->domainRepository->all();
        return response()->json($domains);
	}

	public function store(DomainRequest $request)
	{
		$newDomain = $this->domainRepository->create($request->validated());
        return response()->json($newDomain);
	}

	public function show(Domain $domain)
	{
		$d = $this->domainRepository->findByUuid($domain->domain_uuid, true);
        return response()->json($d);
	}

	public function update(DomainRequest $request, Domain $domain)
	{
		$d = $this->domainRepository->update($domain, $request->validated());
		return response()->json($d);
	}

	public function destroy(Domain $domain)
	{
		$d = $this->domainRepository->delete($domain);
        return response()->json($d);
	}

	public function switch(Request $request)
	{
		DomainService::switchByUuid($request->domain_uuid);

		$url = url()->previous();
		return redirect($url);
	}
}
