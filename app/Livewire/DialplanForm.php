<?php

namespace App\Livewire;

use App\Http\Requests\DialplanRequest;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Dialplan;
use App\Models\DialplanDetail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use XMLWriter;

class DialplanForm extends Component
{
    public $dialplan;
    public string $dialplan_uuid;
	public ?string $domain_uuid = '';
	public ?string $app_uuid = '';
    public ?string $hostname = '';
    public ?string $dialplan_context = '';
    public string $dialplan_name = '';
    public ?string $dialplan_number = '';
	public bool $dialplan_destination = true;
    public bool $dialplan_continue = true;
	public ?int $dialplan_order = 0;
    public bool $dialplan_enabled = true;
    public ?string $dialplan_description = '';

    public ?array $dialplanDetails = [];

    public array $dialplanDetailsToDelete = [];

    public bool $canViewDialplanDetail = false;
    public bool $canAddDialplanDetail = false;
    public bool $canEditDialplanDetail = false;
    public bool $canDeleteDialplanDetail = false;

    public $dialplan_default_context = '';
    public $domains = [];
    public $types = [];

    public function rules()
    {
        $request = new DialplanRequest();

        return $request->rules();
    }

    public function mount($dialplan = null, $domains = [], $types = [], $dialplan_default_context = ''): void
    {
        $this->domains = $domains;
        $this->types = $types;
        $this->dialplan_default_context = $dialplan_default_context;

        if ($dialplan)
        {
            $this->dialplan = $dialplan;
			$this->domain_uuid = $dialplan->domain_uuid;
            $this->dialplan_uuid = $dialplan->dialplan_uuid;
            $this->hostname = $dialplan->hostname;
            $this->dialplan_context = $dialplan->dialplan_context;
            $this->dialplan_name = $dialplan->dialplan_name;
            $this->dialplan_number = $dialplan->dialplan_number;
			$this->dialplan_destination = $dialplan->dialplan_destination ?? false;
			$this->dialplan_continue = $dialplan->dialplan_continue ?? false;
			$this->dialplan_order = $dialplan->dialplan_order;
            $this->dialplan_enabled = $dialplan->dialplan_enabled ?? false;
            $this->dialplan_description = $dialplan->dialplan_description;

            foreach ($dialplan->dialplanDetails as $dialplanDetail)
            {
                $this->dialplanDetails[] = [
                    'dialplan_detail_uuid' => $dialplanDetail->dialplan_detail_uuid,
                    'dialplan_detail_tag' => $dialplanDetail->dialplan_detail_tag,
                    'dialplan_detail_type' => $dialplanDetail->dialplan_detail_type,
                    'dialplan_detail_data' => $dialplanDetail->dialplan_detail_data,
                    'dialplan_detail_break' => $dialplanDetail->dialplan_detail_break,
                    'dialplan_detail_inline' => $dialplanDetail->dialplan_detail_inline,
                    'dialplan_detail_group' => $dialplanDetail->dialplan_detail_group,
                    'dialplan_detail_order' => $dialplanDetail->dialplan_detail_order,
                    'dialplan_detail_enabled' => $dialplanDetail->dialplan_detail_enabled,
                ];
            }
        }

        $this->loadPermissions();

        if (empty($this->dialplanDetails) && $this->canAddDialplanDetail)
        {
            $this->addDialplanDetail();
        }
    }

    private function loadPermissions(): void
    {
        $user = auth()->user();

        $this->canViewDialplanDetail = $user->hasPermission('dialplan_detail_view');
        $this->canAddDialplanDetail = $user->hasPermission('dialplan_detail_add');
        $this->canEditDialplanDetail = $user->hasPermission('dialplan_detail_edit');
        $this->canDeleteDialplanDetail = $user->hasPermission('dialplan_detail_delete');
    }

    public function addDialplanDetail(): void
    {
        if (!$this->canAddDialplanDetail)
        {
            session()->flash('error', 'You do not have permission to add dialplan details.');

            return;
        }

        $this->dialplanDetails[] = [
            'dialplan_detail_uuid' => '',
            'dialplan_detail_tag' => '',
            'dialplan_detail_type' => '',
            'dialplan_detail_data' => '',
            'dialplan_detail_break' => '',
            'dialplan_detail_inline' => '',
            'dialplan_detail_group' => '',
            'dialplan_detail_order' => '',
            'dialplan_detail_enabled' => '',
        ];
    }

    public function removeDialplanDetail($index): void
    {
        if (!$this->canDeleteDialplanDetail)
        {
            session()->flash('error', 'You do not have permission to delete dialplan detail.');
            return;
        }

        if (isset($this->dialplanDetails[$index]['dialplan_detail_uuid']) && !empty($this->dialplanDetails[$index]['dialplan_detail_uuid']))
        {
            $this->dialplanDetailsToDelete[] = $this->dialplanDetails[$index]['dialplan_detail_uuid'];
        }

        unset($this->dialplanDetails[$index]);

        $this->dialplanDetails = array_values($this->dialplanDetails);
    }

    public function save(): void
    {
        $this->validate();

        $filteredDialplanDetails = collect($this->dialplanDetails)->filter(function ($dialplanDetail)
        {
            return !empty($dialplanDetail['dialplan_detail_tag']);
        })->toArray();

        $hasNewDialplanDetails = collect($filteredDialplanDetails)->filter(fn($d) => empty($d['dialplan_detail_uuid']))->count() > 0;

        if ($hasNewDialplanDetails && !$this->canAddDialplanDetail)
        {
            session()->flash('error', 'You do not have permission to add dialplanDetails.');
        }

        if (!empty($this->dialplanDetailsToDelete) && !$this->canDeleteDialplanDetail)
        {
            session()->flash('error', 'You do not have permission to delete dialplanDetails.');
        }

        if ($this->dialplan)
        {
            $this->dialplan->update([
                'domain_uuid' => $this->domain_uuid,
                'hostname' => $this->hostname,
                'dialplan_context' => $this->dialplan_context,
                'dialplan_name' => $this->dialplan_name,
                'dialplan_number' => $this->dialplan_number,
                'dialplan_destination' => $this->dialplan_destination,
                'dialplan_continue' => $this->dialplan_continue,
                'dialplan_order' => $this->dialplan_order,
                'dialplan_enabled' => $this->dialplan_enabled ,
                'dialplan_description' => $this->dialplan_description,
            ]);

            foreach ($filteredDialplanDetails as $dialplanDetail)
            {
                if (empty($dialplanDetail['dialplan_detail_uuid']))
                {
                    DialplanDetail::create([
                        'dialplan_detail_uuid' => Str::uuid(),
                        'dialplan_uuid' => $this->dialplan->dialplan_uuid,
                        'dialplan_detail_tag' => $dialplanDetail['dialplan_detail_tag'],
                        'dialplan_detail_type' => $dialplanDetail['dialplan_detail_type'],
                        'dialplan_detail_data' => $dialplanDetail['dialplan_detail_data'],
                        'dialplan_detail_break' => $dialplanDetail['dialplan_detail_break'],
                        'dialplan_detail_inline' => $dialplanDetail['dialplan_detail_inline'],
                        'dialplan_detail_group' => $dialplanDetail['dialplan_detail_group'],
                        'dialplan_detail_order' => $dialplanDetail['dialplan_detail_order'],
                        'dialplan_detail_enabled' => $dialplanDetail['dialplan_detail_enabled'],
                    ]);
                }
                else
                {
                    DialplanDetail::where('dialplan_detail_uuid', $dialplanDetail['dialplan_detail_uuid'])
                        ->update([
                            'dialplan_detail_tag' => $dialplanDetail['dialplan_detail_tag'],
                            'dialplan_detail_type' => $dialplanDetail['dialplan_detail_type'],
                            'dialplan_detail_data' => $dialplanDetail['dialplan_detail_data'],
                            'dialplan_detail_break' => $dialplanDetail['dialplan_detail_break'],
                            'dialplan_detail_inline' => $dialplanDetail['dialplan_detail_inline'],
                            'dialplan_detail_group' => $dialplanDetail['dialplan_detail_group'],
                            'dialplan_detail_order' => $dialplanDetail['dialplan_detail_order'],
                            'dialplan_detail_enabled' => $dialplanDetail['dialplan_detail_enabled'],
                        ]);
                }
            }

            if (!empty($this->dialplanDetailsToDelete))
            {
                DialplanDetail::whereIn('dialplan_detail_uuid', $this->dialplanDetailsToDelete)->delete();
            }

            session()->flash('message', 'Dialplan updated successfully.');
        }
        else
        {
            $this->dialplan = Dialplan::create([
                'dialplan_uuid' => Str::uuid(),
                'domain_uuid' => $this->domain_uuid,
                'hostname' => $this->hostname,
                'dialplan_context' => $this->dialplan_context,
                'dialplan_name' => $this->dialplan_name,
                'dialplan_number' => $this->dialplan_number,
                'dialplan_destination' => $this->dialplan_destination,
                'dialplan_continue' => $this->dialplan_continue,
                'dialplan_order' => $this->dialplan_order,
                'dialplan_enabled' => $this->dialplan->dialplan_enabled,
                'dialplan_description' => $this->dialplan_description,
            ]);

            foreach ($filteredDialplanDetails as $dialplanDetail)
            {
                DialplanDetail::create([
                    'dialplan_detail_uuid' => Str::uuid(),
                    'dialplan_uuid' => $this->dialplan->dialplan_uuid,
                    'dialplan_detail_tag' => $dialplanDetail['dialplan_detail_tag'],
                    'dialplan_detail_type' => $dialplanDetail['dialplan_detail_type'],
                    'dialplan_detail_data' => $dialplanDetail['dialplan_detail_data'],
                    'dialplan_detail_break' => $dialplanDetail['dialplan_detail_break'],
                    'dialplan_detail_inline' => $dialplanDetail['dialplan_detail_inline'],
                    'dialplan_detail_group' => $dialplanDetail['dialplan_detail_group'],
                    'dialplan_detail_order' => $dialplanDetail['dialplan_detail_order'],
                    'dialplan_detail_enabled' => $dialplanDetail['dialplan_detail_enabled'],
                ]);
            }

            session()->flash('message', 'Dialplan created successfully.');
        }

        $this->dialplan->update([
            'app_uuid' => '90b0e24e-8014-4424-a606-06ea2f5e60c1',
            'dialplan_xml' => $this->buildXML($this->dialplan),
        ]);

        redirect()->route('dialplans.index');
    }

    public function render(): View
    {
        return view('livewire.dialplan-form');
    }

private function buildXML(Dialplan $dialplan)
	{
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->setIndentString('  ');

		$xml->startElement("extension");
		$xml->writeAttribute("name", $dialplan->dialplan_name);
		$xml->writeAttribute("uuid", $dialplan->dialplan_uuid);
		$xml->writeAttribute("continue", $dialplan->dialplan_continue);

		$currentCondition = null;

		foreach($dialplan->dialplanDetails as $dialplanDetail)
		{
			$tag = $dialplanDetail->dialplan_detail_tag;

			if($tag === "condition")
			{
				if($currentCondition !== null)
				{
					$xml->endElement();
				}

				$currentCondition = $dialplanDetail;

				$xml->startElement("condition");
				$xml->writeAttribute("field", htmlspecialchars($dialplanDetail->dialplan_detail_type));
				$xml->writeAttribute("expression", htmlspecialchars($dialplanDetail->dialplan_detail_data));
				$xml->writeAttribute("break", $dialplanDetail->dialplan_detail_break);
			}
			else
			{
				$xml->startElement($tag);
				$xml->writeAttribute("application", htmlspecialchars($dialplanDetail->dialplan_detail_type));
				$xml->writeAttribute("data", htmlspecialchars($dialplanDetail->dialplan_detail_data));

				if(isset($dialplanDetail->dialplan_detail_inline))
				{
					$xml->writeAttribute("inline", $dialplanDetail->dialplan_detail_inline);
				}

				$xml->endElement();
			}
		}

		if($currentCondition !== null)
		{
			$xml->endElement();
		}

		$xml->endElement();

		return $xml->outputMemory();
	}
}
