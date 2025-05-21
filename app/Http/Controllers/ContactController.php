<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use JeroenDesloovere\VCard\VCard;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact.index');
    }

    public function create()
    {
        return view('pages.contact.form');
    }

    public function edit($uuid)
    {
        $contact = Contact::where('contact_uuid', $uuid)->with(['emails', 'phones', 'addresses', 'groups', 'urls', 'settings'])->firstOrFail();
        return view('pages.contact.form', compact('contact'));
    }

    public function search(Request $request)
    {
        $search = $request->search;
        $currentContactUuid = $request->current_contact_uuid;

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $contacts = Contact::where(function ($query) use ($search) {
            $query->where('contact_name_given', 'like', '%' . $search . '%')
                ->orWhere('contact_name_family', 'like', '%' . $search . '%')
                ->orWhere('contact_organization', 'like', '%' . $search . '%');
        })
            ->when($currentContactUuid, function ($query) use ($currentContactUuid) {
                return $query->where('contact_uuid', '!=', $currentContactUuid);
            })
            ->limit(10)
            ->select('contact_uuid', 'contact_name_given', 'contact_name_family', 'contact_organization')
            ->get()
            ->map(function ($contact) {
                $contactName = $contact->contact_name_given . ' ' . $contact->contact_name_family;
                if ($contact->contact_organization) {
                    $contactName .= ' (' . $contact->contact_organization . ')';
                }
                return [
                    'id' => $contact->contact_uuid,
                    'name' => $contactName
                ];
            });


        return response()->json($contacts);
    }

    public function exportVCard($uuid)
    {
        $contact = Contact::where('contact_uuid', $uuid)
            ->with(['emails', 'phones', 'addresses', 'urls', 'settings'])
            ->firstOrFail();

        $vcard = new VCard();

        $vcard->setCharset('UTF-8');

        $fullName = trim($contact->contact_name_given . ' ' . $contact->contact_name_family);
        if (empty($fullName)) {
            $fullName = $contact->contact_organization ?: 'Contact';
        }
        
        $vcard->addName(
            $contact->contact_name_family, 
            $contact->contact_name_given, 
            $contact->contact_name_middle, 
            $contact->contact_name_prefix, 
            $contact->contact_name_suffix
        );
        
        if (!empty($contact->contact_organization)) {
            $vcard->addCompany($contact->contact_organization);
        }

        if (!empty($contact->contact_role)) {
            $vcard->addRole($contact->contact_role);
        }
        
        if (!empty($contact->contact_note)) {
            $vcard->addNote($contact->contact_note);
        }

        foreach ($contact->addresses as $address) {
            $vcard->addAddress(
                $address->address_extended ?? '', 
                $address->address_street_address ?? '', 
                $address->address_locality ?? '', 
                $address->address_region ?? '',
                $address->address_postal_code ?? '',
                $address->address_country ?? '', 
                $this->mapAddressType($address->address_type) 
            );
        }

        foreach ($contact->emails as  $email) {

            $vcard->addEmail($email->email_address);
        }

        foreach ($contact->phones as $phone) {
            $vcard->addPhoneNumber($phone->phone_number, $this->mapPhoneType($phone->phone_type));
        }

        foreach ($contact->urls as $url) {
            $vcard->addURL($url->url_address);
        }

 
        $filename = $this->createSafeFilename($contact);
        return $vcard->download($filename);
    }

    /**
     * Map phone type to vCard standard type
     *
     * @param string $type
     * @return string
     */
    private function mapPhoneType($type)
    {
        $typeMap = [
            'home' => 'HOME',
            'work' => 'WORK',
            'cell' => 'CELL',
            'mobile' => 'CELL',
            'main' => 'MAIN',
            'fax_home' => 'HOME,FAX',
            'fax_work' => 'WORK,FAX',
            'pager' => 'PAGER',
            'other' => 'OTHER'
        ];

        return $typeMap[strtolower($type)] ?? 'OTHER';
    }

    /**
     * Map address type to vCard standard type
     *
     * @param string $type
     * @return string
     */
    private function mapAddressType($type)
    {
        $typeMap = [
            'home' => 'HOME',
            'work' => 'WORK',
            'other' => 'OTHER'
        ];

        return $typeMap[strtolower($type)] ?? 'OTHER';
    }

       private function createSafeFilename(Contact $contact)
    {
        $name = trim($contact->contact_name_given . ' ' . $contact->contact_name_family);
        
        if (empty($name)) {
            if (!empty($contact->contact_organization)) {
                $name = $contact->contact_organization;
            } else {
                $name = 'contact_' . $contact->contact_uuid;
            }
        }
        

        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
        
        return $name;
    }
}

