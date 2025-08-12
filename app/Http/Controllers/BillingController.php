<?php
namespace App\Http\Controllers;

use App\Http\Requests\BillingPaymentRequest;
use App\Http\Requests\BillingRequest;
use App\Http\Requests\BillingTransferRequest;
use App\Models\Billing;
use App\Models\Carrier;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\Lcr;
use App\Models\RateConversion;
use App\Repositories\BillingInvoiceRepository;
use App\Repositories\BillingRepository;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BillingController extends Controller
{
	protected $billingRepository;
	protected $billingInvoiceRepository;

	public function __construct(BillingRepository $billingRepository, BillingInvoiceRepository $billingInvoiceRepository)
	{
		$this->billingRepository = $billingRepository;
		$this->billingInvoiceRepository = $billingInvoiceRepository;
	}

	public function index()
	{
		return view('pages.billings.index');
	}

	public function create()
	{
		$billings = Billing::parentProfiles();
		$domains = Domain::all();

		return view("pages.billings.form", compact("billings", "domains"));
	}

	public function store(BillingRequest $request)
	{
		$data = $request->validated();

    	$data['domain_uuid'] = Session::get('domain_uuid');

		$billing = $this->billingRepository->create($data);

		return redirect()->route("billings.edit", $billing->billing_uuid);
	}

    public function show(Billing $billing)
    {
        //
    }

	public function edit(Billing $billing)
	{
		$billings = Billing::parentProfiles($billing->billing_uuid);
		$domains = Domain::all();

		return view("pages.billings.form", compact("billing", "billings", "domains"));
	}

	public function update(BillingRequest $request, Billing $billing)
	{
		$this->billingRepository->update($billing, $request->validated());

        return redirect()->route("billings.edit", $billing->billing_uuid);
	}

    public function destroy(Billing $billing)
    {
        $this->billingRepository->delete($billing);

        return redirect()->route('billings.index');
    }

    public function analysis(Request $request)
    {
		$sales = [];
		$purchases = [];

		if($request->isMethod('post'))
		{
			$caller_destination = $request->input('caller_destination');

			$caller_destination_ns = number_series($caller_destination);

			$direction = $request->input('direction');

			$short_call_friendly = $request->input('short_call_friendly');

			$lcr_tag = $request->input('lcr_tag');

			$include_disabled_carriers = $request->input('include_disabled_carriers');

			$include_disabled_rates = $request->input('include_disabled_rates');

			$ignore_dates = $request->input('ignore_dates');


			// SALES
			$lcrProfiles = Lcr::select('lcr_profile')->distinct()->whereNull('carrier_uuid')->pluck('lcr_profile');

			foreach($lcrProfiles as $lcrProfile)
			{
				$maxDigits = Lcr::select(DB::raw('MAX(CAST(digits AS UNSIGNED))'))->where('enabled', 'true');

				if(!empty($caller_destination_ns))
				{
					$maxDigits->whereIn("digits", $caller_destination_ns);
				}

				$maxDigits->where('lcr_direction', $direction);
				$maxDigits->whereNull('carrier_uuid');
				$maxDigits->where('lcr_profile', $lcrProfile);

				$sales[$lcrProfile] = Lcr::where('enabled', 'true')
					->where('lcr_direction', $direction)
					->where('lcr_profile', $lcrProfile)
					->whereRaw('NOW() >= date_start')
					->whereRaw('NOW() < date_end')
					->where('digits', $maxDigits)
					->get();
			}

			// PURCHASES
			$rateConversion1 = $rateConversion2 = RateConversion::select('rate')
				->whereColumn('to_iso4217', 'lcr.currency')
				->where('from_iso4217', 'USD')
				->orderByDesc('rate_epoch')
				->limit(1);

			$query = Lcr::query()
				->select([
					'lcr.currency',
					'lcr.digits',
					'lcr.description',
					DB::raw('c.carrier_name'),
					DB::raw('(lcr.connect_rate / (' . $rateConversion1->toSql() . ')) AS connect_rate'),
					DB::raw('(lcr.rate / (' . $rateConversion2->toSql() . ')) AS rate'),
					DB::raw('lcr.connect_increment'),
					DB::raw('lcr.talk_increment'),
				])
				->mergeBindings($rateConversion1->getQuery())
				->mergeBindings($rateConversion2->getQuery())
				->from(Lcr::getTableName() . ' as lcr')
				->join(Carrier::getTableName() . ' as c', 'lcr.carrier_uuid', '=', 'c.carrier_uuid')
				->when($include_disabled_carriers !== 'true', fn($q) =>
					$q->where('c.enabled', 'true'))
				->when($include_disabled_rates !== 'true', fn($q) =>
					$q->where('lcr.enabled', 'true'))
				->where('lcr.lcr_direction', $direction)
				->when($short_call_friendly === 'true', fn($q) =>
					$q->where('c.short_call_friendly', 'true'))
				->when(!empty($lcr_tag), fn($q) =>
					$q->where(function($sub) use ($lcr_tag) {
						$sub->where('c.lcr_tags', $lcr_tag)
							->orWhere('c.lcr_tags', 'like', "$lcr_tag,%")
							->orWhere('c.lcr_tags', 'like', "%,$lcr_tag,%")
							->orWhere('c.lcr_tags', 'like', "%,$lcr_tag");
					}))
				->when($ignore_dates !== 'true', fn($q) =>
					$q->whereRaw('NOW() >= lcr.date_start')
					->whereRaw('NOW() < lcr.date_end'));

			if(!empty($caller_destination))
			{
				if(preg_match('/.*?(\*+)$/', $caller_destination))
				{
					$mod_caller_destination = str_replace('*', '%', $caller_destination);
					$query->where('lcr.digits', 'like', $mod_caller_destination);

					if($include_disabled_rates !== 'true')
					{
						$query->where('lcr.enabled', 'true');
					}

					$query->orderBy('c.priority')
						->orderBy('lcr.digits', 'desc')
						->orderBy('rate')
						->orderBy('lcr.date_start', 'desc');
				}
				else
				{
					$maxDigits = Lcr::select(DB::raw('MAX(CAST(digits AS UNSIGNED))'))->where('enabled', 'true');

					if(!empty($caller_destination_ns))
					{
						$maxDigits->whereIn("digits", $caller_destination_ns);
					}

					$maxDigits->where('lcr_direction', $direction);
					$maxDigits->whereColumn('carrier_uuid', 'c.carrier_uuid');

					if($include_disabled_rates !== 'true')
					{
						$maxDigits->where('enabled', 'true');
					}

					$query->where('lcr.digits', $maxDigits);

					$query->orderBy('c.priority')
						->orderBy('rate')
						->orderBy('lcr.digits', 'desc')
						->orderBy('lcr.date_start', 'desc');
				}
			}
			else
			{
				$query->orderBy('c.priority')
					->orderBy('rate')
					->orderBy('lcr.digits', 'desc')
					->orderBy('lcr.date_start', 'desc');
			}

			$purchases = $query->get();
		}

		return view("pages.billings.analysis", compact("sales", "purchases"));
    }

    public function pricing()
    {
        return view("pages.billings.pricing");
    }

	public function export(Billing $billing, Request $request)
	{
		$answer = [];

		$lcr_profile = $billing->lcr_profile;
		$currency = $billing->currency;
		$format = $request->query('format');
		$prefix = $request->query('prefix');
		$filename = $billing->billing_uuid;

		if(!empty($lcr_profile))
		{
			$digits = Lcr::query()
				->select('digits')
				->whereNull('carrier_uuid')
				->when(strlen($prefix) > 0, function ($query) use ($prefix) {
					$query->where('digits', 'like', $prefix . '%');
				})
				->distinct()
				->orderByDesc('digits')
				->pluck('digits');

			foreach($digits as $d)
			{
				$ns = number_series($d);

				$lcr = Lcr::query()
					->whereNull('carrier_uuid')
					->where('lcr_profile', $lcr_profile)
					->whereIn('digits', $ns)
					->orderByDesc('digits')
					->limit(1)
					->first();

				if($lcr->currency != $currency)
				{
					$lcr->rate = number_format(currency_convert($lcr->rate, $currency, $lcr->currency), 5);
					$lcr->intrastate_rate = number_format(currency_convert($lcr->intrastate_rate, $currency, $lcr->currency), 5);
					$lcr->intralata_rate = number_format(currency_convert($lcr->intralata_rate, $currency, $lcr->currency), 5);
					$lcr->connect_rate = number_format(currency_convert($lcr->connect_rate, $currency, $lcr->currency), 5);
					$lcr->currency = $currency;
				}

				$lcr->digits = $d;
				$lcr->lcr_profile = $lcr_profile;

				$answer[] = $lcr;
			}
		}

		switch($format)
		{
			case "csv":
				$contentType = "text/csv";
				break;
			case "json":
				$contentType = "application/json";
				$answer = json_encode($answer, JSON_PRETTY_PRINT);
				break;
			default:
				$contentType = "";
		}

		return response()->streamDownload(function () use ($answer) {
			$output = fopen('php://output', 'w');

			if(is_array($answer))
			{
				fputcsv($output, array_keys($answer[0]->getAttributes()));

				foreach($answer as $a)
				{
					fputcsv($output, array_values($a->getAttributes()));
				}
			}
			else
			{
				echo $answer;
			}

			fclose($output);
		}, $filename, [
			'Content-Type' => $contentType,
			'Content-Disposition' => "attachment; filename={$filename}",
			'Cache-Control' => 'no-store, no-cache, must-revalidate',
		]);
	}

	public function view(Billing $billing)
	{
		return view("pages.billings.view", compact("billing"));
	}

	public function payment(Billing $billing)
	{
		$paymentgateways = array_keys(config('paymentgateways'));

		return view("pages.billings.payments.index", compact("billing", "paymentgateways"));
	}

	private function getMaxCredit(Billing $billing)
	{
		$max = 0;

		if($billing->credit_type == 'prepaid')
		{
			$max = $billing->balance;
		}

		if($billing->credit_type == 'postpaid')
		{
			$max = abs($billing->credit - $billing->balance);
		}

		return $max;
	}

	public function transferGet(Billing $billing)
	{
		$v_billing = Billing::getTableName();
		$v_contacts = Contact::getTableName();

		$queryBilling = Billing::query()
			->leftJoin($v_contacts, 'contact_uuid_to', '=', $v_contacts . '.contact_uuid')
			->where('billing_uuid', $billing->billing_uuid)
			->where(function ($q) {
				$q->whereNull('whmcs_user_id')
				->orWhere('whmcs_user_id', '<', 1);
			})
			->where(function ($q) {
				$q->where(function ($sub) {
					$sub->where('credit_type', 'postpaid')
						->whereColumn('balance', '>', 'credit');
				})->orWhere(function ($sub) {
					$sub->where('credit_type', 'prepaid')
						->where('balance', '>', 0);
				});
			});

		$billing = $queryBilling->first();

		$queryBillings = Billing::query()
			->selectRaw($v_billing . '.*,' . $v_contacts . '.*, 0 as depth')
			->leftJoin($v_contacts, $v_billing . '.contact_uuid_to', '=', $v_contacts . '.contact_uuid')
			->where(function ($q) {
				$q->whereNull('whmcs_user_id')->orWhere('whmcs_user_id', '<', 1);
			});

		if(!auth()->user()->hasGroup('superadmin'))
		{
			$queryBillings->where('parent_billing_uuid', $billing);
		}

		$billings = $queryBillings->get();

		$max = $this->getMaxCredit($billing);

		return view("pages.billings.transfer", compact("billing", "billings", "max"));
	}

	public function transferPost(BillingTransferRequest $request, Billing $billing)
	{
		$data = $request->validated();

		$billing_uuid_to = $data['billing_uuid_to'];
		$transfer = $data['transfer'];

		$billingTo = Billing::findOrFail($billing_uuid_to);

		$max = $this->getMaxCredit($billing);

		if($transfer > $max)
		{
			$transfer = $max;
		}

		$transferTo = $transfer * currency_convert_rate($billingTo->currency, $billing->currency);

		// Update from
		$billingData = [
			"balance" => $billing->balance - $transfer,
		];

		$this->billingRepository->update($billing, $billingData);

		// Insert activity
		$billingInvoiceData = [
			"billing_uuid" => $billing->billing_uuid,
			"payer_uuid" => $billing->contact_uuid_to,
			"billing_payment_date" => date("Y-m-d H:i:s"),
			"settled" => 1,
			"amount" => $transfer,
			"debt" => $billing->balance,
			"post_payload" => "",
			"plugin_used" => "transfer",
			"domain_uuid" => $billing->domain_uuid,
			"tax" => 0,
		];

		$this->billingInvoiceRepository->create($billingInvoiceData);

		// Update to
		$billingData = [
			"balance" => $billingTo->balance + $transferTo,
		];

		$this->billingRepository->update($billingTo, $billingData);

		// Insert activity
		$billingInvoiceData = [
			"billing_uuid" => $billingTo->billing_uuid,
			"payer_uuid" => $billingTo->contact_uuid_to,
			"billing_payment_date" => date("Y-m-d H:i:s"),
			"settled" => 1,
			"amount" => $transferTo,
			"debt" => $billingTo->balance,
			"post_payload" => "",
			"plugin_used" => "transfer",
			"domain_uuid" => $billingTo->domain_uuid,
			"tax" => 0,
		];

		$this->billingInvoiceRepository->create($billingInvoiceData);

		return view("pages.billings.index");
	}

	public function paymentCreate(Billing $billing, string $paymentGateway)
	{
		$paymentGateways = config('paymentgateways');

		$paymentgatewayConfig = $paymentGateways[$paymentGateway];

		$defaultCharge = $paymentgatewayConfig['default_charge'];

		return view("pages.billings.payments.{$paymentGateway}", compact("billing", "paymentGateway", "defaultCharge"));
	}

	public function paymentStore(BillingPaymentRequest $request, Billing $billing, string $paymentGateway)
	{
		$PaymentGatewayFactory = PaymentGatewayFactory::make($paymentGateway);

		$PaymentGatewayFactory->createPayment($billing, $request->validated());

		return redirect()->route("billings.payment", $billing);
	}
}
