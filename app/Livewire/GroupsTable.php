<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupsTable extends DataTableComponent
{
    protected $model = Group::class;

    public function configure(): void
    {
        $this->setPrimaryKey('group_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Groups')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setTableRowUrl(fn($row) => route('groups.edit', $row->group_uuid))
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        return [
            'markProtected' => 'Mark as Protected',
            'markUnprotected' => 'Mark as Unprotected',
            'bulkDelete' => 'Delete',
            'bulkCopy' => 'Copy',
        ];
    }

    public function markProtected()
    {
        $selectedRows = $this->getSelected();

        Group::whereIn('group_uuid', $selectedRows)->update(['group_protected' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The groups were successfully protected.');
    }

    public function markUnprotected()
    {
        $selectedRows = $this->getSelected();

        Group::whereIn('group_uuid', $selectedRows)->update(['group_protected' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The groups were successfully unprotected.');
    }


    public function bulkDelete()
    {
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

    public function bulkCopy()
    {

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
            session()->flash('error', 'There was a problem copying the groups: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("Name", "group_name")
                ->sortable()
                ->searchable(),

            Column::make("Permissions", "group_uuid")
                ->format(function ($value) {
                    $group = Group::find($value);
                    $totalPermissions = $group->permissions->count();

                    return $totalPermissions;
                })
                ->searchable(),

            Column::make("Mermbers", "group_uuid")
                ->format(function ($value) {
                    $group = Group::find($value);
                    $totalUsers = $group->users->count();

                    return $totalUsers;
                })
                ->searchable(),

            Column::make("Level", "group_level")
                ->sortable(),

            BooleanColumn::make("Protected", "group_protected")
                ->sortable(),

            Column::make("Description", "group_description")
                ->searchable()
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Group::query()
                ->with('permissions')
                ->with('users')
                ->orderBy('group_name', 'asc');
        return $query;
    }
}
