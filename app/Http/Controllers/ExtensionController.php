<?php

namespace App\Http\Controllers;

use App\Repositories\ExtensionRepository;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    protected $extensionRepository;

    public function __construct(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.extension.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.extension.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uuid)
    {
        $extensions = $this->extensionRepository->findByUuid($uuid);
        return view('pages.extension.form', compact('extensions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
