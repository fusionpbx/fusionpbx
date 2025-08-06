<?php

namespace App\Livewire;

use App\Facades\Setting;
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

            Column::make("Organization", "contactTo.contact_organization")
                ->format(function ($value, $row, Column $column) {
			        $json_url = route('billings.export', $row->billing_uuid) . '?format=json&prefix=XXX';
			        $csv_url = route('billings.export', $row->billing_uuid) . '?format=csv&prefix=XXX';

                    return $value . "<br><small>JSON rates: {$json_url}<br/>CSV rates: {$csv_url}</small>";
                })
                ->sortable()
                ->html(),

            Column::make("Given name", "contactTo.contact_name_given")
                ->sortable(),

            Column::make("Family name", "contactTo.contact_name_family")
                ->sortable(),

            Column::make("Balance", "balance")
                ->format(function ($value, $row, Column $column) {
                    $value = "";

                    if(($row->credit_type == 'postpaid') && (strlen($row->whmcs_user_id)))
                    {
                        $value = 'WHMCS is handling the balance';
                    }
                    else
                    {
                        $value = $row->balance . " " . $row->currency;
                    }

                    return $value;
                })
                ->sortable(),

            Column::make("Actions", "billing_uuid")
                ->format(function ($value, $row, Column $column) {
                    $buttons = '<a href="' . route("billings.view", $row->billing_uuid) . '" class="btn btn-primary btn-sm m-1"><i class="fa-solid fa-eye"></i></a>';

                    if(($row->credit_type != 'postpaid') || (!empty($row->whmcs_user_id)))
                    {
                        $buttons .= '<a href="' . route("billings.payment", $row->billing_uuid) . '" class="btn btn-primary btn-sm m-1"><i class="fa-solid fa-wallet"></i></a>';
                    }

                    if(($row->child_count > 0) && ($row->balance > 0))
                    {
                        $buttons .= '<a href="' . route("billings.transfer_get", $row->billing_uuid) . '" class="btn btn-primary btn-sm m-1"><i class="fa-solid fa-money-bill-transfer"></i></a>';
                    }

                    return $buttons;
                })
                ->html()
        ];
    }

    public function builder(): Builder
    {
        $query = Billing::with('contactTo')
            ->select([
                'v_billings.*',
                DB::raw('0 AS depth'),
                DB::raw('(SELECT COUNT(*) FROM ' . Billing::getTableName() . ' bb WHERE bb.parent_billing_uuid = v_billings.billing_uuid GROUP BY bb.parent_billing_uuid) AS child_count'),
                DB::raw('type_value AS path')
            ]);

		if(!auth()->user()->hasGroup('superadmin'))
        {
			if(auth()->user()->hasGroupup('admin'))
            {
				$query->where("v_contacts.domain_uuid", Session::get('domain_uuid'));
			}
			else
            {
				$query->where("contact_uuid", Setting::getSetting("user", "contact_uuid"));
			}
		}

        $query->orderBy('path');

        return $query;
    }
}
