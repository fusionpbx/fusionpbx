<?php

namespace App\Livewire;

use App\Models\CallBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class CallBlockTable extends DataTableComponent
{
    protected $model = CallBlock::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('call_block_edit');
        $this->setPrimaryKey('call_block_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search CallBlocks')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('callblocks.edit', $row->call_block_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('call_block_edit'))
        {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('call_block_delete'))
        {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('call_block_add'))
        {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('call_block_edit'))
        {
            session()->flash('error', 'You do not have permission to mark callblocks as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        CallBlock::whereIn('call_block_uuid', $selectedRows)->update(['call_block_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The callblocks were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('call_block_edit'))
        {
            session()->flash('error', 'You do not have permission to mark callblocks as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        CallBlock::whereIn('call_block_uuid', $selectedRows)->update(['call_block_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The callblocks were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('call_block_delete'))
        {
            session()->flash('error', 'You do not have permission to delete callblocks.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            CallBlock::whereIn('call_block_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'CallBlocks successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the callblocks: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('call_block_add'))
        {
            session()->flash('error', 'You do not have permission to copy callblocks.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $callblockUuid)
            {
                $originalCallBlock = CallBlock::findOrFail($callblockUuid);

                $newCallBlock = $originalCallBlock->replicate();
                $newCallBlock->call_block_uuid = Str::uuid();
                $newCallBlock->call_block_name = $newCallBlock->call_block_name . ' (Copy)';
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the callblocks: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("CallBlock uuid", "call_block_uuid")->hideIf(true),

            Column::make("CallBlock name", "call_block_name")
                ->searchable()
                ->sortable(),

            BooleanColumn::make("CallBlock enabled", "call_block_enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = CallBlock::query()
            ->orderBy('call_block_name', 'asc');
        return $query;
    }
}
