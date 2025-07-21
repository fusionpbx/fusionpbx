<?php

namespace App\Livewire;

use App\Models\Billing;
use App\Models\BillingDeal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class BillingsDealsTable extends DataTableComponent
{
    protected $model = BillingDeal::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('billing_deal_edit');
        $this->setPrimaryKey('billing_deal_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Billing Deals')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('billings.deals.edit', $row->billing_deal_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('billing_deal_delete'))
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
        if (!auth()->user()->hasPermission('billing_deal_delete'))
        {
            session()->flash('error', 'You do not have permission to delete billings.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            BillingDeal::whereIn('billing_deal_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Billings deals successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the billing deals: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('billing_add'))
        {
            session()->flash('error', 'You do not have permission to copy billing deals.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $billingDealUuid)
            {
                $originalBillingDeal = BillingDeal::findOrFail($billingDealUuid);

                $newBillingDeal = $originalBillingDeal->replicate();
                $newBillingDeal->billing_deal_uuid = Str::uuid();
                $newBillingDeal->save();
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
            Column::make("Billing deal uuid", "billing_deal_uuid")->hideIf(true),

            Column::make("Direction", "direction")
                ->sortable(),

            Column::make("Prefix", "digits")
                ->sortable(),

            Column::make("Minutes", "minutes")
                ->sortable(),

            Column::make("Rate", "rate")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = BillingDeal::query()
            ->where('domain_uuid', Session::get('domain_uuid'));
        return $query;
    }
}
