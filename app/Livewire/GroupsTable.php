<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\On;

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
            'bulkUpdate' => 'Update'
        ];
    }


    public function markProtected()
    {
        $selectedRows = $this->getSelected();

        Group::whereIn('group_uuid', $selectedRows)->update(['group_protected' => true]);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'Los grupos fueron protegidos exitosamente.');
    }

    public function markUnprotected()
    {
        $selectedRows = $this->getSelected();

        Group::whereIn('group_uuid', $selectedRows)->update(['group_protected' => false]);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'Los grupos fueron desprotegidos exitosamente.');
    }

    public function bulkActionConfirm($action)
    {
        if ($action === 'update') {
            $this->dispatch('show-bulk-update-modal');
        } elseif ($action === 'delete') {
            $this->dispatch('confirm-bulk-delete');
        }
    }


    public function bulkDelete()
    {
        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            $protectedGroups = Group::whereIn('group_uuid', $selectedRows)
                ->where('group_protected', true)
                ->count();

            if ($protectedGroups > 0) {
                $this->addError('bulk_delete', 'No se pueden eliminar grupos protegidos');
                DB::rollBack();
                return;
            }

            Group::whereIn('group_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Grupos eliminados exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Hubo un problema al eliminar los grupos: ' . $e->getMessage());
        }
    }

    #[On('bulk-update')]
    public function bulkUpdate($data)
    {
        $selectedRows = $this->getSelected();
    
        $validator = Validator::make($data, [
            'group_level' => 'sometimes|integer',
            'group_description' => 'sometimes|string|max:255'
        ]);
    
        if ($validator->fails()) {
            $this->addError('bulk_update', $validator->errors()->first());
            return;
        }
    
        try {
            DB::beginTransaction();
    
            $updateData = array_filter([
                'group_level' => $data['group_level'] ?? null,
                'group_description' => $data['group_description'] ?? null
            ]);
    
            $protectedGroups = Group::whereIn('group_uuid', $selectedRows)
                ->where('group_protected', true)
                ->count();
    
            if ($protectedGroups > 0) {
                $this->addError('bulk_update', 'No se pueden modificar grupos protegidos');
                DB::rollBack();
                return;
            }
    
            Group::whereIn('group_uuid', $selectedRows)->update($updateData);
    
            DB::commit();
    
            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Grupos actualizados exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Hubo un problema al actualizar los grupos: ' . $e->getMessage());
        }
    }

    public function bulkUpdateModal()
    {
        $this->dispatch('open-modal', 'bulk-update-modal');
    }

    public function columns(): array
    {
        return [
            Column::make("Name", "group_name")
                ->sortable()
                ->searchable()
                ->format(fn($value) => ucfirst($value)),

            BooleanColumn::make("Protected", "group_protected")
                ->format(function ($value) {
                    return $value ? '<span class="text-success">True</span>' : '<span class="text-danger">False</span>';
                })

                ->sortable(),

            Column::make("Level", "group_level")
                ->sortable(),

            Column::make("Description", "group_description")
                ->searchable()
                ->format(fn($value) => substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '')),

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

        ];
    }

    public function builder(): Builder
    {
        $query = Group::query()->with('permissions')->with('users');
        return $query;
    }
}
