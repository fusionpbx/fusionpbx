<?php
namespace App\Http\Controllers;

use App\Http\Requests\PhraseRequest;
use App\Models\Phrase;
use App\Models\Domain;
use App\Repositories\PhraseRepository;

class PhraseController extends Controller
{
	protected $phraseRepository;

	public function __construct(PhraseRepository $phraseRepository)
	{
		$this->phraseRepository = $phraseRepository;
	}
	public function index()
	{
		return view('pages.phrases.index');
	}

	public function create()
	{
		$domains = Domain::all();

		$sounds = getSounds();

		return view("pages.phrases.form", compact("domains", "sounds"));
	}

	public function store(PhraseRequest $request)
	{
		$phrase = $this->phraseRepository->create($request->validated());

		return redirect()->route("phrases.edit", $phrase->phrase_uuid);
	}

    public function show(Phrase $phrase)
    {
        //
    }

	public function edit(Phrase $phrase)
	{
		$domains = Domain::all();

		$sounds = getSounds();

		return view("pages.phrases.form", compact("phrase", "domains", "sounds"));
	}

	public function update(PhraseRequest $request, Phrase $phrase)
	{
		$this->phraseRepository->update($phrase, $request->validated());

        return redirect()->route("phrases.edit", $phrase->phrase_uuid);
	}

    public function destroy(Phrase $phrase)
    {
        $this->phraseRepository->delete($phrase);

        return redirect()->route('phrases.index');
    }
}
