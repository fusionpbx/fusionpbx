<?php

namespace App\Http\Controllers;

use App\Models\SipProfile;
use App\Repositories\SipProfileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SipProfileController extends Controller
{
    protected $sipProfileRepository;


    public function __construct(SipProfileRepository $sipProfileRepository)
    {
        $this->sipProfileRepository = $sipProfileRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index() : mixed
    {
        return view('pages.sipprofile.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('pages.sipprofile.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(SipProfile $sipProfile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid) : View
    {
        $sipProfile = $this->sipProfileRepository->findByUuid($uuid, true);
        return view('pages.sipprofile.form', compact('sipProfile'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SipProfile $sipProfile)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid) : RedirectResponse
    {
        try {
            $this->sipProfileRepository->delete($uuid);
            return redirect()->route('sipprofiles.index');
        } catch (\Exception $e) {
            throw $e;
            return redirect()->route('sipprofiles.index')->with('error', 'Failed to delete SIP Profile');
        }
    }

    public function copy($uuid) : RedirectResponse
    {
        try {
            $newProfile = $this->sipProfileRepository->copy($uuid);
            return redirect()->route('sipprofiles.index', $newProfile->sip_profile_uuid);
        } catch (\Exception $e) {
            throw $e;
            return redirect()->route('sipprofiles.index')->with('error', 'Failed to copy SIP Profile');
        }
    }
}
