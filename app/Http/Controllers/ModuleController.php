<?php
namespace App\Http\Controllers;

use App\Facades\FreeSwitchModule;
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
		$modules = Module::orderBy("module_category")->orderBy("module_label")->get();

		foreach($modules as $module)
		{
			$module->module_status = FreeSwitchModule::getModuleStatus(trim($module->module_name));
		}

		return view("pages.modules.index", compact("modules"));
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

    public function start(?Module $module = null)
    {
		$message = "";

		$modules = $module ? collect([$module]) : Module::all();

		foreach($modules as $module)
		{
			$response = FreeSwitchModule::startModule($module->module_name);

			if(!empty($response))
			{
				$message .= "<br><strong>" . $response . "</strong>";
			}
		}

		if(!empty($message))
		{
			session()->flash('success', $message);
		}

		return redirect()->route('modules.index');
    }

    public function stop(?Module $module = null)
    {
		$message = "";

		$modules = $module ? collect([$module]) : Module::all();

		foreach($modules as $module)
		{
			$response = FreeSwitchModule::stopModule($module->module_name);

			if(!empty($response))
			{
				$message .= "<br><strong>" . $response . "</strong>";
			}
		}

		if(!empty($message))
		{
			session()->flash('success', $message);
		}

		return redirect()->route('modules.index');
    }
}
