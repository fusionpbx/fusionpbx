<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MenusTable extends DataTableComponent
{
    protected $model = Menu::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('menu_edit');
        $this->setPrimaryKey('menu_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Menus')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setTableRowUrl(function($row) use ($canEdit) {
                return $canEdit
                    ? route('menus.edit', $row->menu_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('menu_edit')) {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('menu_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('menu_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;


    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('menu_edit')) {
            session()->flash('error', 'You do not have permission to mark menus as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Menu::whereIn('menu_uuid', $selectedRows)->update(['menu_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The menus were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('menu_edit')) {
            session()->flash('error', 'You do not have permission to mark menus as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Menu::whereIn('menu_uuid', $selectedRows)->update(['menu_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The menus were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('menu_delete')) {
            session()->flash('error', 'You do not have permission to delete menus.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Menu::whereIn('menu_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Menus successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the menus: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('menu_add')) {
            session()->flash('error', 'You do not have permission to copy menus.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $menuUuid) {
                $originalMenu = Menu::findOrFail($menuUuid);

                $newMenu = $originalMenu->replicate();
                $newMenu->menu_uuid = Str::uuid();
                $newMenu->menu_description = $newMenu->menu_description . ' (Copy)';
                $newMenu->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the menus: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "menu_uuid")->hideIf(true),

            Column::make("Name", "menu_name")
                ->sortable()
                ->searchable(),

            Column::make("Description", "menu_description")
                ->searchable()
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Menu::query()
                ->orderBy('menu_name', 'asc');
        return $query;
    }
}
