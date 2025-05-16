<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6>Relations</h6>
            <button type="button" class="btn btn-sm btn-primary" wire:click="addRelation">Add
                Relation</button>
        </div>
        <div class="card-body">
            @foreach($relations as $index => $relation)
            <div class="row mb-3">
                <div class="col-md-5">
                    <label for="relations.{{ $index }}.relation_label" class="form-label">Label</label>
                    <select wire:model="relations.{{ $index }}.relation_label" class="form-select">
                        <option value="">Select a label</option>
                        <option value="associate">Associate</option>
                        <option value="child">Child</option>
                        <option value="employee">Employee</option>
                        <option value="member">Member</option>
                        <option value="other">Other</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="contact_search" class="form-label">Contact</label>
                    <div class="position-relative">
                        <input type="text"
                               class="form-control"
                               placeholder="Search contact..."
                               wire:model.live.debounce.300ms="searchTerm"
                               wire:click="$set('searchResults', [])"
                               autocomplete="off">
                        
                        @if(!empty($relations[$index]['relation_contact_uuid']))
                            <div class="selected-contact mt-2 p-2 border rounded bg-light">
                                {{ $relations[$index]['contact_name'] }}
                                <button type="button" class="btn btn-sm text-danger float-end" 
                                        wire:click="$set('relations.{{ $index }}.relation_contact_uuid', '')">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        @endif
                        
                        @if(count($searchResults) > 0 && empty($relations[$index]['relation_contact_uuid']))
                            <div class="position-absolute start-0 end-0 mt-1 bg-white border rounded shadow-sm z-3 search-results" style="max-height: 200px; overflow-y: auto;">
                                @foreach($searchResults as $result)
                                    <div class="p-2 search-item border-bottom" 
                                         wire:click="selectContact('{{ $result['id'] }}', '{{ $result['name'] }}', {{ $index }})">
                                        {{ $result['name'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-danger mb-2" wire:click="removeRelation({{ $index }})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>