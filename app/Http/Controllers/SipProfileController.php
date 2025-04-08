<?php

namespace App\Http\Controllers;

use App\Models\SipProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SipProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.sipprofile.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.sipprofile.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // SipProfile::create($request->validated());
        // return redirect()->route('sipprofiles.index');
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
    public function edit($uuid)
    {
        $sipProfile = SipProfile::with(['sipprofiledomains', 'sipprofilesettings'])->where('sip_profile_uuid', $uuid)->firstOrFail();
        return view('pages.sipprofile.form', compact('sipProfile'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SipProfile $sipProfile)
    {
        // $sipProfile->update($request->validated());
        // return redirect()->route('sipprofiles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        $sipProfile = SipProfile::where('sip_profile_uuid', $uuid)->firstOrFail();
        DB::beginTransaction();
        try {
            $sipProfile->sipprofiledomains()->delete();
            $sipProfile->sipprofilesettings()->delete();
            $sipProfile->delete();
            DB::commit();
            return redirect()->route('sipprofiles.index');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return redirect()->route('sipprofiles.index');
        }
    }

    public function copy($uuid)
    {
        $originalProfile = SipProfile::where('sip_profile_uuid', $uuid)->first();



        DB::beginTransaction();
        try {
            $newProfile = $originalProfile->replicate();
            $newProfile->sip_profile_uuid = Str::uuid();
            $newProfile->sip_profile_description = $originalProfile->sip_profile_description . ' (copy)';
            $newProfile->save();

            foreach ($originalProfile->sipprofiledomains as $domain) {
                $newDomain = $domain->replicate();
                $newDomain->sip_profile_domain_uuid = Str::uuid();
                $newDomain->sip_profile_uuid = $newProfile->sip_profile_uuid;
                $newDomain->save();
            }

            foreach ($originalProfile->sipprofilesettings as $setting) {
                $newSetting = $setting->replicate();
                $newSetting->sip_profile_setting_uuid = Str::uuid();
                $newSetting->sip_profile_uuid = $newProfile->sip_profile_uuid;
                $newSetting->save();
            }

            DB::commit();
            return redirect()->route('sipprofiles.index', $newProfile->sip_profile_uuid);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return redirect()->route('sipprofiles.index');
        }
    }
}
