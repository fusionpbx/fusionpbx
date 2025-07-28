<?php
namespace App\Http\Controllers;

use App\Http\Requests\BillingRequest;
use App\Models\Billing;
use App\Models\Carrier;
use App\Models\Domain;
use App\Models\Lcr;
use App\Models\RateConversion;
use App\Repositories\BillingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BillingController extends Controller
{
	protected $billingRepository;

	public function __construct(BillingRepository $billingRepository)
	{
		$this->billingRepository = $billingRepository;
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
}
