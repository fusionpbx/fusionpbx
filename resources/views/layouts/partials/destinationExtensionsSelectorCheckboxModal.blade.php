@php
    /**
     * @property array $extensionsSelected
     * @property array $extensions
     * @property string $callbackOnClick
     * @property string $label
     */
@endphp
<div id="addDestinationBarMultiple" class="my-1">
    <a href="javascript:addDestinationMultipleModalShow();"
       class="btn btn-success">
        <i class="mdi mdi-plus"></i> {{ $label }}
    </a>
</div>
<div class="modal fade" id="addDestinationMultipleModal" role="dialog"
     aria-labelledby="addDestinationMultipleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDestinationMultipleModalLabel">{{ $label }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select one or more extensions to be added.</p>
                <div class="mb-3">
                    <input onkeyup="triggerDestinationSearch(this)" class="form-control" type="text" name="destination_multiple_search" placeholder="Search" value="" />
                </div>
                <div class="mb-3">
                    <input class="form-control" type="button" onclick="triggerDestinationAll()" name="destination_multiple_search_select_all" value="Select All Extensions" />
                </div>
                <div class="destination_multiple_wrapper">
                    <ul id="destinationMultipleListExtensions"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="{{ $callbackOnClick }}" class="btn btn-success">Add</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<style>
    #addDestinationMultipleModal .destination_multiple_wrapper {
        max-height: 300px;
        overflow-y: scroll;
        padding: 0.6em;
        border: 1px solid #dee2e6;
    }
    #addDestinationMultipleModal .destination_multiple_wrapper label {
        width: 100%;
    }
    #addDestinationMultipleModal ul {
        padding-left: 0;
        font-size: 1.3em;
        margin-bottom: 0;
    }
    #addDestinationMultipleModal ul li {
        list-style: none;
        line-height: 1.6em;
        padding-bottom: 0.5em;
    }
    #addDestinationMultipleModal ul li:last-child {
        padding-bottom: 0;
    }
</style>
<script>
    const destinationMultipleListExtensions = document.getElementById('destinationMultipleListExtensions')
    let destinationsSelected = [
        @foreach ($extensionsSelected as $extension)
        "{{$extension}}",
        @endforeach
    ];
    let destinations = [
        @foreach ($extensions as $extension)
            {
                label: "@if($extension->effective_caller_id_name) {{$extension->effective_caller_id_name}} @else Extension @endif - {{ $extension->extension}}",
                checked: false,
                value: "{{ $extension->extension }}"
            },
        @endforeach
    ];

    renderDestinations(destinations)

    function triggerDestinationSearch(elem) {
        if(elem.value.trim() !== '' && elem.value.trim().length > 0) {
            renderDestinations(destinations.filter(s => s.label.includes(elem.value.trim())))
        } else {
            renderDestinations(destinations)
        }
    }

    function triggerDestinationAll() {
        for(let i = 0; i < destinations.length; i++) {
            destinations[i].checked = true
        }
        renderDestinations(destinations)
    }
    function addDestinationMultipleModalShow() {
        $('#addDestinationMultipleModal').modal('show');
    }

    function renderDestinations(data) {
        destinationMultipleListExtensions.innerHTML = '';
        if(data.length === 0) {
            let elli = document.createElement('li')
            elli.classList.add('text-center')
            elli.innerText = 'No extensions found';
            destinationMultipleListExtensions.append(elli)
        } else {
            for (let i = 0; i < data.length; i++) {
                let el = document.createElement('input')
                el.type = 'checkbox'
                el.name = 'destination_multiple[]'
                el.value = data[i].value
                el.classList.add('form-check-input')
                el.classList.add('action_checkbox')
                if (data[i].checked || destinationsSelected.includes(data[i].value)) {
                    el.checked = true
                }
                el.onclick = function (event) {
                    data[i].checked = event.target.checked
                }
                let ellabel = document.createElement('label')
                ellabel.classList.add('form-check-label')
                ellabel.innerText = data[i].label
                let elli = document.createElement('li')
                ellabel.prepend(el)
                elli.append(ellabel)
                destinationMultipleListExtensions.append(elli)
            }
        }
    }
</script>
