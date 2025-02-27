<div class="text-xl-end mt-xl-0 mt-2">
    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('faxQueue.destroy', ':id') }}');" id="deleteMultipleActionButton" class="btn btn-danger mb-2 me-2 disabled">
            Delete Selected
        </a>
    @endif
    {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
    <a href="{{ route('faxQueue.list', ['scope' => (($selectedScope == 'local')?'global':'local')]) }}" class="btn btn-light mb-2 me-2">
        Show {{ (($selectedScope == 'local')?'global':'local') }} queue
    </a>
</div>
