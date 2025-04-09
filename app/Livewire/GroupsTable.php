<?php

namespace App\Livewire;

use App\Models\Domain;
use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class GroupsTable extends DataTableComponent
{
    // protected $model = Group::class;

    public function builder(): Builder
    {

        $q =  Group::leftJoin(Domain::getTableName(), Group::getTableName().'.domain_uuid', '=', Domain::getTableName().'.domain_uuid')
            ->select('group_uuid', 'group_protected', 'group_level', 'group_description', DB::raw("CONCAT(".Domain::getTableName().".group_name,'@', IFNULL(v_domains.domain_name,'Global')) AS group_name"))
            ->withCount('permissions')
            ->withCount('users')
            ->orderBy('group_name');

        if(App::hasDebugModeEnabled()){
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] query: '.$q->toRawSql());
        }
        return $q;
    }

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('group_edit');
        $tableConfig = $this->setPrimaryKey('group_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Groups')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function($row) use ($canEdit) {
                return route('groups.edit', $row->group_uuid);
            });
        }
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('group_edit')) {
            $bulkActions['markProtected'] = 'Mark as Protected';
            $bulkActions['markUnprotected'] = 'Mark as Unprotected';
        }

        if (auth()->user()->hasPermission('group_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('group_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markProtected()
    {
        if (!auth()->user()->hasPermission('group_edit')) {
            session()->flash('error', 'You do not have permission to mark groups as protected.');
            return;
        }

        $selectedRows = $this->getSelected();

        Group::whereIn('group_uuid', $selectedRows)->update(['group_protected' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The groups were successfully protected.');
    }

    public function markUnprotected()
    {
        if (!auth()->user()->hasPermission('group_edit')) {
            session()->flash('error', 'You do not have permission to mark groups as unprotected.');
            return;
        }

        $selectedRows = $this->getSelected();

        Group::whereIn('group_uuid', $selectedRows)->update(['group_protected' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The groups were successfully unprotected.');
    }

    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('group_delete')) {
            session()->flash('error', 'You do not have permission to delete groups.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Group::whereIn('group_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Groups successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the groups: ' . $e->getMessage());
        }
    }

    /**
     * @throws \Throwable
     */
    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('group_add')) {
            session()->flash('error', 'You do not have permission to copy groups.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $groupUuid) {
                $originalGroup = Group::findOrFail($groupUuid);

                $newGroup = $originalGroup->replicate();
                $newGroup->group_uuid = Str::uuid();
                $newGroup->group_description = $newGroup->group_description . ' (Copy)';
                $newGroup->save();

                $permissions = $originalGroup->permissions()->get();
                $permissionsToSync = [];
                foreach ($permissions as $permission) {
                    $permissionsToSync[$permission->permission_name] = [
                        'group_permission_uuid' => Str::uuid(),
                        'permission_assigned' => $permission->pivot->permission_assigned,
                        'permission_protected' => $permission->pivot->permission_protected
                    ];
                }
                $newGroup->permissions()->sync($permissionsToSync);
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function columns(): array
    {
        $columns = [
            Column::make("Name", "group_name")
                ->sortable()
                ->searchable(),
        ];

        if (auth()->user()->hasPermission('group_permission_view')) {
            $columns[] = Column::make("Permissions", "group_uuid")
                ->format(function ($value, $row, Column $column) {
                    return '<a href="'.route('permissions.index', ['group_uuid' => $value]).'" class="text-primary underline">'
                    .$row->permissions_count.
                    '</a>';
                })
                ->html();
        }

        $columns = array_merge($columns, [
            Column::make("Members", "group_uuid")
                ->format(function ($value, $row, Column $column) {
                    return $row->users_count;
                }),

            Column::make("Level", "group_level")
                ->sortable(),

            BooleanColumn::make("Protected", "group_protected")
                ->sortable(),

            Column::make("Description", "group_description")
                ->searchable()
                ->sortable(),
        ]);

        return $columns;
    }

    /*
    public function builder(): Builder
    {
        return Group::query()
            ->withCount('permissions')
            ->withCount('users')
            ->orderBy('group_name');
    }
    */
}
