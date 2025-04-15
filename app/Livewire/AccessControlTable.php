<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AccessControl;
use App\Models\AccessControlNode;

class AccessControlTable extends DataTableComponent
{
    protected $model = AccessControl::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('access_control_edit');
        $tableConfig = $this->setPrimaryKey('access_control_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Access Control')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) {
                return route('accesscontrol.edit', $row->access_control_uuid);
            });
        }
    }

    public function query(): Builder
    {
        return AccessControl::query();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('access_control_edit')) {
            $bulkActions['toggleAccessControl'] = 'Toggle';
        }

        if (auth()->user()->hasPermission('access_control_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('access_control_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function toggleAccessControl()
    {
        if (!auth()->user()->hasPermission('access_control_edit')) {
            session()->flash('error', 'You do not have permission to toggle access controls.');
            return;
        }

        $selectedRows = $this->getSelected();

        AccessControl::whereIn('access_control_uuid', $selectedRows)->update([
            'access_control_default' => DB::raw("CASE WHEN access_control_default = 'allow' THEN 'deny' ELSE 'allow' END")
        ]);

        $this->clearSelected();
        $this->dispatch('refresh');

        session()->flash('success', 'Access controls successfully updated.');
    }

    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('access_control_delete')) {
            session()->flash('error', 'You do not have permission to delete access controls.');
            return;
        }

        $selectedRows = $this->getSelected();
        try {
            DB::beginTransaction();

            AccessControlNode::whereIn('access_control_uuid', $selectedRows)->delete();

            AccessControl::whereIn('access_control_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Access controls successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'Failed to delete access controls: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('access_control_add')) {
            session()->flash('error', 'You do not have permission to copy access controls.');
            return;
        }

        $selectedRows = $this->getSelected();
        try {
            DB::beginTransaction();

            foreach ($selectedRows as $accessControlUuid) {
                $originalAccessControl = AccessControl::findOrFail($accessControlUuid);

                $newAccessControl = $originalAccessControl->replicate();
                $newAccessControl->access_control_uuid = Str::uuid();
                $newAccessControl->access_control_description = $newAccessControl->access_control_description . ' (Copy)';
                $newAccessControl->save();


                foreach ($originalAccessControl->accesscontrolnodes as $node) {
                    $newNode = $node->replicate();
                    $newNode->access_control_node_uuid = Str::uuid();
                    $newNode->access_control_uuid = $newAccessControl->access_control_uuid;
                    $newNode->save();
                }
            }
            DB::commit();
            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Access controls successfully copied.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'Failed to copy access controls: ' . $e->getMessage());
        }
    }



    public function columns(): array
    {
        $columns = [
            Column::make("Access control uuid", "access_control_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Name", "access_control_name")
                ->sortable()
                ->searchable(),
            Column::make("Default", "access_control_default")
                ->sortable()
                ->searchable(),
            Column::make("Description", "access_control_description")
                ->sortable()
                ->searchable(),
        ];

        return $columns;
    }
}
