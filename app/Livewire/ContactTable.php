<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Contact;
use App\Models\ContactAddress;
use App\Models\ContactAttachment;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\ContactRelation;
use App\Models\ContactUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ContactTable extends DataTableComponent
{
    protected $model = Contact::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('contact_edit');
        $tableConfig = $this->setPrimaryKey('contact_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Contacts')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();
         if ($canEdit) {
             $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                 return route('contacts.edit', $row->contact_uuid);
             });
         }
    }

    public function columns(): array
    {
        return [
            Column::make("Contact uuid", "contact_uuid")
                ->sortable()
                ->hideIf(true)
                ->searchable(),
            Column::make("Type", "contact_type")
                ->sortable(),
            Column::make("Organization", "contact_organization")
                ->sortable(),
            Column::make("First Name", "contact_name_given")
                ->sortable(),
            Column::make("Last Name", "contact_name_family")
                ->sortable(),
            Column::make("Nickname", "contact_nickname")
                ->sortable(),
            Column::make("Title", "contact_title")
                ->sortable(),
            Column::make("Role", "contact_role")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $user = auth()->user();
        $query = Contact::query();

        if (!$user->hasPermission('contact_all')) {
            $query->where(function ($q) use ($user) {
                $q->where('domain_uuid', $user->domain_uuid)
                    ->orWhereNull('domain_uuid');
            });
        }


        if (!$user->hasPermission('contact_domain_view')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('groups', function ($q2) use ($user) {
                    $q2->where('domain_uuid', $user->domain_uuid)
                        ->whereIn('group_uuid', $user->group_uuids);
                })->orWhereHas('users', function ($q3) use ($user) {
                    $q3->where('domain_uuid', $user->domain_uuid)
                        ->where('user_uuid', $user->user_uuid);
                });
            });
        }

        return $query;
    }

    public function bulkActions(): array
    {
        $bulkActions = [];
        $bulkActions['bulkDelete'] = 'Delete';
        return $bulkActions;
    }

    public function bulkDelete(): void
    {
        if(!auth()->user()->hasPermission('contact_delete')) {
            session()->flash('error', 'You do not have permission to delete contacts.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Contact::whereIn('contact_uuid', $selectedRows)->delete();
            ContactEmail::whereIn('contact_uuid', $selectedRows)->delete();
            ContactPhone::whereIn('contact_uuid', $selectedRows)->delete();
            ContactAddress::whereIn('contact_uuid', $selectedRows)->delete();
            ContactUrl::whereIn('contact_uuid', $selectedRows)->delete();
            ContactRelation::whereIn('contact_uuid', $selectedRows)->delete();
            ContactAttachment::whereIn('contact_uuid', $selectedRows)->delete();
            
            DB::commit();
            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Contacts deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'An error occurred while deleting contacts: ' . $e->getMessage());
            throw $e; 
        }
    }


    
}
