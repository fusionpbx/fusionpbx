@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ 'Pricing' }}
            </h3>
        </div>

        <div class="card-body">
            <form id="formCheckRate">
                @csrf
                <div class="row">
                    <p>Please input your phone number to see rate. Use default rate table if you dont know what to put. If no rate, then it means you dont have a complete rating table, connections or not depends on your policy.</h6>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="number" class="form-label">Number</label>
                            <input type="number" min="1" step="1" class="form-control" id="number" name="number">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="number" class="form-label">Rates table</label>
                            <input type="text" class="form-control" id="profile" name="profile" value="default">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="number" class="form-label">Direction</label>
                            <select class="form-select" id="direction" name="direction">
                                <option value="outbound">Outgoing call</option>
                                <option value="inbound">Incoming call</option>
                                <option value="local">Extension-to-Extension call</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row mt-3">
                <div class="col-md-3">
                    <p>Rate: <span id="rate"></span></p>
                </div>
            </div>

        </div>

        <hr>

        @include('pages.lcr.partial.table')
    </div>
</div>
@endsection

@push("scripts")

<script>
function showRate(data)
{
    const rate = document.getElementById("rate");

    if(data.get("number") == 0)
    {
        rate.innerHTML = "";
        return;
    }

    if(data.get("profile") == 0)
    {
        rate.innerHTML = "";
        return;
    }

    fetch("{{ route('lcr.checkrate') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
        },
        body: data
    })
    .then(response => response.text())
    .then(text => {
        rate.innerHTML = text;
    })
    .catch(err => {
        console.error("AJAX Error", err);
    });
}

document.addEventListener('DOMContentLoaded', function()
{
    const formCheckRate = document.getElementById('formCheckRate');
    const inputNumber = document.getElementById('number');
    const inputProfile = document.getElementById('profile');
    const selectDirection = document.getElementById('direction');

    const submitEvent = new Event("submit", { cancelable: true });

    formCheckRate.addEventListener('submit', function(e)
    {
        e.preventDefault();

        const form = e.target;
        const data = new FormData(form);

        showRate(data);

        return false;
    });

    inputNumber.addEventListener('keyup', function()
    {
        formCheckRate.dispatchEvent(submitEvent);
    });
});
</script>

@endpush
