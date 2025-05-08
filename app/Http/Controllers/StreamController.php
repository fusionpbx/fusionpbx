<?php
namespace App\Http\Controllers;

use App\Http\Requests\StreamRequest;
use App\Models\Domain;
use App\Models\Stream;
use App\Repositories\StreamRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StreamController extends Controller
{
	protected $streamRepository;

	public function __construct(StreamRepository $streamRepository)
	{
		$this->streamRepository = $streamRepository;
	}

	public function index()
	{
		return view('pages.streams.index');
	}

	public function create()
	{
		$domains = Domain::all();

		return view("pages.streams.form", compact("domains"));
	}

	public function store(StreamRequest $request)
	{
		$stream = $this->streamRepository->create($request->validated());

		return redirect()->route("streams.edit", $stream->stream_uuid);
	}

    public function show(Stream $stream)
    {
        //
    }

	public function edit(Stream $stream)
	{
		$domains = Domain::all();

		return view("pages.streams.form", compact("stream", "domains"));
	}

	public function update(StreamRequest $request, Stream $stream)
	{
		$this->streamRepository->update($stream, $request->validated());

		return redirect()->route("streams.edit", $stream->stream_uuid);
	}

    public function destroy(Stream $stream)
    {
		$this->streamRepository->delete($stream);

        return redirect()->route('streams.index');
    }
}
