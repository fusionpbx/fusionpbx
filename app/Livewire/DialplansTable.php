<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\Dialplan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DialplansTable extends DataTableComponent
{
    //protected $model = Dialplan::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('dialplan_edit');
        $this->setPrimaryKey('dialplan_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Dialplans')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setTableRowUrl(function($row) use ($canEdit) {
                return $canEdit
                    ? route('dialplans.edit', $row->dialplan_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('dialplan_edit')) {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('dialplan_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('dialplan_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;


    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('dialplan_edit')) {
            session()->flash('error', 'You do not have permission to mark dialplans as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Dialplan::whereIn('dialplan_uuid', $selectedRows)->update(['dialplan_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The dialplans were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('dialplan_edit')) {
            session()->flash('error', 'You do not have permission to mark dialplans as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Dialplan::whereIn('dialplan_uuid', $selectedRows)->update(['dialplan_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The dialplans were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('dialplan_delete')) {
            session()->flash('error', 'You do not have permission to delete dialplans.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Dialplan::whereIn('dialplan_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Dialplans successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the dialplans: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('dialplan_add')) {
            session()->flash('error', 'You do not have permission to copy dialplans.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $dialplanUuid) {
                $originalDialplan = Dialplan::findOrFail($dialplanUuid);

                $newDialplan = $originalDialplan->replicate();
                $newDialplan->dialplan_uuid = Str::uuid();
                $newDialplan->dialplan_description = $newDialplan->dialplan_description . ' (Copy)';
                $newDialplan->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the dialplans: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "dialplan_uuid")->hideIf(true),

            Column::make("Name", "dialplan_name")
                ->sortable()
                ->searchable(),

            Column::make("Number", "dialplan_number")
                ->sortable()
                ->searchable(),

            Column::make("Context", "dialplan_context")
                ->searchable()
                ->sortable(),

            Column::make("Order", "dialplan_order")
                ->searchable()
                ->sortable(),

            BooleanColumn::make("Enabled", "dialplan_enabled")
                ->sortable(),


        ];
    }

    public function builder(): Builder
    {
	$showAll = request()->query('show') === 'all';
	$appUuid = request()->query('app_uuid') ?? '';
	$context = request()->query('context') ?? '';
	$query = Dialplan::query()
		->when( $showAll && auth()->user()->hasPermission('dialplan_all'),
			function($query){},
			function($query){
				$query->where(function ($q) {
					$q->where('domain_uuid', Session::get('domain_uuid'))
						->orWhereNull('domain_uuid');
				});
			}
		)
		->when ( !Str::isUuid($appUuid),
			function($query){
				$query->where('app_uuid','<>','c03b422e-13a8-bd1b-e42b-b6b9b4d27ce')
					->where('dialplan_context','<>', 'public');
				//TODO: verify if it is a good idea to hind outbound rates: app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'
			},
			function($query) use($appUuid){
				if ($appUuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4'){
					$query->where('app_uuid','c03b422e-13a8-bd1b-e42b-b6b9b4d27ce')
						->orWhere('dialplan_context','public');
				}
				else{
					$query->where('app_uuid', $appUuid);
				}
			}
		)
		->when (!empty($context),
			function($query) use($context){
				$query->where('dialplan_context', $context);
			}
		)
                ->orderBy('dialplan_order', 'asc')
                ->orderBy('dialplan_name', 'asc');
        return $query;
    }
}
