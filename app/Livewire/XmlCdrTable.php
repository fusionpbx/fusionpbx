<?php

namespace App\Livewire;

use App\Models\XmlCDR;
use App\Facades\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

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
        $limit = Setting::get('cdr','limit', 'numeric') ?? 100;
        $canEdit = auth()->user()->hasPermission('xml_cdr_edit');
        $this->setPrimaryKey('xml_cdr_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchDisabled()
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage($limit)
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
        $columns = [
            Column::make("UUID", "xml_cdr_uuid")->hideIf(true)
        ];

        if(auth()->user()->hasPermission('xml_cdr_direction'))
        {
            $columns[] = Column::make("", "direction")
                ->format(function ($value, $row, Column $column) {

                    $content = "";

                    $call_result = $row->status;

                    if(!empty($row->direction))
                    {
                        $image_name = "icon_cdr_" . $row->direction . "_" . $call_result;

                        if ($row->leg == 'b')
                        {
                            $image_name .= '_b';
                        }

                        $image_name .= ".png";

                        $title = __(ucfirst($row->direction)) . ': ' . __(ucfirst($call_result)) . ($row->leg == 'b' ? '(b)' : '');

                        $content = '<img src="' . asset("assets/icons/xml_cdr/$image_name") . '" style="border: none; cursor: help;" title="' . $title . '">';
                    }

                    return $content;
                })->html()
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_extension'))
        {
            $columns[] = Column::make("Ext.", "extension.extension")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_all'))
        {
            $columns[] = Column::make("Domain", "domain_name")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_caller_id_name'))
        {
            $columns[] = Column::make("Caller name", "caller_id_name")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_caller_id_number'))
        {
            $columns[] = Column::make("Caller number", "caller_id_number")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_caller_destination'))
        {
            $columns[] = Column::make("Caller destination", "caller_destination")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_destination'))
        {
            $columns[] = Column::make("Destination", "destination_number")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_recording') && (auth()->user()->hasPermission('xml_cdr_recording_play') || auth()->user()->hasPermission('xml_cdr_recording_download')))
        {
            $columns[] = Column::make("Recording", "record_type")
                ->format(function ($value, $row, Column $column) {
                    if($row->record_type == "call")
                    {
                        $play = route('xmlcdr.play', $row->xml_cdr_uuid);
                        $download = route('xmlcdr.download', $row->xml_cdr_uuid);

                        $recording = "
                        <div class='progress-bar' style='background-color: #0d6efd; width: 0; height: 3px; position: relative; margin: 5px 0;'></div>
                        <audio id='recording_audio_{$row->xml_cdr_uuid}' style='display: none;' preload='none' src='{$play}' type='audio/wav'></audio>
                        <button type='button' id='recording_button_{$row->xml_cdr_uuid}' alt='Play / Pause' title='Play / Pause' class='btn btn-secondary btn-play-audio'><i class='fas fa-play'></i></button>
                        <a href='{$download}' target='_self'><button alt='Download' title='Download' class='btn btn-secondary'><i class='fas fa-download'></i></button></a>
                        ";

                        return $recording;
                    }
                })
                ->html()
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_start'))
        {
            $columns[] = Column::make("Date", "start_epoch")
                ->format(function ($value, $row, Column $column) {
                    return date('D j M Y H:i:s', $row->start_epoch);
                })
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_tta'))
        {
            $columns[] = Column::make("TTA", "answer_epoch")
                ->format(function ($value, $row, Column $column) {
                    return $row->tta;
                })
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_duration'))
        {
            $columns[] = Column::make("Duration", "duration")
                ->format(function ($value, $row, Column $column) {
                    $seconds = $row->duration;
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;

                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                })
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_pdd'))
        {
            $columns[] = Column::make("PDD", "pdd_ms")
                ->format(function ($value, $row, Column $column) {
                    $seconds = intval($row->pdd_ms) / 1000;
                    return number_format($seconds, 2).'s';
                })
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_mos'))
        {
            $columns[] = Column::make("MOS", "rtp_audio_in_mos")->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_status'))
        {
            $columns[] = Column::make("Status", "answer_stamp")
                ->format(function ($value, $row, Column $column) {
                    return ucfirst($row->status);
                })
                ->sortable();
        }

        if(auth()->user()->hasPermission('xml_cdr_hangup_cause'))
        {
            $columns[] = Column::make("Hangup cause", "hangup_cause")
            ->format(function ($value, $row, Column $column) {
                $hangup_cause = $row->hangup_cause;
                $hangup_cause = str_replace("_", " ", $hangup_cause);
                $hangup_cause = strtolower($hangup_cause);
                $hangup_cause = ucwords($hangup_cause);

                return $hangup_cause;
            })
            ->sortable();
        }

        return $columns;
    }

    public function builder(): Builder
    {
        $query = XmlCDR::query()
		->where( XmlCDR::getTableName() . ".domain_uuid", "=", Session::get("domain_uuid"))
                ->when($this->filters['direction'] ?? null, fn($q, $v) => $q->where('direction', '=', $v))
                ->when($this->filters['leg'] ?? null, fn($q, $v) => $q->where('leg', '=', $v))
                // ->when($this->filters['status'] ?? null, fn($q, $v) => $q->where('status', '=', $v))
                ->when($this->filters['status'] ?? null, function ($q, $v) {
                    return match($v)
                    {
                        'answered' => $q->whereNotNull('answer_stamp')->whereNotNull('bridge_uuid'),
                        'voicemail' => $q->whereNotNull('answer_stamp')->whereNull('bridge_uuid'),
                        'missed' => $q->where('missed_call', true),
                        'cancelled' => $q->where(function ($q) {
                            $q->where(function ($q) {
                                $q->whereIn('direction', ['inbound', 'local'])
                                ->whereNull('answer_stamp')
                                ->whereNull('bridge_uuid')
                                ->where('sip_hangup_disposition', '!=', 'send_refuse');
                            })
                            ->orWhere(function ($q) {
                                $q->whereIn('direction', ['inbound', 'local'])
                                ->whereNotNull('answer_stamp')
                                ->whereNull('bridge_uuid')
                                ->where('voicemail_message', false);
                            })
                            ->orWhere(function ($q) {
                                $q->where('direction', 'outbound')
                                ->whereNull('answer_stamp')
                                ->whereNotNull('bridge_uuid');
                            });
                        }),

                        default => $q->whereNull('answer_stamp')->whereNull('bridge_uuid')->where('duration', 0),
                    };
                })
                ->when($this->filters['extension'] ?? null, fn($q, $v) => $q->where('extension.extension', '=', $v))
                ->when($this->filters['caller_id_name'] ?? null, fn($q, $v) => $q->where('caller_id_name', '=', $v))
                ->when($this->filters['caller_id_number'] ?? null, fn($q, $v) => $q->where('caller_id_number', '=', $v))
                ->when($this->filter['start_range_from'] ?? null, fn($q, $v) => $q->where('start_stamp', '>=', $v))
                ->when($this->filters['start_range_to'] ?? null, fn($q, $v) => $q->where('start_stamp', '<', $v))
                ->when($this->filters['duration_min'] ?? null, fn($q, $v) => $q->where('duration', '>=', $v))
                ->when($this->filters['duration_max'] ?? null, fn($q, $v) => $q->where('duration', '<=', $v))
                ->when($this->filters['caller_destination'] ?? null, fn($q, $v) => $q->where('caller_destination', '=', $v))
                ->when($this->filters['destination_number'] ?? null, fn($q, $v) => $q->where('destination_number', '=', $v))
                ->when($this->filters['tta_min'] ?? null, fn($q, $v) => $q->where(DB::raw('answer_epoch - start_epoch'), '>=', $v))
                ->when($this->filters['tta_max'] ?? null, fn($q, $v) => $q->where(DB::raw('answer_epoch - start_epoch'), '<=', $v))
                ->when($this->filters['hangup_cause'] ?? null, fn($q, $v) => $q->where('hangup_cause', '=', $v))
                ->when($this->filters['recording'] ?? null, function ($q, $v) {
                    return match ($v)
                    {
                        'true' => $q->whereNotNull('record_path')->whereNotNull('record_name'),
                        'false' => $q->where(function ($query) {
                            $query->whereNull('record_path')->orWhereNull('record_name');
                        }),
                        default => null,
                    };
                })
                ->when($this->filters['order_field'] ?? null, fn($q, $v) => $q->orderBy($this->filters['order_field'], $this->filters['order_sort'] ?? 'asc'))
                ->with("extension")
                ->orderBy("start_epoch", "desc");
        	if(App::hasDebugModeEnabled()){
//			$query->dump();
		}
        return $query;
    }
}
