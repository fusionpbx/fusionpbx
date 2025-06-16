<?php

namespace App\Livewire;

use App\Models\Extension;
use App\Models\ExtensionUser;
use App\Models\Voicemail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
class ExtensionTable extends DataTableComponent
{
    //protected $model = Extension::class;
    public bool $show_all = false;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('extension_edit');

        $tableConfig = $this->setPrimaryKey('extension_uuid')
                    ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Extensions')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('extensions.edit', $row->extension_uuid);
            });
        }

        if(request()->has('show_all')) {
            $this->show_all = true;
        }
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('extension_edit')) {
            $bulkActions['toggleExtension'] = 'Toggle';
        }

        if (auth()->user()->hasPermission('extension_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        return $bulkActions;
    }

    public function toogleExtension(): void
    {
        if (!auth()->user()->hasPermission('extension_edit')) {
            session()->flash('error', 'You do not have permission to toggle extension status.');
            return;
        }
        $selectRows = $this->getSelected();

        if (count($selectRows) > 0) {
            Extension::whereIn('extension_uuid', $selectRows)->update(['enabled' => DB::row("CASE WHEN enabled = 'true' THEN 'false' ELSE 'true' END")]);
            session()->flash('success', 'Extensions toggled successfully.');
        } else {
            session()->flash('error', 'No extensions selected.');
        }
    }

    public function bulkDelete(): void
    {
        if (!auth()->user()->hasPermission('extension_delete')) {
            session()->flash('error', 'You do not have permission to delete extensions.');
            return;
        }

        $selectRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Extension::whereIn('extension_uuid', $selectRows)->delete();
            ExtensionUser::whereIn('extension_uuid', $selectRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bulkDeleteWithVoicemail()
    {
        if (!auth()->user()->hasPermission('extension_delete')) {
            session()->flash('error', 'You do not have permission to delete extensions.');
            return;
        }

        $selectRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Extension::whereIn('extension_uuid', $selectRows)->delete();


            ExtensionUser::whereIn('extension_uuid', $selectRows)->delete();

            $extensions = Extension::whereIn('extension_uuid', $selectRows)->get();
            $extensions->voicemail?->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function columns(): array
    {
        $showAll = request()->query('show') === 'all';

        $columns = [
            Column::make("Extension uuid", "extension_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Domain uuid", "domain_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Extension", "extension")
                ->sortable()
                ->searchable(),
            Column::make("Effective CID Name", "effective_caller_id_name")
                ->sortable()
                ->searchable(),
            Column::make("Outbound CID name", "outbound_caller_id_name")
                ->sortable()
                ->searchable(),
            Column::make("Call Group", "call_group")
                ->sortable(),
            Column::make("Account code", "accountcode")
                ->sortable()
                ->searchable(),
            BooleanColumn::make("Enabled", "enabled")
                ->sortable(),
            Column::make("Description", "description")
                ->sortable(),
        ];

        if ($this->show_all) {
            array_splice($columns, 3, 0, [
                Column::make("Domain", "domain.domain_name")
                    ->sortable()
                    ->searchable()
            ]);
        }

        return $columns;
    }

    public function builder(): Builder
    {
        $query = Extension::query();

        if ($this->show_all) {
            $query->leftJoin('v_domains', 'v_extensions.domain_uuid', '=', 'v_domains.domain_uuid')
                  ->select('v_extensions.*', 'v_domains.domain_name');
        } else {
            $query->where(function ($query) {
                $query->where('v_extensions.domain_uuid', Session::get('domain_uuid'))
                    ->orWhereNull('v_extensions.domain_uuid');
            });
        }

        return $query;
    }
}
