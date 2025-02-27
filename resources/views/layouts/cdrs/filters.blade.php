<div x-data="{ openFilters: false }">
    <button type="button" class="btn dropdown-toggle mb-3" x-on:click="openFilters = !openFilters">
        Filters
        @if ($count = $component->getFilterBadgeCount())
            <span class="badge bg-info">
                {{ $count }}
            </span>
        @endif
    </button>
    <div x-show="openFilters">
        <div class="row mb-3 row-cols-lg-auto g-3 align-items-end">
            <div class="col-md-2 col-lg-2 col-sm-6">
                <label class="form-label">Date Range</label>
                <input type="text" class="form-control date" id="dateRangeFilter" data-toggle="date-picker"
                    data-cancel-class="btn-warning">
            </div>

            <div class="col-md-1 col-lg-1 col-sm-3">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button"
                        id="{{ $component->getTableName() }}-dropdown-{{ $component->getFilterByKey('direction')->getKey() }}"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ $component->getFilterByKey('direction')->getName() }}
                    </button>
                    <div class="dropdown-menu "
                        aria-labelledby="{{ $component->getTableName() }}-dropdown-{{ $component->getFilterByKey('direction')->getKey() }}">
                        <div class="px-4 py-3">
                            <div class="form-check">
                                <input type="checkbox"
                                    id="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('direction')->getKey() }}@if ($component->getFilterByKey('direction')->hasCustomPosition()) -{{ $component->getFilterByKey('direction')->getCustomPosition() }} @endif-select-all"
                                    wire:input="selectAllFilterOptions('direction')" class="form-check-input">
                                <label class="form-check-label"
                                    for="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('direction')->getKey() }}@if ($component->getFilterByKey('direction')->hasCustomPosition()) -{{ $component->getFilterByKey('direction')->getCustomPosition() }} @endif-select-all">All</label>
                            </div>

                            @foreach ($component->getFilterByKey('direction')->getOptions() as $key => $value)
                                <div class="form-check"
                                    wire:key="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('direction')->getKey() }}@if ($component->getFilterByKey('direction')->hasCustomPosition()) -{{ $component->getFilterByKey('direction')->getCustomPosition() }} @endif-multiselect-{{ $key }}">
                                    <input class="form-check-input" type="checkbox"
                                        id="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('direction')->getKey() }}@if ($component->getFilterByKey('direction')->hasCustomPosition()) -{{ $component->getFilterByKey('direction')->getCustomPosition() }} @endif-{{ $loop->index }}"
                                        value="{{ $key }}"
                                        wire:key="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('direction')->getKey() }}@if ($component->getFilterByKey('direction')->hasCustomPosition()) -{{ $component->getFilterByKey('direction')->getCustomPosition() }} @endif-{{ $loop->index }}"
                                        wire:model.live.stop="table.filters.{{ $component->getFilterByKey('direction')->getKey() }}">
                                    <label class="form-check-label"
                                        for="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('direction')->getKey() }}@if ($component->getFilterByKey('direction')->hasCustomPosition()) -{{ $component->getFilterByKey('direction')->getCustomPosition() }} @endif-{{ $loop->index }}">{{ $value }}</label>
                                </div>
                            @endforeach


                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-2 col-lg-2 col-sm-4">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button"
                        id="{{ $component->getTableName() }}-dropdown-{{ $component->getFilterByKey('call_category')->getKey() }}"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ $component->getFilterByKey('call_category')->getName() }}
                    </button>
                    <div class="dropdown-menu "
                        aria-labelledby="{{ $component->getTableName() }}-dropdown-{{ $component->getFilterByKey('call_category')->getKey() }}">
                        <div class="px-4 py-3">
                            <div class="form-group mb-1">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search..."
                                    oninput="filterCategories()">
                            </div>
                            <div class="form-check category-item">
                                <input type="checkbox"
                                    id="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('call_category')->getKey() }}@if ($component->getFilterByKey('call_category')->hasCustomPosition()) -{{ $component->getFilterByKey('call_category')->getCustomPosition() }} @endif-select-all"
                                    wire:input="selectAllFilterOptions('call_category')" class="form-check-input">
                                <label class="form-check-label"
                                    for="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('call_category')->getKey() }}@if ($component->getFilterByKey('call_category')->hasCustomPosition()) -{{ $component->getFilterByKey('call_category')->getCustomPosition() }} @endif-select-all">All</label>
                            </div>

                            @foreach ($component->getFilterByKey('call_category')->getOptions() as $key => $value)
                                <div class="form-check category-item"
                                    wire:key="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('call_category')->getKey() }}@if ($component->getFilterByKey('call_category')->hasCustomPosition()) -{{ $component->getFilterByKey('call_category')->getCustomPosition() }} @endif-multiselect-{{ $key }}">
                                    <input class="form-check-input" type="checkbox"
                                        id="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('call_category')->getKey() }}@if ($component->getFilterByKey('call_category')->hasCustomPosition()) -{{ $component->getFilterByKey('call_category')->getCustomPosition() }} @endif-{{ $loop->index }}"
                                        value="{{ $key }}"
                                        wire:key="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('call_category')->getKey() }}@if ($component->getFilterByKey('call_category')->hasCustomPosition()) -{{ $component->getFilterByKey('call_category')->getCustomPosition() }} @endif-{{ $loop->index }}"
                                        wire:model.live.stop="table.filters.{{ $component->getFilterByKey('call_category')->getKey() }}">
                                    <label class="form-check-label"
                                        for="{{ $component->getTableName() }}-filter-{{ $component->getFilterByKey('call_category')->getKey() }}@if ($component->getFilterByKey('call_category')->hasCustomPosition()) -{{ $component->getFilterByKey('call_category')->getCustomPosition() }} @endif-{{ $loop->index }}">{{ $value }}</label>
                                </div>
                            @endforeach


                        </div>

                    </div>
                </div>
            </div>

        </div>



        {{-- <div class="card card-body"> --}}



        {{-- </div> --}}
    </div>
</div>


@push('scripts')
    <script>
        function filterCategories() {
            var input, filter, categories, category;
            input = document.getElementById('searchInput');
            filter = input.value.toUpperCase();
            categories = document.getElementsByClassName('category-item');

            for (var i = 0; i < categories.length; i++) {
                category = categories[i].getElementsByTagName('label')[0];
                if (category.innerHTML.toUpperCase().indexOf(filter) > -1) {
                    categories[i].style.display = '';
                } else {
                    categories[i].style.display = 'none';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {

            // Get the current browser URL
            const urlString = window.location.href;
            // Create a URL object
            const url = new URL(urlString);

            // Extract specific query parameters
            const dateFrom = url.searchParams.get("table[filters][date_from]");
            const dateTo = url.searchParams.get("table[filters][date_to]");

            // Initialize date variables with default values
            const formattedDateFrom = '{{ now()->format("m/d/y") }}';
            const formattedDateTo = '{{ now()->format("m/d/y") }}';

            // Check if dateFrom and dateTo are not null before proceeding
            if (dateFrom !== null && dateTo !== null) {
                // Split the date string by "-"
                const dateFromParts = dateFrom.split("-");
                // Reformat the date
                const formattedDateFrom = `${dateFromParts[1]}/${dateFromParts[2]}/${dateFromParts[0].slice(-2)}`;

                // Split the date string by "-"
                const dateToParts = dateTo.split("-");
                // Reformat the date
                const formattedDateTo = `${dateToParts[1]}/${dateToParts[2]}/${dateToParts[0].slice(-2)}`;
            }

            $('#dateRangeFilter').daterangepicker({
                timePicker: false,
                startDate: formattedDateFrom,
                endDate: formattedDateTo,
                locale: {
                    format: 'MM/DD/YY'
                }
            }).on('apply.daterangepicker', function(e, picker) {
                @this.setDateRange(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format(
                    'YYYY-MM-DD'))

            });
        });
    </script>
@endpush
