<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class UsersTable extends DataTableComponent
{
    protected $model = User::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('user_edit');
        $this->setPrimaryKey('user_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Users')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setTableRowUrl(function($row) use ($canEdit) {
                return $canEdit
                    ? route('users.edit', $row->user_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('user_edit')) {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('user_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if(auth()->user()->hasPermission('user_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;


    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('user_edit')) {
            session()->flash('error', 'You do not have permission to mark users as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        User::whereIn('user_uuid', $selectedRows)->update(['user_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The users were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('user_edit')) {
            session()->flash('error', 'You do not have permission to mark users as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        User::whereIn('user_uuid', $selectedRows)->update(['user_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The users were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('user_delete')) {
            session()->flash('error', 'You do not have permission to delete users.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            User::whereIn('user_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Users successfully deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the users: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('user_add')) {
            session()->flash('error', 'You do not have permission to copy users.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            foreach ($selectedRows as $userUuid) {
                $originalUser = User::findOrFail($userUuid);

                $newUser = $originalUser->replicate();
                $newUser->user_uuid = Str::uuid();
                $newUser->user_description = $newUser->user_description . ' (Copy)';
                $newUser->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the users: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [

            Column::make("UUID", "user_uuid")->hideIf(true),

            Column::make("User", "user_email")
                ->format(function ($value, $row, Column $column) {
                    return $row->username.'<br/><small>'.$row->user_email.'</small>';
                })
                ->sortable()
                ->searchable(),

            Column::make("Groups", "user_uuid")
                ->format(function ($value, $row, Column $column) {
					$groups = [];
					foreach($row->groups as $group)
					{
						$groups[] = $group->group_name;
					}

					return implode(", ", $groups);
                }),

            BooleanColumn::make("Enabled", "user_enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = User::where('domain_uuid', Session::get('domain_uuid'))
				->with('groups')
                ->orderBy('username', 'asc');
        return $query;
    }
}
