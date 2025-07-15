<?php

namespace App\Livewire;

use App\Models\Billing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class BillingsTable extends DataTableComponent
{
    protected $model = Billing::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('billing_edit');
        $this->setPrimaryKey('billing_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Billings')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('billings.edit', $row->billing_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('billing_delete'))
        {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('billing_add'))
        {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('billing_delete'))
        {
            session()->flash('error', 'You do not have permission to delete billings.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            Billing::whereIn('billing_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Billings successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the billings: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('billing_add'))
        {
            session()->flash('error', 'You do not have permission to copy billings.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $billingUuid)
            {
                $originalBilling = Billing::findOrFail($billingUuid);

                $newBilling = $originalBilling->replicate();
                $newBilling->billing_uuid = Str::uuid();
                $newBilling->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the billings: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Billing uuid", "billing_uuid")->hideIf(true),

            Column::make("Billing name", "billing_name")
                ->sortable(),

            Column::make("Billing destination", "billing_destination")
                ->sortable(),

            BooleanColumn::make("Billing enabled", "billing_enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Billing::query()
            ->where('domain_uuid', Session::get('domain_uuid'))
            ->orderBy('billing_name', 'asc');
        return $query;
    }
}
