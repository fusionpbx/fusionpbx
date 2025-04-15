<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SipProfile;
use App\Models\SipProfileDomain;
use App\Models\SipProfileSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class SipProfileTable extends DataTableComponent
{
    protected $model = SipProfile::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('sip_profile_edit');

        $tableConfig = $this->setPrimaryKey('sip_profile_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])  
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search SIP Profiles')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('sipprofiles.edit', $row->sip_profile_uuid);
            });
        }
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('sip_profile_edit')) {
            $bulkActions['toggleSipProfile'] = 'Toggle';
        }
        
        // if(auth()->user()->hasPermission('sip_profile_add')) {
        //     $bulkActions['bulkCopy'] = 'Copy';
        // }

        if (auth()->user()->hasPermission('sip_profile_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        return $bulkActions;
    }

    public function toggleSipProfile(): void
    {
        if (!auth()->user()->hasPermission('sip_profile_edit')) {
            session()->flash('error', 'You do not have permission to toggle SIP Profile status.');
            return;
        }
        $selectRows = $this->getSelected();

        SipProfile::whereIn('sip_profile_uuid', $selectRows)
            ->update([
                'sip_profile_enabled' => DB::raw("CASE WHEN sip_profile_enabled = 'true' THEN 'false' ELSE 'true' END")
            ]);

        $this->clearSelected();
        $this->dispatch('refresh');
        
        session()->flash('message', 'SIP Profile status toggled successfully');
    }

    public function bulkDelete(): void
    {
        if (!auth()->user()->hasPermission('sip_profile_delete')) {
            session()->flash('error', 'You do not have permission to delete SIP Profiles.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            SipProfileDomain::whereIn('sip_profile_uuid', $selectedRows)->delete();

            SipProfileSetting::whereIn('sip_profile_uuid', $selectedRows)->delete();

            SipProfile::whereIn('sip_profile_uuid', $selectedRows)->delete();


            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;           
        }
    }

    public function columns(): array
    {
        $columns = [
            Column::make("UUID", "sip_profile_uuid")
                ->sortable()
                ->searchable()
                ->hideIf(true),
            Column::make("Name", "sip_profile_name")
                ->sortable()
                ->searchable(),
            Column::make("Hostname", "sip_profile_hostname")
                ->sortable()
                ->searchable(),
            BooleanColumn::make("Enabled", "sip_profile_enabled")
                ->sortable()
                ->searchable(),
            Column::make("Description", "sip_profile_description")
                ->sortable()
                ->searchable(),
        ];

        return $columns;
    }

    public function builder(): Builder
    {
        return SipProfile::query();
    }
}
