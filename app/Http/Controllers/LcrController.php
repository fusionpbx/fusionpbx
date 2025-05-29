<?php
namespace App\Http\Controllers;

use App\Http\Requests\LcrRequest;
use App\Models\Lcr;
use App\Repositories\LcrRepository;

class LcrController extends Controller
{
	protected $lcrRepository;

	public function __construct(LcrRepository $lcrRepository)
	{
		$this->lcrRepository = $lcrRepository;
	}
	public function index()
	{
		return view('pages.lcrs.index');
	}

	public function create()
	{
		return view("pages.lcrs.form");
	}

	public function store(LcrRequest $request)
	{
		$lcr = $this->lcrRepository->create($request->validated());

		return redirect()->route("lcrs.edit", $lcr->lcr_uuid);
	}

    public function show(Lcr $lcr)
    {
        //
    }

	public function edit(Lcr $lcr)
	{
		return view("pages.lcrs.form", compact("lcr"));
	}

	public function update(LcrRequest $request, Lcr $lcr)
	{
		$this->lcrRepository->update($lcr, $request->validated());

        return redirect()->route("lcrs.edit", $lcr->lcr_uuid);
	}

    public function destroy(Lcr $lcr)
    {
        $this->lcrRepository->delete($lcr);

        return redirect()->route('lcrs.index');
    }
}
