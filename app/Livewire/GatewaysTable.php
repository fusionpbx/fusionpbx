<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Gateway;
use App\Http\Controllers\FreeSWITCHAPIController;
use Illuminate\Support\Str;

class GatewaysTable extends DataTableComponent
{
    protected $model = Gateway::class;

    public $gatewayStatuses = [];

    protected $listeners = ['refreshGatewayStatus' => 'getGatewayStatuses'];

    public function boot(): void
    {
        //To do: Uncomment once FreeSwitchAPIController is working.
        // $this->getGatewayStatuses();
    }

    public function getGatewayStatuses() 
    {
        $fsapi = new FreeSWITCHAPIController();
        $gateways = Gateway::all();

        foreach ($gateways as $gateway) {

            $response = $fsapi->execute('sofia', 'xmlstatus gateway ' . $gateway->gateway_uuid);

            if ($response == "Invalid Gateway!") {
                $this->gatewayStatuses[$gateway->gateway_uuid] = [
                    'status' => 'stopped',
                    'state' => null
                ];
            } else {
                try {
                    $xml = new \SimpleXMLElement($response);
                    $state = (string)$xml->state;
                    $this->gatewayStatuses[$gateway->gateway_uuid] = [
                        'status' => 'running',
                        'state' => $state
                    ];
                } catch (\Exception $e) {
                    $this->gatewayStatuses[$gateway->gateway_uuid] = [
                        'status' => 'error',
                        'state' => null
                    ];
                }
            }
        }
    }

    public function startGateway($gatewayUuid)
    {
        $fsapi = new FreeSWITCHAPIController();
        $response = $fsapi->execute('sofia', 'profile external rescan');

        $this->getGatewayStatuses();

        session()->flash('message', 'Gateway started successfully');
    }

    public function stopGateway($gatewayUuid)
    {
        $fsapi = new FreeSWITCHAPIController();
        $response = $fsapi->execute('sofia', 'profile external killgw ' . $gatewayUuid);

        $this->getGatewayStatuses();

        session()->flash('message', 'Gateway stopped successfully');
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('gateway_edit')) {
            $bulkActions['toggleGateway'] = 'Toggle';
        }
        
        if(auth()->user()->hasPermission('gateway_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        if (auth()->user()->hasPermission('gateway_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        return $bulkActions;
    }

    public function toggleGateway()
    {
        $selectedRow = $this->getSelected();
    
        Gateway::whereIn('gateway_uuid', $selectedRow)
            ->update([
                'enabled' => DB::raw("CASE WHEN enabled = 'true' THEN 'false' ELSE 'true' END")
            ]);
    
        session()->flash('message', 'Gateway status toggled successfully');
    }


    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('gateway_add')) {
            session()->flash('error', 'You do not have permission to copy groups.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $gatewayUuid) {
                $originalGateway = Gateway::findOrFail($gatewayUuid);

                $newGateway = $originalGateway->replicate();
                $newGateway->gateway_uuid = Str::uuid();
                $newGateway->description = $newGateway->description . ' (Copy)';
                $newGateway->save();

            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('group_delete')) {
            session()->flash('error', 'You do not have permission to delete groups.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Gateway::whereIn('gateway_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Groups successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the groups: ' . $e->getMessage());
        }
    }


    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('gateway_edit');

        $tableConfig = $this->setPrimaryKey('gateway_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Gateways')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('gateways.edit', $row->gateway_uuid);
            });
        }
    }

    public function columns(): array
    {
        $canEdit = auth()->user()->hasPermission('gateway_edit');
        $canDelete = auth()->user()->hasPermission('gateway_delete');

        $columns = [
            Column::make("Gateway", "gateway")
                ->sortable()
                ->searchable(),
            Column::make("Proxy", "proxy")
                ->sortable()
                ->searchable(),
            Column::make("Context", "context")
                ->sortable()
                ->searchable(),
            Column::make("Register", "register")
                ->sortable()
                ->searchable()
        ];

        // To do: Uncomment once FreeSwitchAPIController is working.
        // $columns[] = Column::make("Status", "gateway_uuid")
        //     ->format(function($value, $row, Column $column) {
        //         $status = $this->gatewayStatuses[$value]['status'] ?? 'unknown';

        //         if ($status == 'running') {
        //             return '<span class="badge badge-success">Running</span>';
        //         } elseif ($status == 'stopped') {
        //             return '<span class="badge badge-danger">Stopped</span>';
        //         } else {
        //             return '<span class="badge badge-warning">Unknown</span>';
        //         }
        //     })
        //     ->html();

        $columns[] = Column::make("Status", "gateway_uuid")
            ->format(function ($value, $row, Column $column) {
                return '<span class="">Stopped</span>'; 
            })
            ->html();


            //To do: Uncomment once FreeSwitchAPIController is working.
            // $columns[] = Column::make("Action", "gateway_uuid")
            //     ->format(function ($value, $row, Column $column) {
            //         $status = $this->gatewayStatuses[$value]['status'] ?? 'unknown';

            //         if ($status == 'running') {
            //             return '<button wire:click="stopGateway(\'' . $value . '\')" class="btn btn-sm btn-danger">Stop</button>';
            //         } elseif ($status == 'stopped') {
            //             return '<button wire:click="startGateway(\'' . $value . '\')" class="btn btn-sm btn-success">Start</button>';
            //         } else {
            //             return '<button class="btn btn-sm btn-secondary" disabled>Unknown</button>';
            //         }
            //     })
            //     ->html();

            $columns[] = Column::make("Action", "gateway_uuid")
                ->format(function ($value, $row, Column $column) {
                    return '<a class="btn btn-sm btn-primary" disabled>Start</a>';
                })
                ->html();


        $columns[] = Column::make("State", "gateway_uuid")
            ->format(function ($value, $row, Column $column) {
                $state = $this->gatewayStatuses[$value]['state'] ?? '';
                return $state ?: '&nbsp;';
            })
            ->html();

        $columns[] = Column::make("Hostname", "hostname")
            ->sortable()
            ->searchable();

        // To do: Uncomment once FreeSwitchAPIController is working.
        // $columns[] = Column::make("Enabled", "enabled")
        //     ->format(function ($value, $row, Column $column) use ($canEdit) {
        //         if ($canEdit) {
        //             return '<button wire:click="toggleGateway(\'' . $row->gateway_uuid . '\')" class="btn btn-sm ' .
        //                 ($value == 'true' ? 'btn-success' : 'btn-danger') . '">' .
        //                 ($value == 'true' ? 'True' : 'False') . '</button>';
        //         } else {
        //             return $value == 'true' ? 'True' : 'False';
        //         }
        //     })
        //     ->html();

        $columns[] = Column::make("Enabled", "enabled")
            ->sortable()
            ->searchable();

        $columns[] = Column::make("Description", "description")
            ->sortable()
            ->searchable();

        return $columns;
    }


    public function builder(): Builder
    {
        $query = Gateway::query();
    
        $showAll = request()->query('show') === 'all';
    
        if (!$showAll && !auth()->user()->hasPermission('gateway_all')) {
            $query->where(function ($q) {
                $q->where('domain_uuid', auth()->user()->domain_uuid)
                    ->orWhereNull('domain_uuid');
            });
        }
    
        return $query;
    }
}
