<?php

namespace App\Livewire;

use App\Facades\DomainService;
use App\Models\Domain;
use App\Repositories\DomainRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class DomainsTable extends DataTableComponent
{
    protected $model = Domain::class;
    protected $domainRepository;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('domain_edit');
        $this->setPrimaryKey('domain_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Domains')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setTableRowUrl(function($row) use ($canEdit) {
                return $canEdit
                    ? route('domains.edit', $row->domain_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('domain_edit')) {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('domain_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('domain_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;


    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('domain_edit')) {
            session()->flash('error', 'You do not have permission to mark domains as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Domain::whereIn('domain_uuid', $selectedRows)->update(['domain_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The domains were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('domain_edit')) {
            session()->flash('error', 'You do not have permission to mark domains as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Domain::whereIn('domain_uuid', $selectedRows)->update(['domain_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The domains were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('domain_delete')) {
            session()->flash('error', 'You do not have permission to delete domains.');
            return;
        }

        $selectedRows = $this->getSelected();
        if (App::hasDebugModeEnabled()) {
             Log::debug('[DomainRepository:getForSelectControl] $selectedRows: ' . print_r($selectedRows, true));
        }

        if (in_array(auth()->user()->domain_uuid, $selectedRows))
        {
            session()->flash('error', 'You cannot delete your own tenant.');
        }
        else
        {
            foreach ($selectedRows as $domain_uuid)
            {
                $trashedDomain = Domain::find($domain_uuid);
                if (isset($trashedDomain))
                {
                    if ($trashedDomain->children()->isEmpty()){
                        $this->domainRepository = new DomainRepository($trashedDomain, null);
                        $this->domainRepository->delete($trashedDomain);
                    }
                    else{
                        session()->flash('error', 'You cannot delete tenants with children.');
                    }
                }
            }
/*
            // NOTE: Don't know if this still necessary
            try {
                DB::beginTransaction();

                Domain::whereIn('domain_uuid', $selectedRows)->delete();

                DB::commit();

                $this->clearSelected();
                $this->dispatch('refresh');
                session()->flash('success', 'Domains successfully deleted.');
            } catch (\Exception $e) {
                DB::rollBack();
                session()->flash('error', 'There was a problem deleting the domains: ' . $e->getMessage());
            }
*/
            // If we deleted our current domain
            if (in_array(Session::get('domain_uuid'), $selectedRows))
            {
                DomainService::switchByUuid(auth()->user()->domain_uuid);
                return redirect()->intended('/dashboard');
            }
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('domain_add')) {
            session()->flash('error', 'You do not have permission to copy domains.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $domainUuid) {
                $originalDomain = Domain::findOrFail($domainUuid);

                $newDomain = $originalDomain->replicate();
                $newDomain->domain_uuid = Str::uuid();
                $newDomain->domain_description = $newDomain->domain_description . ' (Copy)';
                $newDomain->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the domains: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "domain_uuid")->hideIf(true),

            Column::make("Name", "domain_name")
                ->sortable()
                ->searchable(),

            BooleanColumn::make("Enabled", "domain_enabled")
                ->sortable(),

            Column::make("Description", "domain_description")
                ->searchable()
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Domain::query()
                ->orderBy('domain_name', 'asc');
        return $query;
    }
}
