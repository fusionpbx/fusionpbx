<div class="col-12">
    <div class="page-title-box">
        @if($breadcrumbs)
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    @foreach($breadcrumbs as $title => $path)
                        @if($path)
                            <li class="breadcrumb-item"><a href="{{ route($path) }}">{{ $title }}</a></li>
                        @else
                            <li class="breadcrumb-item active">{{ $title }}</li>
                        @endif
                    @endforeach
                </ol>
            </div>
        @endif
        <h4 class="page-title">{{ $title }}</h4>
    </div>
</div>
