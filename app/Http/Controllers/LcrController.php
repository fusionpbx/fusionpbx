<?php
namespace App\Http\Controllers;

use App\Http\Requests\LcrImportRequest;
use App\Http\Requests\LcrRequest;
use App\Models\Lcr;
use App\Repositories\LcrRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LcrController extends Controller
{
	protected $lcrRepository;

	public function __construct(LcrRepository $lcrRepository)
	{
		$this->lcrRepository = $lcrRepository;
	}

	public function index()
	{
		return view('pages.lcr.index');
	}

	public function create(Request $request)
	{
		$carrier_uuid = $request->query("carrier_uuid");

		return view("pages.lcr.form", compact("carrier_uuid"));
	}

	public function store(LcrRequest $request)
	{
		$lcr = $this->lcrRepository->create($request->validated());

		return redirect()->route("lcr.edit", $lcr->lcr_uuid);
	}

    public function show(Lcr $lcr)
    {
        //
    }

	public function edit(Request $request, Lcr $lcr)
	{
		$carrier_uuid = $lcr->carrier_uuid;

		return view("pages.lcr.form", compact("lcr", "carrier_uuid"));
	}

	public function update(LcrRequest $request, Lcr $lcr)
	{
		$this->lcrRepository->update($lcr, $request->validated());

        return redirect()->route("lcr.edit", $lcr->lcr_uuid);
	}

    public function destroy(Lcr $lcr)
    {
        $this->lcrRepository->delete($lcr);

        return redirect()->route('lcr.index');
    }

	public function import(LcrImportRequest $request)
	{
		$file = $request->file('upload_file');
		$provider = $request->input('provider', 'custom');
		$clearBefore = $request->boolean('clear_before');
		$lcrProfile = $request->input('lcr_profile');
		$carrierUuid = $request->input('carrier_uuid');

		$path = $file->storeAs('tmp', Str::uuid() . '.' . $file->getClientOriginalExtension());

		$fullPath = storage_path("app/$path");

		$csv = array_map('str_getcsv', file($fullPath));

		$headers = array_shift($csv);

		$rates = array_map(function ($row) use ($headers) {
			return array_combine($headers, $row);
		}, $csv);

		$normalizedRates = $this->normalizeLcr($rates, $provider);

		if ($clearBefore)
		{
			$query = Lcr::query();

			if($carrierUuid)
			{
				$query->where('carrier_uuid', $carrierUuid);
			}
			else
			{
				$query->whereNull('carrier_uuid');
			}

			if($lcrProfile)
			{
				$query->where('lcr_profile', $lcrProfile);
			}

			$query->delete();
		}

		foreach($normalizedRates as $rate)
		{
			$this->insertLcr($rate, $carrierUuid, $lcrProfile);
		}

		return redirect()->back()->with('message', 'Importación completada.');
	}

	private function insertLcr(array $data, ?string $carrierUuid, ?string $defaultProfile): void
	{
		$ods = preg_split('/[\s,]+/', $data['origination_digits'] ?? '') ?: [''];

		foreach($ods as $origDigits)
		{
			$this->lcrRepository->create([
				'lcr_uuid' => Str::uuid(),
				'carrier_uuid' => $carrierUuid,
				'origination_digits' => $origDigits,
				'digits' => $data['digits'] ?? null,
				'rate' => $data['rate'] ?? 0,
				'currency' => strtoupper($data['currency'] ?? 'USD'),
				'connect_rate' => $data['connect_rate'] ?? $data['rate'] ?? 0,
				'intrastate_rate' => $data['intrastate_rate'] ?? $data['rate'] ?? 0,
				'intralata_rate' => $data['intralata_rate'] ?? $data['rate'] ?? 0,
				'lead_strip' => $data['lead_strip'] ?? 0,
				'trail_strip' => $data['trail_strip'] ?? 0,
				'prefix' => $data['prefix'] ?? null,
				'suffix' => $data['suffix'] ?? null,
				'lcr_profile' => $data['lcr_profile'] ?? $defaultProfile,
				'date_start' => $data['date_start'] ?? now(),
				'date_end' => $data['date_end'] ?? '2099-12-31 06:50:00',
				'quality' => $data['quality'] ?? 0,
				'reliability' => $data['reliability'] ?? 0,
				'cid' => $data['cid'] ?? null,
				'enabled' => true,
				'description' => $data['description'] ?? null,
				'connect_increment' => $data['connect_increment'] ?? 1,
				'talk_increment' => $data['talk_increment'] ?? ($data['connect_increment'] ?? 1),
				'lcr_direction' => $data['lcr_direction'] ?? 'outbound',
			]);
		}
	}

	function normalize_lcr($rates, $provider = 'custom')
	{
		$index = [];

		switch($provider)
		{
			case 'idtexpress':
				// IDTExpress
				$index['Location Name'] = 'description';
				$index['IDT Instant $ USD'] = 'rate';
				$index['Gold $ USD'] = 'rate';
				$index['Platinum $ USD'] = 'rate';
				$index['Dialer $ USD'] = 'rate';
				$index['Effective Date'] = 'date_start';
				$index['Dial Code'] = 'digits';
				$index['Initial Billing'] = 'connect_increment';
				$index['Incremental Billing'] = 'talk_increment';
				break;

			case 'custom':
				//Custom To Connect Me
				$index['Destination'] = 'description';
				$index['Prefix'] = 'digits';
				$index['Connect Increment'] = 'connect_increment';
				$index['Talking Increment'] = 'talk_increment';
				$index['Rate'] = 'rate';
				$index['Currency'] = 'currency';
				$index['Connect Rate'] = 'connect_rate';
				$index['Direction'] = 'lcr_direction';
				$index['Start Date'] = 'date_start';
				$index['End Date'] = 'date_end';
				$index['Profile'] = 'lcr_profile';
				$index['Lead Strip'] = 'lead_strip';
				$index['Trail Strip'] = 'trial_strip';
				$index['Add Prefix'] = 'prefix';
				$index['Add Suffix'] = 'suffix';
				break;

			case 'voxbeam':
				//voxbeam
				$index['Destination'] = 'description';
				$index['Named Route Name'] = 'description';
				$index['Prefix'] = 'digits';
				$index['BillMinimum'] = 'connect_increment';
				$index['BillIncrement'] = 'talk_increment';
				$index['PerMinuteCost'] = 'rate';
				$index['Currency'] = 'currency';
				$index['Named Route Name'] = 'description';
				$index['New Rate'] = 'rate';
				$index['Connection Unit'] =  'connect_increment';
				$index['Bill Unit'] = 'talk_increment';
				$index['Expiry Date'] = 'date_end';
				break;

			case 'voipms':
				//voip.ms
				$index['Description'] = 'description';
				$index['Destination'] = 'description';
				$index['Prefix'] = 'digits';
				$index['Rate'] = 'rate';
				$index['Increment'] = 'connect_increment';
				break;

			case 'flowroute':
				//flowroute
				$index['Destination'] = 'description';
				$index['Prefix'] = 'digits';
				$index['First Interval'] = 'connect_increment';
				$index['Sub Interval'] = 'talk_increment';
				$index['Default'] = 'rate';
				$index['Start Date'] = 'date_start';
				$index['Interstate Rate'] = 'intrastate_rate';
				$index['Intrastate Rate'] = 'intralata_rate';
				break;

			case 'alcazernet_international':
				//alcazernet international
				$index['dest description'] = 'description';
				$index['rate'] = 'rate';
				$index['Minimal increment'] = 'connect_increment';
				$index['Additional Increment'] = 'talk_increment';
				break;

			case 'alcazernet_usca':
				//alcazernet us/ca
				$index['dest description'] = 'description';
				$index['Minimal increment'] = 'connect_increment';
				$index['Additional Increment'] = 'talk_increment';
				$index['interstate'] = 'intrastate_rate';
				$index['interstate'] = 'rate';
				break;

			case 'didlogic':
				//didlogic
				$index['Destination'] = 'description';
				$index['Rate'] = 'rate';
				$index['Numberplan'] = 'digits';
				break;

			case 'telnyx':
				//telnyx
				$index['Description'] = 'description';
				$index['Destination Prefixes'] = 'digits';
				$index['Interval 1'] = 'connect_increment';
				$index['Interval N'] = 'talk_increment';
				$index['Rate'] = 'rate';
				$index['Origination Prefixes'] = 'origination_digits';
				break;

			case 'anveo':
				//anveo
				$index['prefix'] = 'digits';
				$index['destination'] = 'description';
				$index['rate_inter'] = 'intrastate_rate';
				$index['rate_intra'] = 'intralata_rate';
				$index['billing'] = 'connect_increment';
				break;

			case 'incorporus':
				//incorporus
				$index['Code'] = 'digits';
				$index['Destination'] = 'description';
				$index['Cost / Min (USD'] = 'rate';
				$index['Initial Increment'] = 'connect_increment';
				$index['Increment'] = 'talk_increment';
				break;

			case 'alcazar':
				//alcazar
				$index['destination'] = 'digits';
				$index['interstate'] = 'intralata_rate';
				$index['intrastate'] = 'intrastate_rate';
				break;

			case 'commpeak':
				//commpeak
				$index['Price'] = 'rate';
				$index['Billing Increments'] = 'connect_increment';
				break;
		}

		foreach($rates as &$rate)
		{
			foreach($rate as $key => $value)
			{
				foreach($index as $i => $rr)
				{
					if($key == $i)
					{
						$rate[$rr] = $value;

						unset($rate[$key]);
					}
				}
			}

			//autofill some missing data
			if((preg_match('/^1[2-9]\d{4,}/',$rate['digits'])) && (strlen($rate['connect_increment']) == 0) && (strlen($rate['talk_increment']) == 0))
			{
				$rate['connect_increment'] = 6;
				$rate['talk_increment'] = 6;
			}

			if(floatval($rate['intrastate_rate']) == 0)
			{
				$rate['intrastate_rate'] = $rate['rate'];
			}

			if(floatval($rate['intralata_rate']) == 0)
			{
				if(floatval($rate['intrastate_rate']) > 0)
				{
					$rate['intralata_rate'] = $rate['intrastate_rate'];
				}
				elseif(floatval($rate['rate']) > 0)
				{
					$rate['intralata_rate'] = $rate['rate'];
				}
			}

			if(strlen($rate['rate']) == 0)
			{
				if(floatval($rate['intralata_rate']) > 0)
				{
					$rate['rate'] = $rate['intralata_rate'];
				}
				elseif(floatval($rate['intrastate_rate']) > 0)
				{
					$rate['rate'] = $rate['intrastate_rate'];
				}
			}

			if(strlen($rate['connect_rate']) == 0)
			{
				$rate['connect_rate'] = $rate['rate'];
			}

			if(preg_match('/\d+[\-\/]\d+/',$rate['connect_increment'], $matches))
			{
				$rate['connect_increment'] = $matches[1];

				if(strlen($rate['talk_increment']) == 0)
				{
					$rate['talk_increment'] = $matches[2];
				}
			}
		}

		return $rates;
	}

	public function export(Request $request): StreamedResponse
	{
		$carrierUuid = $request->query('carrier_uuid');
		$filename = 'LCR-' . ($carrierUuid ?? 'null') . '.csv';

		return response()->streamDownload(function () use ($carrierUuid) {
			$output = fopen('php://output', 'w');

			// Obtener el primer registro para generar headers
			$firstRow = Lcr::when($carrierUuid, fn($q) => $q->where('carrier_uuid', $carrierUuid))->first();

			if($firstRow)
			{
				fputcsv($output, array_keys($firstRow->getAttributes()));
			}

			Lcr::when($carrierUuid, fn($q) => $q->where('carrier_uuid', $carrierUuid))
				->orderBy('digits')
				->chunk(1000, function ($chunk) use ($output) {
					foreach ($chunk as $lcr) {
						fputcsv($output, array_values($lcr->getAttributes()));
					}
				});

			fclose($output);
		}, $filename, [
			'Content-Type' => 'text/csv',
			'Content-Disposition' => "attachment; filename={$filename}",
			'Cache-Control' => 'no-store, no-cache, must-revalidate',
		]);
	}
}
