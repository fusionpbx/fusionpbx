<?php
namespace App\Http\Controllers;

use App\Http\Requests\BillingInvoiceRequest;
use App\Models\BillingInvoice;
use App\Repositories\BillingInvoiceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class BillingInvoiceController extends Controller
{
	protected $billingInvoiceRepository;

	public function __construct(BillingInvoiceRepository $billingInvoiceRepository)
	{
		$this->billingInvoiceRepository = $billingInvoiceRepository;
	}

	public function process(BillingInvoiceRequest $request, BillingInvoice $billingInvoice)
	{
		$data = $request->validated();

		$this->billingInvoiceRepository->update($billingInvoice, $data);

		return redirect()->back()->with('success', __('Operation completed'));
	}
}
