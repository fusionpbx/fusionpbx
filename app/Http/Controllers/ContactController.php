<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

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
        // dd($contact);
        return view('pages.contact.form', compact('contact'));
    }
}
