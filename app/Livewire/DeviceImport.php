<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\DeviceLine;
use App\Models\DeviceKey;
use App\Models\DeviceSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DeviceImport extends Component
{
    use WithFileUploads;

    public $step = 1;
    public $data = '';
    public $uploadedFile;
    public $fromRow = 2;
    public $delimiter = ',';
    public $enclosure = '"';
    public $csvData = [];
    public $headers = [];
    public $fieldMappings = [];
    public $availableFields = [];
    public $importResults = [];

    protected $rules = [
        'data' => 'required_without:uploadedFile',
        'uploadedFile' => 'required_without:data',
        'fromRow' => 'required|integer|min:1|max:99',
        'delimiter' => 'required|in:comma,pipe,semicolon,tab',
        'enclosure' => 'required|in:quote,none',
    ];

    public function mount()
    {
        $this->setupAvailableFields();
    }

    public function setupAvailableFields()
    {
        $this->availableFields = [
            'devices' => [
                'device_mac_address',
                'device_label',
                'device_vendor',
                'device_model',
                'device_firmware_version',
                'device_provision_enable',
                'device_template',
                'device_enabled',
                'device_description',
                'device_username',
                'device_password',
                'device_uuid_alternative',
                'device_address',
                'device_address_port',
                'device_address_primary',
                'device_address_secondary',
                'device_user_uuid'
            ],
            'device_lines' => [
                'line_number',
                'server_address',
                'server_address_primary',
                'server_address_secondary',
                'outbound_proxy_primary',
                'outbound_proxy_secondary',
                'display_name',
                'user_id',
                'auth_id',
                'password',
                'sip_port',
                'sip_transport',
                'register_expires',
                'enabled'
            ],
            'device_keys' => [
                'device_key_category',
                'device_key_id',
                'device_key_type',
                'device_key_line',
                'device_key_value',
                'device_key_extension',
                'device_key_protected',
                'device_key_label',
                'device_key_icon'
            ],
            'device_settings' => [
                'device_setting_category',
                'device_setting_subcategory',
                'device_setting_name',
                'device_setting_value',
                'device_setting_enabled',
                'device_setting_description'
            ],
            'users' => [
                'username'
            ]
        ];
    }

    public function continue()
    {
        $this->validate();

        if ($this->step == 1) {
            $this->processUpload();
        } elseif ($this->step == 2) {
            $this->importData();
        }
    }

    private function processUpload()
    {
        try {
            if ($this->uploadedFile) {
                $csvContent = file_get_contents($this->uploadedFile->getRealPath());
            } else {
                $csvContent = $this->data;
            }

            $filename = 'devices-import-' . Str::random(10) . '.csv';
            Storage::disk('local')->put('temp/' . $filename, $csvContent);

            $this->processCsvData($csvContent);

            $this->step = 2;

        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    private function processCsvData($csvContent)
    {
        $delimiter = $this->getDelimiterChar();
        $enclosure = $this->getEnclosureChar();

        $lines = explode("\n", $csvContent);
        $this->csvData = [];

        foreach ($lines as $index => $line) {
            if ($index >= ($this->fromRow - 1) && !empty(trim($line))) {
                $this->csvData[] = str_getcsv($line, $delimiter, $enclosure);
            }
        }

        if (!empty($lines)) {
            $this->headers = str_getcsv($lines[0], $delimiter, $enclosure);
            $this->headers = array_map(function($header) {
                return preg_replace('/[^a-zA-Z0-9_]/', '', trim($header));
            }, $this->headers);
        }

        $this->initializeFieldMappings();
    }

    private function initializeFieldMappings()
    {
        foreach ($this->headers as $index => $header) {
            $this->fieldMappings[$index] = $this->suggestFieldMapping($header);
        }
    }

    private function suggestFieldMapping($header)
    {
        $header = strtolower($header);

        $mappings = [
            'device_mac_address' => 'devices.device_mac_address',
            'mac_address' => 'devices.device_mac_address',
            'mac' => 'devices.device_mac_address',
            'device_label' => 'devices.device_label',
            'label' => 'devices.device_label',
            'device_vendor' => 'devices.device_vendor',
            'vendor' => 'devices.device_vendor',
            'device_model' => 'devices.device_model',
            'model' => 'devices.device_model',
            'device_template' => 'devices.device_template',
            'template' => 'devices.device_template',
            'device_enabled' => 'devices.device_enabled',
            'enabled' => 'devices.device_enabled',
            'device_description' => 'devices.device_description',
            'description' => 'devices.device_description',
            'username' => 'users.username',
            'user_id' => 'device_lines.user_id',
            'auth_id' => 'device_lines.auth_id',
            'password' => 'device_lines.password',
            'display_name' => 'device_lines.display_name',
            'server_address' => 'device_lines.server_address',
            'line_number' => 'device_lines.line_number'
        ];

        return $mappings[$header] ?? '';
    }

    private function getDelimiterChar()
    {
        $delimiters = [
            'comma' => ',',
            'pipe' => '|',
            'semicolon' => ';',
            'tab' => "\t"
        ];

        return $delimiters[$this->delimiter] ?? ',';
    }

    private function getEnclosureChar()
    {
        return $this->enclosure === 'quote' ? '"' : '';
    }

    public function importData()
    {
        try {
            DB::beginTransaction();

            $importCount = 0;
            $errors = [];
            $users = User::where('domain_uuid', Session::get('domain_uuid'))->get()->keyBy('username');
            $fieldCounts = [];

            foreach ($this->csvData as $rowIndex => $row) {
                try {
                    $deviceData = [];
                    $deviceLines = [];
                    $deviceKeys = [];
                    $deviceSettings = [];
                    $deviceUserUuid = null;

                    foreach ($this->fieldMappings as $csvIndex => $mapping) {
                        if (empty($mapping) || !isset($row[$csvIndex])) {
                            continue;
                        }

                        [$table, $field] = explode('.', $mapping);
                        $value = trim($row[$csvIndex]);

                        if (!isset($fieldCounts[$table][$field])) {
                            $fieldCounts[$table][$field] = 0;
                        } else {
                            $fieldCounts[$table][$field]++;
                        }
                        $fieldId = $fieldCounts[$table][$field];

                        switch ($table) {
                            case 'devices':
                                $deviceData[$field] = $this->processFieldValue($field, $value);
                                break;
                            
                            case 'device_lines':
                                if (!isset($deviceLines[$fieldId])) {
                                    $deviceLines[$fieldId] = [];
                                }
                                $deviceLines[$fieldId][$field] = $this->processFieldValue($field, $value);
                                break;
                            
                            case 'device_keys':
                                if (!isset($deviceKeys[$fieldId])) {
                                    $deviceKeys[$fieldId] = [];
                                }
                                $deviceKeys[$fieldId][$field] = $this->processFieldValue($field, $value);
                                break;
                            
                            case 'device_settings':
                                if (!isset($deviceSettings[$fieldId])) {
                                    $deviceSettings[$fieldId] = [];
                                }
                                $deviceSettings[$fieldId][$field] = $this->processFieldValue($field, $value);
                                break;
                            
                            case 'users':
                                if ($field === 'username' && isset($users[$value])) {
                                    $deviceUserUuid = $users[$value]->user_uuid;
                                }
                                break;
                        }
                    }

                    if (!empty($deviceData)) {
                        $deviceData['domain_uuid'] = Session::get('domain_uuid');
                        
                        if ($deviceUserUuid) {
                            $deviceData['device_user_uuid'] = $deviceUserUuid;
                        }

                        $existingDevice = null;
                        if (isset($deviceData['device_mac_address'])) {
                            $existingDevice = Device::where('device_mac_address', $deviceData['device_mac_address'])
                                                  ->where('domain_uuid', Session::get('domain_uuid'))
                                                  ->first();
                        }

                        if ($existingDevice) {
                            $existingDevice->update($deviceData);
                            $device = $existingDevice;
                        } else {
                            $deviceData['device_uuid'] = Str::uuid();
                            $device = Device::create($deviceData);
                        }

                        foreach ($deviceLines as $lineData) {
                            if (count($lineData) > 0) {
                                $lineData['device_line_uuid'] = Str::uuid();
                                $lineData['domain_uuid'] = Session::get('domain_uuid');
                                $lineData['device_uuid'] = $device->device_uuid;
                                DeviceLine::create($lineData);
                            }
                        }

                        foreach ($deviceKeys as $keyData) {
                            if (count($keyData) > 0) {
                                $keyData['device_key_uuid'] = Str::uuid();
                                $keyData['domain_uuid'] = Session::get('domain_uuid');
                                $keyData['device_uuid'] = $device->device_uuid;
                                DeviceKey::create($keyData);
                            }
                        }

                        foreach ($deviceSettings as $settingData) {
                            if (count($settingData) > 0) {
                                $settingData['device_setting_uuid'] = Str::uuid();
                                $settingData['domain_uuid'] = Session::get('domain_uuid');
                                $settingData['device_uuid'] = $device->device_uuid;
                                DeviceSetting::create($settingData);
                            }
                        }

                        $importCount++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Fila " . ($rowIndex + $this->fromRow) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            $this->importResults = [
                'success' => $importCount,
                'errors' => $errors
            ];

            $this->step = 3;

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }

    private function processFieldValue($field, $value)
    {
        switch ($field) {
            case 'device_mac_address':
                $value = strtolower($value);
                return preg_replace('/[^a-fA-F0-9.]/', '', $value);
            
            case 'device_enabled':
            case 'device_provision_enable':
            case 'enabled':
                return strtolower($value) === 'true' || $value === '1' ? 'true' : 'false';
            
            case 'sip_port':
            case 'register_expires':
            case 'line_number':
                return is_numeric($value) ? (int)$value : $value;
            
            default:
                return $value;
        }
    }

    public function resetImport()
    {
        $this->step = 1;
        $this->data = '';
        $this->uploadedFile = null;
        $this->csvData = [];
        $this->headers = [];
        $this->fieldMappings = [];
        $this->importResults = [];
    }

    public function render()
    {
        return view('livewire.device-import');
    }
}