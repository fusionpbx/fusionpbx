<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Http\Requests\ExtensionRequest;
use App\Repositories\ExtensionRepository;
use Illuminate\Http\Request;

class ExtensionAPIController extends Controller
{
	protected $extensionRepository;

	public function __construct(ExtensionRepository $extensionRepository)
	{
		$this->extensionRepository = $extensionRepository;
	}

	public function mine(){
        return response()->json(["data" => $this->extensionRepository->mine()]);
    }

	public function index()
	{
        return response()->json($this->extensionRepository->all());
	}

    // TODO:
    public function store(ExtensionRequest $request)
	{
		$newExtension = $this->extensionRepository->create($request->validated());
        return response()->json($newExtension);
	}

	public function show(Extension $extension)
	{
		$d = $this->extensionRepository->findByUuid($extension->domain_uuid, true);
        return response()->json($d);
	}

	public function update(ExtensionRequest $request, Extension $extension)
	{
		$d = $this->extensionRepository->update($extension, $request->validated());
		return response()->json($d);
	}

	public function destroy(Extension $extension)
	{
		$d = $this->extensionRepository->delete($extension);
        return response()->json($d);
	}

	public function switch(Request $request)
	{
		ExtensionService::switchByUuid($request->domain_uuid);

		$url = url()->previous();
		return redirect($url);
	}
}
