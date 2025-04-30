<?php
namespace App\Http\Controllers;

use App\Http\Requests\StreamRequest;
use App\Models\Domain;
use App\Models\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StreamController extends Controller
{
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
		$data = $request->validated();

    	$data['domain_uuid'] = session('domain_uuid');

		$stream = Stream::create($data);

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
		$stream->update($request->validated());

		return redirect()->route("streams.edit", $stream->stream_uuid);
	}

    public function destroy(Stream $stream)
    {
        $stream->delete();

        return redirect()->route('streams.index');
    }
}
