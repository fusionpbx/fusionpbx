<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\EmailQueue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmailQueueTable extends DataTableComponent
{
    protected $model = EmailQueue::class;
    public string $status = '';

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('email_queue_edit');
        $tableConfig = $this->setPrimaryKey('email_queue_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search email queues')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();
        

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('email-queues.edit', $row->email_queue_uuid);
            });
        }
    }

    public function columns(): array
    {
        $columns = [];
        $columns = [
            Column::make("Email queue uuid", "email_queue_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Domain uuid", "domain_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make('Email date', 'email_date')
                ->sortable()
                ->searchable()
                ->format(fn($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null),
            Column::make('Hostname', 'hostname')
                ->sortable()
                ->searchable(),
            Column::make("Email from", "email_from")
                ->sortable(),
            Column::make("Email to", "email_to")
                ->sortable(),
            Column::make("Email subject", "email_subject")
                ->sortable()
                ->format(fn($value) => $value ? iconv_mime_decode($value, 0, 'UTF-8') : null),
            Column::make("Email status", "email_status")
                ->sortable(),
            Column::make('Retry', 'email_retry_count')
                ->sortable(),
            Column::make('After Email', 'email_action_after')
                ->sortable(),

        ];
        
        return $columns;
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        $bulkActions['bulkDelete'] = 'Delete';

        return $bulkActions;
    }

    public function bulkDelete(): void
    {
        $selectedRows = $this->getSelected();
        try {
            DB::beginTransaction();
            EmailQueue::whereIn('email_queue_uuid', $selectedRows)->delete();
            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');

            session()->flash('message', 'Email queues deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            // session()->flash('error', 'Failed to delete email queues: ' . $e->getMessage());
        }
    }

    public function builder(): Builder
    {
        $query = EmailQueue::query();

        return $query->when($this->status, fn ($query) => $query->where('email_status', $this->status));

    }

}
