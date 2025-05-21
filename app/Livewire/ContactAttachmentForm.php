<?php

namespace App\Livewire;

use App\Models\ContactAttachment;
use App\Models\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

class ContactAttachmentForm extends Component
{
    use WithFilePond;

    public $contactUuid;
    public $attachments = [];
    public $removedAttachments = [];

    protected $listeners = [
        'saveAttachment' => 'save', 
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadAttachments();
    }

    public function loadAttachments()
    {
        $contact = Contact::where('contact_uuid', $this->contactUuid)
            ->with('attachments')
            ->first();

        if ($contact && $contact->attachments->count() > 0) {
            $this->attachments = $contact->attachments->map(function ($attachment) {
                return [
                    'contact_attachment_uuid' => $attachment->contact_attachment_uuid,
                    'attachment_filename' => $attachment->attachment_filename,
                    'attachment_primary' => (bool)$attachment->attachment_primary,
                    'attachment_description' => $attachment->attachment_description,
                    'attachment_uploaded_date' => $attachment->attachment_uploaded_date,
                    'attachment_uploaded_user_uuid' => $attachment->attachment_uploaded_user_uuid,
                    'file' => '/storage/attachments/' . $attachment->attachment_filename,
                ];
            })->toArray();
        } else {
            $this->addAttachment();
        }
    }

    public function addAttachment()
    {
        if (!auth()->user()->hasPermission('contact_attachment_add')) {
            session()->flash('message', 'You do not have permission to add attachments.');
            return;
        }
        $this->attachments[] = [
            'attachment_filename' => '',
            'attachment_primary' => '',
            'attachment_description' => '',
            'attachment_uploaded_date' => '',
            'attachment_uploaded_user_uuid' => '',
            'file' => null,
        ];
    }
    public function removeAttachment($index)
    {
        if (!auth()->user()->hasPermission('contact_attachment_delete')) {
            session()->flash('message', 'You do not have permission to remove attachments.');
            return;
        }
        if ($this->attachments[$index]['attachment_uploaded_date'] !== null) {
            $this->removedAttachments[] = $this->attachments[$index];
        }
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function save()
    {
        foreach ($this->removedAttachments as $removedAttachment) {
            $contactAttachment = ContactAttachment::where('contact_attachment_uuid', $removedAttachment['contact_attachment_uuid'])->first();
            if ($contactAttachment) {
                Storage::delete('public\\attachments\\' . $contactAttachment->attachment_filename);
                $contactAttachment->delete();
            }
        }
        foreach ($this->attachments as $attachment) {
            $contactAttachment = null;

            if (isset($attachment['contact_attachment_uuid'])) {
                $contactAttachment = ContactAttachment::where('contact_attachment_uuid', $attachment['contact_attachment_uuid'])->first();
            } else {
                $contactAttachment = new ContactAttachment();
            }

            $contactAttachment->contact_uuid = $this->contactUuid;
            $contactAttachment->attachment_primary = $attachment['attachment_primary'] ? 1 : 0;
            $contactAttachment->attachment_description = $attachment['attachment_description'];
            $contactAttachment->domain_uuid = auth()->user()->domain_uuid;

            if (!isset($attachment['contact_attachment_uuid'])) {
                $contactAttachment->attachment_uploaded_date = now();
                $contactAttachment->attachment_uploaded_user_uuid = auth()->user()->uuid;
                $contactAttachment->attachment_filename = Str::uuid() . '_' . $attachment['file']->getClientOriginalName();
                Storage::putFileAs('public\\attachments', $attachment['file'], $contactAttachment->attachment_filename);
            }

            $contactAttachment->save();
        }        

        session()->flash('message', 'Attachment saved successfully.');
        redirect()->route('contacts.edit', ['contact' => $this->contactUuid]);
    }
    
    public function render()
    {
        return view('livewire.contact-attachment-form');
    }
}
