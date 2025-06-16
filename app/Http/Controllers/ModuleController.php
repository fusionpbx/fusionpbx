<?php
namespace App\Http\Controllers;

use App\Facades\FreeSwitchModule;
use App\Facades\Setting;
use App\Http\Requests\ModuleRequest;
use App\Models\Module;
use App\Repositories\ModuleRepository;
use Illuminate\Support\Facades\Session;

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
		$message = "Modules started: <br>";

		$modules = $module ? collect([$module]) : Module::all();

		foreach($modules as $module)
		{
			$response = FreeSwitchModule::startModule($module->module_name);

			$message .= "<br>" . $module->module_name . ": " . ($response) ? "success" : "failed";
		}

		if(!empty($message))
		{
			session()->flash('success', $message);
		}

		$this->saveXML();

		return redirect()->route('modules.index');
    }

    public function stop(?Module $module = null)
    {
		$message = "Modules stopped: <br>";

		$modules = $module ? collect([$module]) : Module::all();

		foreach($modules as $module)
		{
			$response = FreeSwitchModule::stopModule($module->module_name);

			if(!empty($response))
			{
				$message .= "<br>" . $module->module_name . ": " . ($response) ? "success" : "failed";
			}
		}

		if(!empty($message))
		{
			session()->flash('success', $message);
		}

		$this->saveXML();

		return redirect()->route('modules.index');
    }

	private function saveXML()
	{
		$switchConfDir = Setting::getSetting("switch", "conf", "dir");

		if(!file_exists($switchConfDir . "/autoload_configs"))
		{
			return;
		}

		$modules = Module::orderBy("module_order")
			->orderBy("module_category")
			->orderBy("module_label")
			->get();

		$filePath = $switchConfDir . "/autoload_configs/modules.conf.xml";

		$xml = new \XMLWriter();
		$xml->openURI($filePath);
		$xml->setIndent(true);
		$xml->setIndentString("    ");
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('configuration');
		$xml->writeAttribute('name', 'modules.conf');
		$xml->writeAttribute('description', 'Modules');

		$xml->startElement('modules');

		$prevModuleCategory = null;

		foreach($modules as $module)
		{
			if($prevModuleCategory !== $module->module_category)
			{
				$xml->text("\n");
				$xml->writeComment(" " . $module->module_category . " ");
			}

			if($module->module_enabled == "true")
			{
				$xml->startElement("load");
				$xml->writeAttribute("module", $module->module_name);
				$xml->endElement();
			}

			$prevModuleCategory = $module->module_category;
		}

		$xml->endElement();
		$xml->endElement();
		$xml->endDocument();

		$xml->flush();

		Session::put("reload_xml", true);
	}
}
