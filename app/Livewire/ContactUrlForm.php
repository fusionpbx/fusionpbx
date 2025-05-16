<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactUrl;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Throwable;

class ContactUrlForm extends Component
{

    public $contactUuid;
    public $urls = [];

    public $listeners = [
        'urlsSaved' => 'save'
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadUrls();
    }

    public function loadUrls()
    {
        $contact = Contact::with('urls')
            ->where('contact_uuid', $this->contactUuid)
            ->first();

        if ($contact && $contact->urls->count() > 0) {
            $this->urls = $contact->urls->toArray();
        } else {
            $this->addUrl();
        }
    }

    public function addUrl()
    {
        $this->urls[] = [
            'url_label' => '',
            'url_address' => '',
            'url_description' => '',
            'url_primary' => false
        ];
    }

    public function removeUrl($index)
    {
        unset($this->urls[$index]);
        $this->urls = array_values($this->urls);
    }

    public function save()
    {
        try {
            ContactUrl::where('contact_uuid', $this->contactUuid)->delete();

            foreach ($this->urls as $url) {
                if (!empty($url['url'])) {
                    ContactUrl::create([
                        'contact_uuid' => $this->contactUuid,
                        'url_label' => $url['url_label'],
                        'url_address' => $url['url_address'],
                        'url_description' => $url['url_description'],
                        'url_primary' => $url['url_primary'] ?? false,
                    ]);
                }
            }

            $this->dispatch('relationsSaved')->to(ContactRelationForm::class);
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('message', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function render()
    {
        return view('livewire.contact-url-form');
    }
}
