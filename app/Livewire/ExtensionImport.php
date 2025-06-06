<?php

namespace App\Livewire;

use App\Facades\Setting;
use App\Models\Extension;
use App\Models\ExtensionUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ExtensionImport extends Component
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
            'extensions' => [
                'extension',
                'number_alias',
                'password',
                'accountcode',
                'effective_caller_id_name',
                'effective_caller_id_number',
                'outbound_caller_id_name',
                'outbound_caller_id_number',
                'emergency_caller_id_name',
                'emergency_caller_id_number',
                'directory_first_name',
                'directory_last_name',
                'directory_visible',
                'directory_exten_visible',
                'limit_max',
                'limit_destination',
                'missed_call_app',
                'missed_call_data',
                'user_context',
                'toll_allow',
                'call_timeout',
                'call_group',
                'call_screen',
                'user_record',
                'hold_music',
                'auth_acl',
                'cidr',
                'sip_force_contact',
                'sip_force_expires',
                'nibble_account',
                'sip_bypass_media',
                'unique_id',
                'dial_string',
                'dial_domain',
                'do_not_disturb',
                'forward_all_destination',
                'forward_all_enabled',
                'forward_busy_destination',
                'forward_busy_enabled',
                'forward_no_answer_destination',
                'forward_no_answer_enabled',
                'forward_user_not_registered_destination',
                'forward_user_not_registered_enabled',
                'follow_me_uuid',
                'enabled',
                'description'
            ],
            'extension_users' => [
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

            $filename = 'extensions-import-' . Str::random(10) . '.csv';
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
            'extension' => 'extensions.extension',
            'password' => 'extensions.password',
            'firstname' => 'extensions.directory_first_name',
            'lastname' => 'extensions.directory_last_name',
            'username' => 'extension_users.username',
            'enabled' => 'extensions.enabled',
            'description' => 'extensions.description',
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
            $users = User::where('domain_uuid', auth()->user()->domain_uuid)->get()->keyBy('username');

            foreach ($this->csvData as $rowIndex => $row) {
                try {
                    $extensionData = [];
                    $extensionUsers = [];


                    foreach ($this->fieldMappings as $csvIndex => $mapping) {
                        if (empty($mapping) || !isset($row[$csvIndex])) {
                            continue;
                        }

                        [$table, $field] = explode('.', $mapping);
                        $value = trim($row[$csvIndex]);

                        if ($table === 'extensions') {
                            $extensionData[$field] = $this->processFieldValue($field, $value);
                        } elseif ($table === 'extension_users' && $field === 'username') {
                            if (isset($users[$value])) {
                                $extensionUsers[] = $users[$value]->user_uuid;
                            }
                        }
                    }

                    if (!empty($extensionData)) {
                        $extensionData['domain_uuid'] = Session::get('domain_uuid');
                        //$extensionData['extension_uuid'] = Str::uuid();
                        $passwordLength = Setting::getSetting('extension', 'password_length', 'numeric');
                        $passwordStrength = Setting::getSetting('extension', 'password_strength', 'numeric');
                        $extensionData['password'] = generatePassword($passwordLength, $passwordStrength);

                        $extension = Extension::create($extensionData);

                        foreach ($extensionUsers as $userUuid) {
                            ExtensionUser::create([
                                'extension_user_uuid' => Str::uuid(),
                                'domain_uuid' => Session::get('domain_uuid'),
                                'extension_uuid' => $extension->extension_uuid,
                                'user_uuid' => $userUuid
                            ]);
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
            case 'phone_number':
                return preg_replace('/\D/', '', $value);
            case 'enabled':
                return strtolower($value) === 'true' || $value === '1' ? 'true' : 'false';
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
        return view('livewire.extension-import');
    }
}
