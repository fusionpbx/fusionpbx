<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\DeviceLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class DeviceExport extends Component
{
    public $selectedColumnGroups = [];
    public $selectAllDevices = false;
    public $selectAllDeviceLines = false;
    
    public $availableColumns = [
        'devices' => [
            'device_uuid' => 'device_uuid',
            'device_profile_uuid' => 'device_profile_uuid',
            'device_mac_address' => 'device_mac_address',
            'device_label' => 'device_label',
            'device_vendor' => 'Device Vendor',
            'device_template' => 'Device Template',
            'device_enabled_date' => 'Device Enabled Date',
            'device_username' => 'Device Username',
            'device_password' => 'Device Password',
            'device_uuid_alternate' => 'Device UUID Alternate',
            'device_provisioned_date' => 'Device Provisioned Date',
            'device_provisioned_method' => 'Device Provisioned Method',
            'device_provisioned_ip' => 'Device Provisioned IP',
            'device_enabled' => 'Device Enabled',
            'device_description' => 'Device Description',
        ],
        'device_lines' => [
            'device_line_uuid' => 'Device Line UUID',
            'line_number' => 'Line Number',
            'server_address' => 'Server Address',
            'server_address_primary' => 'Server Address Primary',
            'server_address_secondary' => 'Server Address Secondary',
            'outbound_proxy_primary' => 'Outbound Proxy Primary',
            'outbound_proxy_secondary' => 'Outbound Proxy Secondary',
            'display_name' => 'Display Name',
            'user_id' => 'User ID',
            'auth_id' => 'Auth ID',
            'password' => 'Password',
            'sip_port' => 'SIP Port',
            'sip_transport' => 'SIP Transport',
            'register_expires' => 'Register Expires',
            'shared_line' => 'Shared Line',
            'enabled' => 'Enabled',
        ]
    ];

    public function mount()
    {
        foreach (array_keys($this->availableColumns) as $table) {
            $this->selectedColumnGroups[$table] = [];
        }
    }

    public function updatedSelectAllDevices()
    {
        if ($this->selectAllDevices) {
            $this->selectedColumnGroups['devices'] = array_keys($this->availableColumns['devices']);
            
        } else {
            $this->selectedColumnGroups['devices'] = [];
        }
    }

    public function updatedSelectAllDeviceLines()
    {
        if ($this->selectAllDeviceLines) {
            $this->selectedColumnGroups['device_lines'] = array_keys($this->availableColumns['device_lines']);
        } else {
            $this->selectedColumnGroups['device_lines'] = [];
        }
    }

    public function updatedSelectedColumnGroups()
    {
        $this->selectAllDevices = count($this->selectedColumnGroups['devices']) === count($this->availableColumns['devices']);
        $this->selectAllDeviceLines = count($this->selectedColumnGroups['device_lines']) === count($this->availableColumns['device_lines']);
    }

    public function exportDevices()
    {
        $totalSelected = array_sum(array_map('count', $this->selectedColumnGroups));
        
        if ($totalSelected === 0) {
            session()->flash('error', 'Please select at least one column to export.');
            return;
        }

        
        $validatedColumns = [];
        foreach ($this->selectedColumnGroups as $table => $columns) {
            if (!empty($columns) && isset($this->availableColumns[$table])) {
                $validatedColumns[$table] = array_intersect($columns, array_keys($this->availableColumns[$table]));
            }
        }

        if (empty($validatedColumns)) {
            session()->flash('error', 'Invalid columns selected.');
            return;
        }

        return $this->downloadCsv($validatedColumns);
    }

    private function downloadCsv($columnGroups)
    {
        $fileName = 'device_export_' . date('Y-m-d') . '.csv';
        
        return response()->streamDownload(function () use ($columnGroups) {
            $handle = fopen('php://output', 'w');
            
            $headers = [];
            foreach ($columnGroups as $table => $columns) {
                foreach ($columns as $column) {
                    $headers[] = $this->availableColumns[$table][$column] ?? $column;
                }
            }
            fputcsv($handle, $headers);
            
            $devicesColumns = $columnGroups['devices'] ?? [];
            if (!empty($devicesColumns)) {
                $query = Device::where('domain_uuid', auth()->user()->domain_uuid);
                
                if (!empty($columnGroups['device_lines'])) {
                    $deviceLinesColumns = $columnGroups['device_lines'];
                    
                    $query->leftJoin('v_device_lines', 'v_devices.device_uuid', '=', 'v_device_lines.device_uuid')
                          ->select(array_merge(
                              array_map(fn($col) => 'devices.' . $col, $devicesColumns),
                              array_map(fn($col) => 'v_device_lines.' . $col, $deviceLinesColumns)
                          ));
                } else {
                    $query->select($devicesColumns);
                }
                
                $query->chunk(1000, function ($devices) use ($handle, $columnGroups) {
                    foreach ($devices as $device) {
                        $row = [];
                        
                        if (!empty($columnGroups['devices'])) {
                            foreach ($columnGroups['devices'] as $column) {
                                $row[] = $device->$column ?? '';
                            }
                        }
                        
                        if (!empty($columnGroups['device_lines'])) {
                            foreach ($columnGroups['device_lines'] as $column) {
                                $row[] = $device->$column ?? '';
                            }
                        }
                        
                        fputcsv($handle, $row);
                    }
                });
            }
            
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function getTotalSelectedColumns()
    {
        return array_sum(array_map('count', $this->selectedColumnGroups));
    }

    public function getTotalAvailableColumns()
    {
        return array_sum(array_map('count', $this->availableColumns));
    }

    public function goBack()
    {
        return redirect()->route('devices.index'); 
    }

    public function render()
    {
        return view('livewire.device-export');
    }
}