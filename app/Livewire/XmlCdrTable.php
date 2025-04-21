<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\XmlCDR;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class XmlCDRTable extends DataTableComponent
{
    protected $model = XmlCDR::class;

    public $filters = [];

    public function mount($filters = [])
    {
        $this->filters = $filters;
    }

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('xml_cdr_edit');
        $this->setPrimaryKey('xml_cdr_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchDisabled()
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

            Column::make("", "direction")
                ->format(function ($value, $row, Column $column) {

                    $content = "";

                    if($row->direction == 'inbound' || $row->direction == 'local')
                    {
                        if($row->answer_stamp != '' && $row->bridge_uuid != '')
                        {
                            $call_result = 'answered';
                        }
                        else if($row->answer_stamp != '' && $row->bridge_uuid == '')
                        {
                            $call_result = 'voicemail';
                        }
                        else if($row->answer_stamp == '' && $row->bridge_uuid == '' && $row->sip_hangup_disposition != 'send_refuse')
                        {
                            $call_result = 'cancelled';
                        }
                        else
                        {
                            $call_result = 'failed';
                        }
                    }
                    else if($row->direction == 'outbound')
                    {
                        if($row->answer_stamp != '' && $row->bridge_uuid != '')
                        {
                            $call_result = 'answered';
                        }
                        else if($row->hangup_cause == 'NORMAL_CLEARING')
                        {
                            $call_result = 'answered';
                        }
                        else if($row->answer_stamp == '' && $row->bridge_uuid != '')
                        {
                            $call_result = 'cancelled';
                        }
                        else
                        {
                            $call_result = 'failed';
                        }
                    }

                    if($row->record_type == "text")
                    {
                        $call_result = 'answered';
                    }

                    if(!empty($row->direction))
                    {
                        $image_name = "icon_cdr_" . $row->direction . "_" . $call_result;

                        if ($row->leg == 'b')
                        {
                            $image_name .= '_b';
                        }

                        $image_name .= ".png";

                        $title = __($row->direction) . ': ' . __($call_result) . ($row->leg == 'b' ? '(b)' : '');

                        $content = '<img src="' . asset("assets/icons/xml_cdr/$image_name") . '" style="border: none; cursor: help;" title="' . $title . '">';
                    }

                    return $content;
                })->html()
                ->sortable(),

            Column::make("Ext.", "extension.extension")
                ->sortable(),

            Column::make("Caller name", "caller_id_name")
                ->sortable(),

            Column::make("Caller number", "caller_id_number")
                ->sortable(),

            Column::make("Caller destination", "caller_destination")
                ->sortable(),

            Column::make("Destination", "destination_number")
                ->sortable(),

            Column::make("Date", "start_epoch")
                ->format(function ($value, $row, Column $column) {
                    return date('D j M Y H:i:s', $row->start_epoch);
                })
                ->sortable(),

            Column::make("TTA", "answer_epoch")
                ->format(function ($value, $row, Column $column) {
                    return (int)$row->answer_epoch - (int)$row->start_epoch;
                })
                ->sortable(),

            Column::make("Duration", "duration")
                ->format(function ($value, $row, Column $column) {
                    $seconds = $row->duration;
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;

                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                })
                ->sortable(),

            Column::make("PDD", "pdd_ms")
                ->format(function ($value, $row, Column $column) {
                    $milliseconds = $row->pdd_ms;
                    $seconds = $milliseconds / 1000;

                    return number_format($seconds, 2) . 's';
                })
                ->sortable(),

            Column::make("MOS", "rtp_audio_in_mos")
                ->sortable(),

            Column::make("Hangup cause", "hangup_cause")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = XmlCDR::query()
                ->with("extension")
                ->where( XmlCDR::getTableName() . ".domain_uuid", "=", Session::get("domain_uuid"))
                ->when($this->filters['direction'] ?? null, fn($q, $v) => $q->where('direction', '=', $v))
                ->when($this->filters['leg'] ?? null, fn($q, $v) => $q->where('leg', '=', $v))
                ->when($this->filters['extension'] ?? null, fn($q, $v) => $q->where('extension.extension', '=', $v))
                ->when($this->filters['caller_id_name'] ?? null, fn($q, $v) => $q->where('caller_id_name', '=', $v))
                ->when($this->filters['caller_id_number'] ?? null, fn($q, $v) => $q->where('caller_id_number', '=', $v))
                ->when($this->filters['start_range_from'] ?? null, fn($q, $v) => $q->where('start_stamp', '>=', $v))
                ->when($this->filters['start_range_to'] ?? null, fn($q, $v) => $q->where('start_stamp', '<', $v))
                ->when($this->filters['duration_min'] ?? null, fn($q, $v) => $q->where('duration', '>=', $v))
                ->when($this->filters['duration_max'] ?? null, fn($q, $v) => $q->where('duration', '<', $v))
                ->when($this->filters['caller_destination'] ?? null, fn($q, $v) => $q->where('caller_destination', '=', $v))
                ->when($this->filters['destination'] ?? null, fn($q, $v) => $q->where('destination_number', '=', $v))
                // ->when($this->filters['tta'] ?? null, fn($q, $v) => $q->where('destination_number', '=', $v))
                ->when($this->filters['hangup_cause'] ?? null, fn($q, $v) => $q->where('hangup_cause', '=', $v))
                ->when($this->filters['order_field'] ?? null, fn($q, $v) => $q->orderBy($this->filters['order_field'], $this->filters['order_sort'] ?? 'asc'))
                ->orderBy("start_epoch", "desc");
        return $query;
    }
}
