<?php

namespace App\Livewire;

use App\Http\Requests\PhraseRequest;
use App\Repositories\PhraseRepository;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Repositories\PhraseDetailRepository;
use Illuminate\Contracts\View\View;

class PhraseForm extends Component
{
    public $phrase;
    public string $phrase_uuid;
    public ?string $domain_uuid = '';
    public string $phrase_name = '';
    public ?string $phrase_language = '';
    public bool $phrase_enabled = true;
    public ?string $phrase_description = '';

    public ?array $phraseDetails = [];

    public array $phraseDetailsToDelete = [];

    public bool $canViewPhraseDetail = false;
    public bool $canAddPhraseDetail = false;
    public bool $canEditPhraseDetail = false;
    public bool $canDeletePhraseDetail = false;

    public $domains = [];

    protected $phraseRepository;
    protected $phraseDetailRepository;

    public function boot(PhraseRepository $phraseRepository, PhraseDetailRepository $phraseDetailRepository)
    {
        $this->phraseRepository = $phraseRepository;
        $this->phraseDetailRepository = $phraseDetailRepository;
    }

    public function rules()
    {
        $request = new PhraseRequest();
        return $request->rules();
    }

    public function mount($phrase = null, $domains = []): void
    {
        $this->domains = $domains;

        if ($phrase)
        {
            $this->phrase = $phrase;
            $this->domain_uuid = $phrase->domain_uuid;
            $this->phrase_uuid = $phrase->phrase_uuid;
            $this->phrase_name = $phrase->phrase_name;
            $this->phrase_language = $phrase->phrase_language;
            $this->phrase_enabled = $phrase->phrase_enabled ?? false;
            $this->phrase_description = $phrase->phrase_description;

            foreach ($phrase->details as $phraseDetail)
            {
                $this->phraseDetails[] = [
                    'phrase_detail_uuid' => $phraseDetail->phrase_detail_uuid,
                    'phrase_detail_function' => $phraseDetail->phrase_detail_function,
                    'phrase_detail_data' => $phraseDetail->phrase_detail_data,
                    'phrase_detail_order' => $phraseDetail->phrase_detail_order,
                ];
            }
        }

        $this->loadPermissions();

        if (empty($this->phraseDetails) && $this->canAddPhraseDetail)
        {
            $this->addPhraseDetail();
        }
    }

    private function loadPermissions(): void
    {
        $user = auth()->user();

        // $this->canViewPhraseDetail = $user->hasPermission('phrase_detail_view');
        // $this->canAddPhraseDetail = $user->hasPermission('phrase_detail_add');
        // $this->canEditPhraseDetail = $user->hasPermission('phrase_detail_edit');
        // $this->canDeletePhraseDetail = $user->hasPermission('phrase_detail_delete');

        $this->canViewPhraseDetail = true;
        $this->canAddPhraseDetail = true;
        $this->canEditPhraseDetail = true;
        $this->canDeletePhraseDetail = true;
    }

    public function addPhraseDetail(): void
    {
        if (!$this->canAddPhraseDetail)
        {
            session()->flash('error', 'You do not have permission to add phrase details.');
            return;
        }

        $this->phraseDetails[] = [
            'phrase_detail_uuid' => '',
            'phrase_detail_function' => '',
            'phrase_detail_data' => '',
            'phrase_detail_order' => '',
        ];
    }

    public function removePhraseDetail($index): void
    {
        if (!$this->canDeletePhraseDetail)
        {
            session()->flash('error', 'You do not have permission to delete phrase detail.');
            return;
        }

        if (isset($this->phraseDetails[$index]['phrase_detail_uuid']) && !empty($this->phraseDetails[$index]['phrase_detail_uuid']))
        {
            $this->phraseDetailsToDelete[] = $this->phraseDetails[$index]['phrase_detail_uuid'];
        }

        unset($this->phraseDetails[$index]);
        $this->phraseDetails = array_values($this->phraseDetails);
    }

    public function save(): void
    {
        $this->validate();

        $filteredPhraseDetails = collect($this->phraseDetails)->filter(function ($phraseDetail)
        {
            return !empty($phraseDetail['phrase_detail_function']);
        })->toArray();

        $hasNewPhraseDetails = collect($filteredPhraseDetails)->filter(fn($d) => empty($d['phrase_detail_uuid']))->count() > 0;

        if ($hasNewPhraseDetails && !$this->canAddPhraseDetail)
        {
            session()->flash('error', 'You do not have permission to add phraseDetails.');
            return;
        }

        if (!empty($this->phraseDetailsToDelete) && !$this->canDeletePhraseDetail)
        {
            session()->flash('error', 'You do not have permission to delete phraseDetails.');
            return;
        }

        $phraseData = [
            'domain_uuid' => $this->domain_uuid,
            'phrase_name' => $this->phrase_name,
            'phrase_language' => $this->phrase_language,
            'phrase_enabled' => $this->phrase_enabled,
            'phrase_description' => $this->phrase_description,
        ];

        if ($this->phrase)
        {
            $updated = $this->phraseRepository->update($this->phrase, $phraseData);

            if (!$updated)
            {
                session()->flash('error', 'Failed to update phrase.');
                return;
            }

            // $this->phraseDetailRepository->update($this->phrase, $filteredPhraseDetails);

            // if (!empty($this->phraseDetailsToDelete))
            // {
            //     $this->phraseDetailRepository->delete($this->phraseDetailsToDelete);
            // }

            session()->flash('message', 'Phrase updated successfully.');
        }
        else
        {
            $phraseData['phrase_uuid'] = Str::uuid();
            $this->phrase = $this->phraseRepository->create($phraseData);

            // $this->phraseDetailRepository->create($this->phrase, $filteredPhraseDetails);

            session()->flash('message', 'Phrase created successfully.');
        }

        redirect()->route('phrases.edit', $this->phrase->phrase_uuid);
    }

    public function render(): View
    {
        return view('livewire.phrase-form');
    }
}
