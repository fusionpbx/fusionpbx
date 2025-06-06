<?php

namespace App\Livewire;

use App\Models\Phrase;
use App\Models\PhraseDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class PhrasesTable extends DataTableComponent
{
    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('phrase_edit');
        $this->setPrimaryKey('phrase_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Phrases')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
	    ->setDefaultPerPage(100)
            ->setTableRowUrl(function($row) use ($canEdit) {
                return $canEdit
                    ? route('phrases.edit', $row->phrase_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('phrase_edit')) {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('phrase_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('phrase_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;


    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('phrase_edit')) {
            session()->flash('error', 'You do not have permission to mark phrases as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Phrase::whereIn('phrase_uuid', $selectedRows)->update(['phrase_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The phrases were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('phrase_edit')) {
            session()->flash('error', 'You do not have permission to mark phrases as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Phrase::whereIn('phrase_uuid', $selectedRows)->update(['phrase_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The phrases were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('phrase_delete')) {
            session()->flash('error', 'You do not have permission to delete phrases.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            PhraseDetail::whereIn('phrase_uuid', $selectedRows)->delete();
            Phrase::whereIn('phrase_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Phrases successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the phrases: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('phrase_add')) {
            session()->flash('error', 'You do not have permission to copy phrases.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $phraseUuid) {
                $originalPhrase = Phrase::findOrFail($phraseUuid);

                $newPhrase = $originalPhrase->replicate();
                $newPhrase->phrase_uuid = Str::uuid();
                $newPhrase->phrase_name = $originalPhrase->phrase_name . ' (Copy)';
                $newPhrase->phrase_language = $originalPhrase->phrase_language;
                $newPhrase->phrase_enabled = $originalPhrase->phrase_enabled;
                $newPhrase->phrase_description = $originalPhrase->phrase_description;
                $newPhrase->save();

                foreach ($originalPhrase->dialPlanDetail as $detail) {
                    $newDialPlanDetail = $detail->replicate();
                    $newDialPlanDetail->phrase_detail_uuid = Str::uuid();
                    $newDialPlanDetail->phrase_uuid = $originalPhrase->phrase_uuid;
                    $newDialPlanDetail->domain_uuid = $originalPhrase->domain_uuid;
                    $newDialPlanDetail->phrase_detail_function = $originalPhrase->phrase_detail_function;
                    $newDialPlanDetail->phrase_detail_data = $originalPhrase->phrase_detail_data;
                    $newDialPlanDetail->phrase_detail_order = $originalPhrase->phrase_detail_order;
                    $newDialPlanDetail->save();
                }
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the phrases: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "phrase_uuid")->hideIf(true),

            Column::make("Name", "phrase_name")
                ->sortable()
                ->searchable(),

            BooleanColumn::make("Enabled", "phrase_enabled")
                ->sortable(),

        ];
    }

    public function builder(): Builder
    {
        $query = Phrase::query()
            ->where('domain_uuid', Session::get('domain_uuid'))
            ->orderBy('phrase_name', 'asc');

	    if(App::hasDebugModeEnabled())
        {
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] query: '.$query->toRawSql());
        }

        return $query;
    }
}
