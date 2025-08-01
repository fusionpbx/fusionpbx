<?php

namespace App\Livewire;

use App\Facades\Setting;
use App\Models\Billing;
use App\Models\BillingInvoice;
use App\Models\Bridge;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class BillingView extends DataTableComponent
{
    protected $model = BillingInvoice::class;

    public $billing = null;

    public function mount($billing = null)
    {
        $this->billing = $billing;
    }

    public function configure(): void
    {
        $this->setPrimaryKey('billing_invoice_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Billings Invoices')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setPaginationEnabled();
    }

    public function columns(): array
    {
        return [
            Column::make("Billing Invoice", "billing_invoice_uuid")->hideIf(true),
            Column::make("Date", "billing_payment_date"),
            Column::make("UUID", "billing_invoice_uuid"),
			Column::make("Amount", "amount"),
			Column::make("Username", "username"),
			Column::make("Tax", "tax"),
			Column::make("Method", "plugin_used"),
            Column::make("Actions", "settled")
                ->format(function ($value, $row, Column $column) {
                    $cell = '';

                    if($row->settled == 0)
                    {
                        $cell .= 'Pending';

                        if(!auth()->user()->hasGroup('superadmin'))
                        {
                            $cell .= '<br><form action="' . route("billings.process", $row->billing_invoice_uuid) . '" method="post">';
                            $cell .= '<input type="hidden" name="_token" value="' . csrf_token() .'">';
                            $cell .= '<button type="submit" class="btn btn-success btn-sm">Settle</button>';
                            $cell .= '<input type="hidden" name="settled" value="1">';
                            $cell .= '</form>';
                        }
                    }

                    if($row->settled == 1)
                    {
                        $cell .= 'Settled';

                        if(!auth()->user()->hasGroup('superadmin'))
                        {
                            $cell .= '<br><form action="' . route("billings.process", $row->billing_invoice_uuid) . '" method="post">';
                            $cell .= '<input type="hidden" name="_token" value="' . csrf_token() .'">';
                            $cell .= '<button type="submit" class="btn btn-danger btn-sm">Refund</button>';
                            $cell .= '<input type="hidden" name="settled" value="-1">';
                            $cell .= '</form>';
                        }
                    }

                    if($row->settled == -1)
                    {
                        $cell = '<button type="button" class="btn btn-primary btn-sm">Refunded</button>';
                    }

                    return $cell;
                })
                ->html()
        ];
    }

    public function builder(): Builder
    {
		$v_billing = Billing::getTableName();
		$v_billing_invoices = BillingInvoice::getTableName();
		$v_users = User::getTableName();

		$billing = $this->billing;

		$query1 = BillingInvoice::query()
			->select(
				"{$v_billing_invoices}.*",
				"{$v_users}.username",
				"{$v_billing}.currency"
			)
			->join("{$v_users}", "{$v_users}.user_uuid", "=", "{$v_billing_invoices}.payer_uuid")
			->join("{$v_billing}", "{$v_billing}.billing_uuid", "=", "{$v_billing_invoices}.billing_uuid")
			->where("{$v_billing_invoices}.billing_uuid", $billing->billing_uuid)
			->distinct();

		$query2 = BillingInvoice::query()
			->selectRaw("{$v_billing_invoices}.*, '[System]' as username, {$v_billing}.currency")
			->join("{$v_billing}", "{$v_billing}.billing_uuid", "=", "{$v_billing_invoices}.billing_uuid")
			->where("{$v_billing_invoices}.billing_uuid", $billing->billing_uuid)
			->whereNotIn("{$v_billing_invoices}.billing_invoice_uuid", function ($q) use ($v_billing, $v_billing_invoices, $v_users, $billing) {
				$q->select("{$v_billing_invoices}.billing_invoice_uuid")
					->from($v_billing_invoices)
					->join("{$v_users}", "{$v_users}.user_uuid", "=", "{$v_billing_invoices}.payer_uuid")
					->join("{$v_billing}", "{$v_billing}.billing_uuid", "=", "{$v_billing_invoices}.billing_uuid")
					->where("{$v_billing_invoices}.billing_uuid", $billing->billing_uuid);
			})
			->distinct();

		$unionQuery = $query1->union($query2);

		return BillingInvoice::fromSub(DB::query()->fromSub($unionQuery, 'x'), 'v_billing_invoices');
    }
}
