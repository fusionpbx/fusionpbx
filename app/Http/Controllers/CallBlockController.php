<?php
namespace App\Http\Controllers;

use App\Http\Requests\CallBlockRequest;
use App\Models\CallBlock;
use App\Models\Extension;
use App\Repositories\CallBlockRepository;

class CallBlockController extends Controller
{
	protected $callBlockRepository;

	public function __construct(CallBlockRepository $callBlockRepository)
	{
		$this->callBlockRepository = $callBlockRepository;
	}
	public function index()
	{
		return view('pages.callblocks.index');
	}

	public function create()
	{
		$extensions = Extension::all();

		return view("pages.callblocks.form", compact("extensions"));
	}

	public function store(CallBlockRequest $request)
	{
		$callblock = $this->callBlockRepository->create($request->validated());

		return redirect()->route("callblocks.edit", $callblock->callblock_uuid);
	}

    public function show(CallBlock $callblock)
    {
        //
    }

	public function edit(CallBlock $callblock)
	{
		$extensions = Extension::all();

		return view("pages.callblocks.form", compact("callblock", "extensions"));
	}

	public function update(CallBlockRequest $request, CallBlock $callblock)
	{
		$this->callBlockRepository->update($callblock, $request->validated());

        return redirect()->route("callblocks.edit", $callblock->call_block_uuid);
	}

    public function destroy(CallBlock $callblock)
    {
        $this->callBlockRepository->delete($callblock);

        return redirect()->route('callblocks.index');
    }
}
