<?php
namespace App\Http\Controllers;

use App\Http\Requests\ModuleRequest;
use App\Models\Module;
use App\Repositories\ModuleRepository;

class ModuleController extends Controller
{
	protected $moduleRepository;

	public function __construct(ModuleRepository $moduleRepository)
	{
		$this->moduleRepository = $moduleRepository;
	}
	public function index()
	{
		return view('pages.modules.index');
	}

	public function create()
	{
		$categories = Module::categories();

		return view("pages.modules.form", compact("categories"));
	}

	public function store(ModuleRequest $request)
	{
		$module = $this->moduleRepository->create($request->validated());

		return redirect()->route("modules.edit", $module->module_uuid);
	}

    public function show(Module $module)
    {
        //
    }

	public function edit(Module $module)
	{
		$categories = Module::categories();

		return view("pages.modules.form", compact("module", "categories"));
	}

	public function update(ModuleRequest $request, Module $module)
	{
		$this->moduleRepository->update($module, $request->validated());

        return redirect()->route("modules.edit", $module->module_uuid);
	}

    public function destroy(Module $module)
    {
        $this->moduleRepository->delete($module);

        return redirect()->route('modules.index');
    }
}
