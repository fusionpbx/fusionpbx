<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\XmlCDR;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XmlCDRTable extends DataTableComponent
{
    protected $model = XmlCDR::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('xml_cdr_edit');
        $this->setPrimaryKey('xml_cdr_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search XmlCDRs')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('xml_cdr_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        return $bulkActions;
    }

    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('xml_cdr_delete')) {
            session()->flash('error', 'You do not have permission to delete xml_cdrs.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            XmlCDR::whereIn('xml_cdr_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'XmlCDRs successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the xml_cdrs: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "xml_cdr_uuid")->hideIf(true),

            Column::make("Ext.", "extension.extension")
                ->searchable()
                ->sortable(),

            Column::make("Caller name", "caller_id_name")
                ->searchable()
                ->sortable(),

            Column::make("Caller number", "caller_id_number")
                ->searchable()
                ->sortable(),

            Column::make("Caller destination", "caller_destination")
                ->searchable()
                ->sortable(),

            Column::make("Destination", "destination_number")
                ->searchable()
                ->sortable(),

            Column::make("Date", "start_epoch")
                ->format(function ($value, $row, Column $column) {
                    return date('D j M Y H:i:s', $row->start_epoch);
                })
                ->searchable()
                ->sortable(),

            Column::make("TTA", "answer_epoch")
                ->format(function ($value, $row, Column $column) {
                    return (int)$row->answer_epoch - (int)$row->start_epoch;
                })
                ->searchable()
                ->sortable(),

            Column::make("Duration", "duration")
                ->format(function ($value, $row, Column $column) {
                    $seconds = $row->duration;
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;

                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                })
                ->searchable()
                ->sortable(),

            Column::make("PDD", "pdd_ms")
                ->format(function ($value, $row, Column $column) {
                    $milliseconds = $row->pdd_ms;
                    $seconds = $milliseconds / 1000;

                    return number_format($seconds, 2) . 's';
                })
                ->searchable()
                ->sortable(),

            Column::make("MOS", "rtp_audio_in_mos")
                ->searchable()
                ->sortable(),

            Column::make("Hangup cause", "hangup_cause")
                ->searchable()
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = XmlCDR::query()
                ->with("extension")
                ->orderBy("start_epoch", "desc");
        return $query;
    }
}
