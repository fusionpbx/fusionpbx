<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Extension;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExtensionExport extends Component
{
    public $selectedColumns = [];
    public $selectAll = false;
    
    public $availableColumns = [
        'extension_uuid' => 'Extension UUID',
        'domain_uuid' => 'Domain UUID',
        'extension' => 'Extension',
        'number_alias' => 'Number Alias',
        'password' => 'Password',
        'accountcode' => 'Account Code',
        'effective_caller_id_name' => 'Effective Caller ID Name',
        'effective_caller_id_number' => 'Effective Caller ID Number',
        'outbound_caller_id_name' => 'Outbound Caller ID Name',
        'outbound_caller_id_number' => 'Outbound Caller ID Number',
        'emergency_caller_id_name' => 'Emergency Caller ID Name',
        'emergency_caller_id_number' => 'Emergency Caller ID Number',
        'directory_first_name' => 'Directory First Name',
        'directory_last_name' => 'Directory Last Name',
        'directory_visible' => 'Directory Visible',
        'directory_exten_visible' => 'Directory Extension Visible',
        'limit_max' => 'Limit Max',
        'limit_destination' => 'Limit Destination',
        'missed_call_app' => 'Missed Call App',
        'missed_call_data' => 'Missed Call Data',
        'user_context' => 'User Context',
        'toll_allow' => 'Toll Allow',
        'call_timeout' => 'Call Timeout',
        'call_group' => 'Call Group',
        'call_screen_enabled' => 'Call Screen Enabled',
        'user_record' => 'User Record',
        'hold_music' => 'Hold Music',
        'auth_acl' => 'Auth ACL',
        'cidr' => 'CIDR',
        'sip_force_contact' => 'SIP Force Contact',
        'nibble_account' => 'Nibble Account',
        'sip_force_expires' => 'SIP Force Expires',
        'mwi_account' => 'MWI Account',
        'sip_bypass_media' => 'SIP Bypass Media',
        'unique_id' => 'Unique ID',
        'dial_string' => 'Dial String',
        'dial_user' => 'Dial User',
        'dial_domain' => 'Dial Domain',
        'do_not_disturb' => 'Do Not Disturb',
        'forward_all_destination' => 'Forward All Destination',
        'forward_all_enabled' => 'Forward All Enabled',
        'forward_busy_destination' => 'Forward Busy Destination',
        'forward_busy_enabled' => 'Forward Busy Enabled',
        'forward_no_answer_destination' => 'Forward No Answer Destination',
        'forward_no_answer_enabled' => 'Forward No Answer Enabled',
        'follow_me_uuid' => 'Follow Me UUID',
        'enabled' => 'Enabled',
        'description' => 'Description',
        'absolute_codec_string' => 'Absolute Codec String',
        'forward_user_not_registered_destination' => 'Forward User Not Registered Destination',
        'forward_user_not_registered_enabled' => 'Forward User Not Registered Enabled',
    ];

    public function mount()
    {
        // if (!Gate::allows('extension_export')) {
        //     abort(403, 'Access denied');
        // }
    }



    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedColumns = array_keys($this->availableColumns);
        } else {
            $this->selectedColumns = [];
        }
    }

    public function updatedSelectedColumns()
    {
        $this->selectAll = count($this->selectedColumns) === count($this->availableColumns);
    }

    public function exportExtensions()
    {
        if (empty($this->selectedColumns)) {
            session()->flash('error', 'Please select at least one column to export.');
            return;
        }

        $validColumns = array_intersect($this->selectedColumns, array_keys($this->availableColumns));
        
        if (empty($validColumns)) {
            session()->flash('error', 'Invalid columns selected.');
            return;
        }

        return $this->downloadCsv($validColumns);
    }

    private function downloadCsv($columns)
    {
        $fileName = 'extension_export_' . date('Y-m-d') . '.csv';
        
        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            
            
            $headers = [];
            foreach ($columns as $column) {
                $headers[] = $this->availableColumns[$column] ?? $column;
            }
            fputcsv($handle, $headers);
            

            Extension::where('domain_uuid', auth()->user()->domain_uuid)
                ->select($columns)
                ->chunk(1000, function ($extensions) use ($handle, $columns) {
                    foreach ($extensions as $extension) {
                        $row = [];
                        foreach ($columns as $column) {
                            $row[] = $extension->$column;
                        }
                        fputcsv($handle, $row);
                    }
                });
            
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function goBack()
    {
        return redirect()->route('extensions.index');
    }

    public function render()
    {
        return view('livewire.extension-export');
    }
}