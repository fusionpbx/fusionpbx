<?php

namespace App\Livewire;

use App\Facades\FreeSwitchRegistration;
use App\Models\SipProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RegistrationsTable extends DataTableComponent
{
    protected $registrations = [];
    protected $model = SipProfile::class;


    public function builder(): Builder
    {
        $query = SipProfile::query()->select([
            DB::raw("'registered' as is_registered"),
            DB::raw("'user' as user"),
            DB::raw("'agent' as agent"),
            DB::raw("'contact' as contact"),
            DB::raw("'connection_status' as connection_status"),
            DB::raw("'lan_ip' as lan_ip"),
            DB::raw("'registration_ip' as registration_ip"),
            DB::raw("'network_port' as network_port"),
            DB::raw("'connection_status' as connection_status"),
            DB::raw("'ping_time' as ping_time"),

        ])->from(DB::raw('(SELECT *, "registered", "user", "agent", "contact", "connection_status", "ping_time", "lan_ip", "registration_ip", "network_port", "connection_status" as is_registered FROM v_sip_profiles) as v_sip_profiles'))
            ->withRegistrationStatus();

        $showAll = request()->input('show') === 'all';

        if (!$showAll && !auth()->user()->hasPermission('registration_all')) {
            $query->where('sip_profile_uuid', auth()->user()->sip_profile_uuid);
            
        }

        return $query;
    }

    public function configure(): void
    {
        $this->setPrimaryKey('sip_profile_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Registrations')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled()
            ->setRefreshTime(30000);
    }

    public function columns(): array
    {
        return [
            Column::make("SIP Profile", "sip_profile_uuid")
                ->sortable()
                ->hideIf(true)
                ->searchable(),


            Column::make("User", "user")
                ->format(function ($value, $row, Column $column) {
                    $userParts = explode('@', $value ?? '');
                    return $userParts[0] ?? '';
                })
                ->sortable()
                ->searchable(),

            Column::make("Agent", "agent")
                ->format(function ($value, $row, Column $column) {
                    return strlen($value) > 30 ? substr($value, 0, 27) . '...' : $value;
                })
                ->html()
                ->sortable()
                ->searchable(),

            Column::make("Contact", "contact")
                ->format(function ($value, $row, Column $column) {
                    return strlen($value) > 30 ? substr($value, 0, 27) . '...' : $value;
                })
                ->html()
                ->sortable()
                ->searchable(),

            Column::make("LAN IP", "lan_ip")
                ->sortable()
                ->searchable(),

            Column::make("IP", "registration_ip")
                ->sortable()
                ->searchable(),


            Column::make("Port", "network_port")
                ->sortable(),



            Column::make("Status", "connection_status")
                ->format(function ($value, $row, Column $column) {
                    $statusClass = $value === 'Registered' ? 'success' : 'warning';
                    return '<span class="badge bg-' . $statusClass . '">' . $value . '</span>';
                })
                ->html()
                ->sortable(),

            Column::make("Ping Time", "ping_time")
                ->format(function ($value, $row, Column $column) {
                    return $value ? $value . ' ms' : '';
                })
                ->sortable(),


            Column::make("Profile", "sip_profile_name")
                ->sortable()
                ->searchable(),

        ];
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        $bulkActions['unregister'] = 'Unregister';
        $bulkActions['provision'] = 'Provision';
        $bulkActions['reboot'] = 'Reboot';

        return $bulkActions;
    }

    public function showDetails($username, $domain)
    {

        $registration = null;
        foreach ($this->registrations as $reg) {
            $userParts = explode('@', $reg['user'] ?? '');
            if ($userParts[0] === $username && $userParts[1] === $domain) {
                $registration = $reg;
                break;
            }
        }

        if (!$registration) {
            session()->flash('error', "Registration details not found for $username@$domain");
            return;
        }

        $this->dispatch('open-registration-modal', $registration);
    }

    public function unregister()
    {
        try {

            $profilesUuids = $this->getSelected();

            $profiles = SipProfile::query()
                ->withRegistrationStatus()->whereIn('sip_profile_uuid', $profilesUuids)->get();

            FreeSwitchRegistration::executeUnregisterAction($profiles);

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            throw $e;
            session()->flash('error', 'Error unregistering device: ' . $e->getMessage());
        }
    }

    public function provision()
    {
        try {

            $profilesUuids = $this->getSelected();

            $profiles = SipProfile::query()
                ->withRegistrationStatus()->whereIn('sip_profile_uuid', $profilesUuids)->get();

            FreeSwitchRegistration::executeProvisionAction($profiles);

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function reboot()
    {
        try {

            $profilesUuids = $this->getSelected();

            $profiles = SipProfile::query()
                ->withRegistrationStatus()->whereIn('sip_profile_uuid', $profilesUuids)->get();

            FreeSwitchRegistration::executeRebootAction($profiles);

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            throw $e;
        }
    }


}
