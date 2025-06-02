<?php

namespace App\Livewire;

use App\Facades\Setting;
use App\Http\Requests\ExtensionRequest;
use App\Models\Destination;
use App\Models\Extension;
use App\Models\Domain;
use App\Models\User;
use App\Repositories\ExtensionRepository;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExtensionForm extends Component
{
    public $extensions;

    public string $extension;
    public ?string $number_alias;
    public string $password;
    public string $accountcode;
    public $range = 1;

    //CALLED ID
    public $effective_caller_id_name = '';
    public $effective_caller_id_number = 0;
    public $outbound_caller_id_name = '';
    public $outbound_caller_id_number = '';
    public $emergency_caller_id_name = '';
    public $emergency_caller_id_number = '';

    // Directory
    public $directory_first_name = '';
    public $directory_last_name = '';
    public $directory_visible = 'true';
    public $directory_exten_visible = 'true';

    //Advanced Config
    public $max_registrations;
    public $limit_max;
    public $limit_destination;
    public $user_context = '';
    public $call_timeout;
    public $call_group = '';
    public $call_screen_enabled;
    public $user_record = '';
    public $hold_music = '';
    public array $holdMusicOptions = [];

    //missing call
    public $missed_call_app = '';
    public $missed_call_data = '';

    //toll
    public $toll_allow = '';
    public $tollAllowOptions = []; // Nueva propiedad para las opciones

    // advanced
    public $auth_acl = '';
    public $cidr = '';
    public  $sip_force_contact = '';
    public $sip_force_expires = 0;
    public $mwi_account = '';
    public $sip_bypass_media = '';
    public $absolute_codec_string = '';
    public $force_ping = '';
    public $dial_string = '';

    public $enabled = 'true';
    public $description = '';

    public  $extensionUsers = [];
    public $selectedDomain = '';

    public $usersToDelete = [];
    public $domainsToDelete = [];

    public $availableUsers = [];
    public $availableDomains = '';

    public $voicemail_enabled = false;
    public $voicemail_password;
    public $voicemail_mail_to;
    public $voicemail_transcription_enabled = false;
    public $voicemail_file;
    public $voicemail_local_after_email = false;

    public $emergency_destination;

    public $destinations;

    public $showCopyModal = false;
    public $copyExtensionNumber = '';
    public $copyNumberAlias = '';


    protected ExtensionRepository $extensionRepository;


    public function boot(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    public function rules()
    {
        $request = new ExtensionRequest();
        $request->setExtensionUuid($this->extensions->extension_uuid);
        return $request->rules();
    }

    private function setDefaultValues()
    {
        $this->user_context ??= auth()->user()->domain->domain_name;
        $this->max_registrations ??= Setting::getSetting('extension', 'max_registrations', 'numeric');
        $this->accountcode ??= getAccountCode();
        $this->limit_max ??= 5;
        $this->limit_destination ??= '!USER_BUSY';
        $this->call_timeout ??= 30;
        $this->call_screen_enabled ??= false;
        $this->user_record ??= Setting::getSetting('extension', 'user_record_default', 'text');
        $this->voicemail_transcription_enabled ??= Setting::getSetting('extension', 'transcription_enabled_default', 'boolean');
        $this->voicemail_enabled ??= Setting::getSetting('extension', 'enabled_default', 'boolean');
        $this->enabled ??= true;
        $this->toll_allow ??= 'all'; 
    }

    public function mount($extensions = null)
    {
        $this->loadAvailableData();

        if ($extensions) {
            $this->extension = $extensions->extension;
            $this->number_alias = $extensions->number_alias;
            $this->password = $extensions->password;
            $this->accountcode = $extensions->accountcode;
            $this->range = $extensions->range ?? 1;
            $this->effective_caller_id_name = $extensions->effective_caller_id_name;
            $this->effective_caller_id_number = $extensions->effective_caller_id_number;
            $this->outbound_caller_id_name = $extensions->outbound_caller_id_name;
            $this->outbound_caller_id_number = $extensions->outbound_caller_id_number;
            $this->emergency_caller_id_name = $extensions->emergency_caller_id_name;
            $this->emergency_caller_id_number = $extensions->emergency_caller_id_number;
            $this->directory_first_name = $extensions->directory_first_name;
            $this->directory_last_name = $extensions->directory_last_name;
            $this->directory_visible = $extensions->directory_visible;
            $this->directory_exten_visible = $extensions->directory_exten_visible;
            $this->max_registrations = $extensions->max_registrations;
            $this->limit_max = $extensions->limit_max;
            $this->limit_destination = $extensions->limit_destination;
            $this->user_context = $extensions->user_context;
            $this->call_timeout = $extensions->call_timeout;
            $this->call_group = $extensions->call_group;
            $this->call_screen_enabled = $extensions->call_screen_enabled ?? false;
            $this->user_record = $extensions->user_record;
            $this->hold_music = $extensions->hold_music;
            $this->toll_allow = $extensions->toll_allow ?? 'all'; 
            $this->auth_acl = $extensions->auth_acl;
            $this->cidr = $extensions->cidr;
            $this->sip_force_contact = $extensions->sip_force_contact;
            $this->sip_force_expires = $extensions->sip_force_expires;
            $this->mwi_account = $extensions->mwi_account;
            $this->sip_bypass_media = $extensions->sip_bypass_media;
            $this->absolute_codec_string = $extensions->absolute_codec_string;
            $this->force_ping = $extensions->force_ping;
            $this->dial_string = $extensions->dial_string;
            $this->enabled = $extensions->enabled;
            $this->description = $extensions->description;

            $this->voicemail_enabled = $extensions->voicemail?->voicemail_enabled;
            $this->voicemail_password = $extensions->voicemail?->voicemail_password;
            $this->voicemail_mail_to = $extensions->voicemail?->voicemail_mail_to;
            $this->voicemail_transcription_enabled = $extensions->voicemail?->voicemail_transcription_enabled;
            $this->voicemail_file = $extensions->voicemail?->voicemail_file;
            $this->voicemail_local_after_email = $extensions->voicemail?->voicemail_local_after_email;

            foreach ($extensions->extensionUsers as $user) {
                $this->extensionUsers[] = [
                    'extension_user_uuid' => $user->extension_user_uuid,
                    'domain_uuid' => $user->domain_uuid,
                    'user_uuid' => $user->user_uuid,
                    'username' => $user->username,
                ];
            }

            if ($extensions->domain) {
                $this->selectedDomain = $extensions->domain->domain_uuid;
            } else {
                $this->selectedDomain = auth()->user()->domain_uuid;
            }
        }
        $this->setDefaultValues();
    }

    public function loadAvailableData()
    {
        $this->availableUsers = User::select('user_uuid', 'username', 'domain_uuid')
            ->with('domain:domain_uuid,domain_name')
            ->get()
            ->toArray();

        $this->availableDomains = Domain::select('domain_uuid', 'domain_name')->get()->toArray();

        if(!$this->extensions) {
            $this->selectedDomain = auth()->user()->domain_uuid;
        }
    }

    public function emergencyDestination()
    {
        if (auth()->user()->hasPermission('emergency_caller_id_select')) {
            $this->emergency_destination = Destination::where('domain_uuid', auth()->user()->domain_uuid)
                ->where('destination_type', 'inbound')
                ->where('destination_type_emergency', 1)
                ->get()
                ->orderBy('destination_number', 'asc')
                ->get();
        }
    }

    public function getDestinations()
    {
        $this->destinations = Destination::where('domain_uuid', auth()->user()->domain_uuid)
            ->where('destination_type', 'inbound')
            ->orderBy('destination_number', 'asc')
            ->get();
    }

    public function addUser()
    {
        $this->extensionUsers[] = [
            'extension_user_uuid' => '',
            'domain_uuid' => '',
            'user_uuid' => '',
            'username' => '',
        ];
    }

    public function removeUser($index)
    {
        if (isset($this->extensionUsers[$index]['extension_user_uuid']) && !empty($this->extensionUsers[$index]['extension_user_uuid'])) {
            $this->usersToDelete[] = $this->extensionUsers[$index]['extension_user_uuid'];
        }
        unset($this->extensionUsers[$index]);
        $this->extensionUsers = array_values($this->extensionUsers);
    }

    public function save()
    {
        $this->validate();

        if (auth()->user()->hasPermission('extension_domain')) {
            $domainUuid = $this->selectedDomain;
        } else {
            $domainUuid = auth()->user()->domain_uuid;
        }

        $passwordLegth = Setting::getSetting('extension', 'password_length', 'numeric');
        $passwordStrength = Setting::getSetting('extension', 'password_strength', 'numeric');

        $this->password = generatePassword($passwordLegth, $passwordStrength);

        if (!empty($this->outbound_caller_id_number)) {
            $this->outbound_caller_id_number = preg_replace('#[^\+0-9]#', '', $this->outbound_caller_id_number);
        }

        $toll_allow = str_replace(',', ':', $this->toll_allow);

        $extensionData = [
            'extension' => $this->extension,
            'number_alias' => $this->number_alias ?? 0,
            'domain_uuid' =>  $domainUuid,
            'domain_name' => auth()->user()->domain->domain_name,
            'password' => $this->password,
            'range' => $this->range,
            'accountcode' => $this->accountcode ?? '',
            'effective_caller_id_name' => $this->effective_caller_id_name,
            'effective_caller_id_number' => $this->effective_caller_id_number,
            'outbound_caller_id_name' => $this->outbound_caller_id_name,
            'outbound_caller_id_number' => $this->outbound_caller_id_number,
            'emergency_caller_id_name' => $this->emergency_caller_id_name,
            'emergency_caller_id_number' => $this->emergency_caller_id_number,
            'directory_first_name' => $this->directory_first_name,
            'directory_last_name' => $this->directory_last_name,
            'toll_allow' => $toll_allow,
            'directory_visible' => $this->directory_visible,
            'directory_exten_visible' => $this->directory_exten_visible,
            'max_registrations' => $this->max_registrations,
            'limit_max' => $this->limit_max,
            'limit_destination' => $this->limit_destination,
            'user_context' => $this->user_context,
            'call_timeout' => $this->call_timeout,
            'call_group' => $this->call_group,
            'call_screen_enabled' => $this->call_screen_enabled,
            'user_record' => $this->user_record,
            'hold_music' => $this->hold_music,
            'auth_acl' => $this->auth_acl,
            'cidr' => $this->cidr,
            'sip_force_contact' => $this->sip_force_contact ?? '',
            'sip_force_expires' => $this->sip_force_expires ?? 0,
            'mwi_account' => $this->mwi_account ?? '',
            'sip_bypass_media' => $this->sip_bypass_media ?? '',
            'absolute_codec_string' => $this->absolute_codec_string ?? '',
            'force_ping' => $this->force_ping ?? '',
            'dial_string' => $this->dial_string,
            'enabled' => $this->enabled,
            'description' => $this->description,

            'voicemail_enabled' => $this->voicemail_enabled ?? false,
            'voicemail_password' => $this->voicemail_password,
            'voicemail_mail_to' => $this->voicemail_mail_to,
            'voicemail_transcription_enabled' => $this->voicemail_transcription_enabled ?? false,
            'voicemail_file' => $this->voicemail_file,
            'voicemail_local_after_email' => $this->voicemail_local_after_email ?? false,
        ];

        try {
            if ($this->extensions) {
                $extensionData['range'] = 1;
                $this->extensionRepository->update(
                    $this->extensions->extension_uuid,
                    $extensionData,
                    $this->extensionUsers,
                    $this->usersToDelete
                );
            } else {
                $this->extensionRepository->create($extensionData, $this->extensionUsers);
            }

            session()->flash('success', 'Extension saved successfully');
            redirect()->route('extensions.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving extension: ' . $e->getMessage());
            throw $e;
        }
    }

    public function showCopyModals()
    {
        $this->showCopyModal = true;
        $this->copyExtensionNumber = '';
        $this->copyNumberAlias = '';
        $this->resetErrorBag(['copyExtensionNumber', 'copyNumberAlias']);
    }

    public function closeCopyModal()
    {
        $this->showCopyModal = false;
        $this->copyExtensionNumber = '';
        $this->copyNumberAlias = '';
        $this->resetErrorBag(['copyExtensionNumber', 'copyNumberAlias']);
    }


    public function copy()
    {
        try {
            $this->extensionRepository->copy($this->extensions->extension_uuid, $this->copyExtensionNumber, $this->copyNumberAlias);
            $this->closeCopyModal();
            session()->flash('success', 'Extension copied successfully');
            redirect()->route('extensions.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error copying extension: ' . $e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.extension-form');
    }
}