<?php

namespace App\Repositories;

use App\Facades\FreeSwitch;
use App\Facades\Setting;
use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use XMLWriter;

class ModuleRepository
{
    protected $model;

    public function __construct(Module $module)
    {
        $this->model = $module;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?Module
    {
        return $this->model->where('module_uuid', $uuid)->first();
    }

    public function create(array $data): Module
    {
        if(!isset($data['module_uuid']))
        {
            $data['module_uuid'] = Str::uuid();
        }

        return $this->model->create($data);
    }

    public function update(Module $module, array $data): bool
    {
        return $module->update($data);
    }

    public function delete(Module $module): ?bool
    {
        return $module->delete();
    }

    public function saveXML()
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

        $xmlPayload = $xml->outputMemory();
        if(App::hasDebugModeEnabled())
        {
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $xmlPayload = '. $xmlPayload);
        }

		$xml->flush();

        $this->reloadXML();
    }

	public function reloadXML()
    {
        $command = "reloadxml"; $status = false;

        try
        {
		    $response = FreeSwitch::execute($command);
            if(App::hasDebugModeEnabled())
            {
                Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $response = '. $response);
            }

            $status = (substr($response,0,3) == "+OK");
        }
        catch(\Exception $e)
        {
            throw $e;

            if(App::hasDebugModeEnabled())
            {
                Log::error('[' . __CLASS__ . '][' . __METHOD__ . ']: ' . $e->getMessage());
            }
        }
        finally
        {
            return $status;
        }
    }
}
