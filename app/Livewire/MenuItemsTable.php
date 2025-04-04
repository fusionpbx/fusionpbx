<?php

namespace App\Livewire;

use App\Http\Controllers\MenuController;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MenuItemsTable extends DataTableComponent
{
    protected $model = MenuItem::class;

    protected $menu_uuid = null;

    public function mount($menu_uuid)
    {
        $this->menu_uuid = $menu_uuid;
    }

    public function configure(): void
    {
		$this->setPaginationStatus(false);

        $canEdit = auth()->user()->hasPermission('menu_item_edit');
        $this->setPrimaryKey('menu_item_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search MenuItems')
            ->setTableRowUrl(function($row) use ($canEdit) {
                return $canEdit
                    ? route('menuitems.edit', $row->menu_item_uuid)
                    : null;
            });
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('menu_item_edit')) {
            $bulkActions['markProtected'] = 'Mark as Protected';
            $bulkActions['markUnprotected'] = 'Mark as Unprotected';
        }

        if (auth()->user()->hasPermission('menu_item_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('menu_item_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markProtected()
    {
        if (!auth()->user()->hasPermission('menu_item_edit')) {
            session()->flash('error', 'You do not have permission to mark menu items as protected.');
            return;
        }

        $selectedRows = $this->getSelected();

        MenuItem::whereIn('menu_item_uuid', $selectedRows)->update(['menu_item_protected' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The menu items were successfully protected.');
    }

    public function markUnprotected()
    {
        if (!auth()->user()->hasPermission('menu_item_edit')) {
            session()->flash('error', 'You do not have permission to mark menu items as unprotected.');
            return;
        }

        $selectedRows = $this->getSelected();

        MenuItem::whereIn('menu_item_uuid', $selectedRows)->update(['menu_item_protected' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The menu items were successfully unprotected.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('menu_item_delete')) {
            session()->flash('error', 'You do not have permission to delete menu items.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            MenuItem::whereIn('menu_item_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'MenuItems successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the menu items: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('menu_item_add')) {
            session()->flash('error', 'You do not have permission to copy menu items.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $menu_item_uuid) {
                $originalMenuItem = MenuItem::findOrFail($menu_item_uuid);

                $newMenuItem = $originalMenuItem->replicate();
                $newMenuItem->menu_uuid = Str::uuid();
                $newMenuItem->menu_description = $newMenuItem->menu_item_title . ' (Copy)';
                $newMenuItem->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the menu items: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "menu_item_uuid")->hideIf(true),

            Column::make("Name", "menu_item_title")->searchable(),

            Column::make("Parent", "menu_item_parent_uuid")
                ->format(function ($value, $row, Column $column) {
					$parents = [];
					foreach($row->parent as $parent)
					{
						$parents[] = $parent->menu_item_title;
					}
					return implode(", ", $parents);
                }),

            Column::make("Groups", "menu_item_uuid")
                ->format(function ($value, $row, Column $column) {
					$groups = [];
					foreach($row->groups as $group)
					{
						$groups[] = $group->group_name;
					}

					return implode(", ", $groups);
                }),

            BooleanColumn::make("Protected", "menu_item_protected")
        ];
    }

    public function builder(): Builder
    {
        $query = MenuItem::query()
				->where("menu_uuid", "=", $this->menu_uuid)
                ->orderBy('menu_item_title', 'asc');
        return $query;
    }
}
