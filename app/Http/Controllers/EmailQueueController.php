<?php

namespace App\Http\Controllers;

use App\Repositories\EmailQueueRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EmailQueueController extends Controller
{
    protected $emailQueueRepository;

    public function __construct(EmailQueueRepository $emailQueueRepository)
    {
        $this->emailQueueRepository = $emailQueueRepository;
    }
    public function index(): View
    {
        return view("pages.emailqueue.index");
    }

    public function findByStatus(Request $request): View
    {
        $status = $request->get('status');
        $emailQueues = $status
            ? $this->emailQueueRepository->findbyStatus($status)
            : $this->emailQueueRepository->all();

        return view("pages.emailqueue.index", compact('emailQueues'));
    }

    public function edit(string $uuid): View
    {
        $emailQueue = $this->emailQueueRepository->findByUuid($uuid);
        $emailQueueUuid = $emailQueue->email_queue_uuid;
        // dd($emailQueueUuid);

        return view("pages.emailqueue.form", compact('emailQueueUuid'));
    }
}
