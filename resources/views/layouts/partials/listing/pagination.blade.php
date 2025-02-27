<div class="col-4">
    @if($collection->count() > 0)
    <label class="form-label">Showing {{ $collection->firstItem() }} - {{ $collection->lastItem() }} of {{ $collection->total() }} results</label>
    @endif
</div>
<div class="col-8">
    <div class="float-end">
        {{ $collection->appends(request()->except('page'))->links() }}
    </div>
</div>
