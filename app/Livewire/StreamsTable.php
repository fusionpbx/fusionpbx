<?php

namespace App\Livewire;

use App\Models\Stream;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class StreamsTable extends DataTableComponent
{
    protected $model = Stream::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('stream_edit');
        $this->setPrimaryKey('stream_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Streams')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('streams.edit', $row->stream_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('stream_edit'))
        {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('stream_delete'))
        {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('stream_add'))
        {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('stream_edit'))
        {
            session()->flash('error', 'You do not have permission to mark streams as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Stream::whereIn('stream_uuid', $selectedRows)->update(['stream_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The streams were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('stream_edit'))
        {
            session()->flash('error', 'You do not have permission to mark streams as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Stream::whereIn('stream_uuid', $selectedRows)->update(['stream_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The streams were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('stream_delete'))
        {
            session()->flash('error', 'You do not have permission to delete streams.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            Stream::whereIn('stream_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Streams successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the streams: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('stream_add'))
        {
            session()->flash('error', 'You do not have permission to copy streams.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $streamUuid)
            {
                $originalStream = Stream::findOrFail($streamUuid);

                $newStream = $originalStream->replicate();
                $newStream->stream_uuid = Str::uuid();
                $newStream->domain_uuid = $newStream->domain_uuid;
                $newStream->stream_name = $newStream->stream_name . ' (Copy)';
                $newStream->stream_enabled = $newStream->stream_enabled;
                $newStream->stream_description = $newStream->stream_description;
                $newStream->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the streams: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Stream uuid", "stream_uuid")->hideIf(true),

            Column::make("Stream name", "stream_name")
                ->sortable(),

            Column::make("Stream location", "stream_location")
                ->format(function ($value, $row, Column $column) {
                    if (!empty($row->stream_location))
                    {
                        $location_parts = explode('://',$row->stream_location);
                        $http_protocol = ($location_parts[0] == "shout") ? 'http' : 'https';
                        $location = $location_parts[1] ?? '';
                        return "<audio src='{$http_protocol}://{$location}' controls='controls'>";
                    }
                })
                ->html()
                ->sortable(),

            BooleanColumn::make("Stream enabled", "stream_enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Stream::query()
            ->where('domain_uuid', Session::get('domain_uuid'))
            ->orderBy('stream_name', 'asc');
        return $query;
    }
}
