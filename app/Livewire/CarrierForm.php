<?php

namespace App\Livewire;

use App\Http\Requests\CarrierRequest;
use App\Repositories\CarrierRepository;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Repositories\CarrierGatewayRepository;
use Illuminate\Contracts\View\View;

class CarrierForm extends Component
{
    public $carrier;
    public string $carrier_uuid;
    public string $carrier_name = '';
    public ?int $carrier_channels = 0;
    public ?int $priority = 0;
    public ?float $cancellation_ratio = 0.0;
    public bool $short_call_friendly = true;
    public bool $fax_enabled = true;
    public ?string $lcr_tags = '';
    public bool $enabled = true;

    public ?array $carrierGateways = [];

    public array $carrierGatewaysToDelete = [];

    public bool $canViewCarrierGateways = false;
    public bool $canAddCarrierGateways = false;
    public bool $canEditCarrierGateways = false;
    public bool $canDeleteCarrierGateways = false;

    public $gateways = [];

    protected $carrierRepository;
    protected $carrierGatewayRepository;

    public function boot(CarrierRepository $carrierRepository, CarrierGatewayRepository $carrierGatewayRepository)
    {
        $this->carrierRepository = $carrierRepository;
        $this->carrierGatewayRepository = $carrierGatewayRepository;
    }

    public function rules()
    {
        $request = new CarrierRequest();
        return $request->rules($this->carrier_uuid);
    }

    public function mount($carrier = null, $gateways = []): void
    {
        $this->gateways = $gateways;

        if ($carrier)
        {
            $this->carrier = $carrier;
            $this->carrier_uuid = $carrier->carrier_uuid;
            $this->carrier_name = $carrier->carrier_name;
            $this->carrier_channels = $carrier->carrier_channels;
            $this->priority = $carrier->priority;
            $this->cancellation_ratio = $carrier->cancellation_ratio;
            $this->short_call_friendly = $carrier->short_call_friendly ?? false;
            $this->fax_enabled = $carrier->fax_enabled ?? false;
            $this->lcr_tags = $carrier->lcr_tags;
            $this->enabled = $carrier->enabled ?? false;

            foreach ($carrier->gateways as $carrierGateway)
            {
                $this->carrierGateways[] = [
                    'carrier_gateway_uuid' => $carrierGateway->carrier_gateway_uuid,
                    'gateway_uuid' => $carrierGateway->gateway_uuid,
                    'prefix' => $carrierGateway->prefix,
                    'suffix' => $carrierGateway->suffix,
                    'priority' => $carrierGateway->priority,
                    'codec' => $carrierGateway->codec,
                    'enabled' => $carrierGateway->enabled,
                ];
            }
        }

        $this->loadPermissions();

        if (empty($this->carrierGateways) && $this->canAddCarrierGateways)
        {
            $this->addCarrierGateway();
        }
    }

    private function loadPermissions(): void
    {
        $user = auth()->user();

        $this->canViewCarrierGateways = $user->hasPermission('carrier_gateway_view');
        $this->canAddCarrierGateways = $user->hasPermission('carrier_gateway_add');
        $this->canEditCarrierGateways = $user->hasPermission('carrier_gateway_edit');
        $this->canDeleteCarrierGateways = $user->hasPermission('carrier_gateway_delete');
    }

    public function addCarrierGateway(): void
    {
        if (!$this->canAddCarrierGateways)
        {
            session()->flash('error', 'You do not have permission to add carrier gateways.');
            return;
        }

        $this->carrierGateways[] = [
            'carrier_gateway_uuid' => '',
            'gateway_uuid' => '',
            'prefix' => '',
            'suffix' => '',
            'codec' => '',
            'priority' => '',
            'enabled' => '',
        ];
    }

    public function removeCarrierGateway($index): void
    {
        if (!$this->canDeleteCarrierGateways)
        {
            session()->flash('error', 'You do not have permission to delete carrier gateway.');
            return;
        }

        if (isset($this->carrierGateways[$index]['carrier_gateway_uuid']) && !empty($this->carrierGateways[$index]['carrier_gateway_uuid']))
        {
            $this->carrierGatewaysToDelete[] = $this->carrierGateways[$index]['carrier_gateway_uuid'];
        }

        unset($this->carrierGateways[$index]);
        $this->carrierGateways = array_values($this->carrierGateways);
    }

    public function save(): void
    {
        $this->validate();

        $filteredCarrierGateways = collect($this->carrierGateways)->filter(function ($carrierGateway)
        {
            return !empty($carrierGateway['gateway_uuid']);
        })->toArray();

        $hasNewCarrierGateways = collect($filteredCarrierGateways)->filter(fn($cg) => empty($cg['carrier_gateway_uuid']))->count() > 0;

        if ($hasNewCarrierGateways && !$this->canAddCarrierGateways)
        {
            session()->flash('error', 'You do not have permission to add carrierGateways.');
            return;
        }

        if (!empty($this->carrierGatewaysToDelete) && !$this->canDeleteCarrierGateways)
        {
            session()->flash('error', 'You do not have permission to delete carrierGateways.');
            return;
        }

        $carrierData = [
            'carrier_name' => $this->carrier_name,
            'carrier_channels' => $this->carrier_channels,
            'priority' => $this->priority,
            'cancellation_ratio' => $this->cancellation_ratio,
            'short_call_friendly' => $this->short_call_friendly,
            'fax_enabled' => $this->fax_enabled,
            'lcr_tags' => $this->lcr_tags,
            'enabled' => $this->enabled,
        ];

        if ($this->carrier)
        {
            $updated = $this->carrierRepository->update($this->carrier, $carrierData);

            if (!$updated)
            {
                session()->flash('error', 'Failed to update carrier.');
                return;
            }

            $this->carrierGatewayRepository->update($this->carrier, $filteredCarrierGateways);

            if (!empty($this->carrierGatewaysToDelete))
            {
                $this->carrierGatewayRepository->delete($this->carrierGatewaysToDelete);
            }

            session()->flash('message', 'Carrier updated successfully.');
        }
        else
        {
            $carrierData['carrier_uuid'] = Str::uuid();
            $this->carrier = $this->carrierRepository->create($carrierData);

            $this->carrierGatewayRepository->create($this->carrier, $filteredCarrierGateways);

            session()->flash('message', 'Carrier created successfully.');
        }

        redirect()->route('carriers.edit', $this->carrier->carrier_uuid);
    }

    public function render(): View
    {
        return view('livewire.carrier-form');
    }
}
