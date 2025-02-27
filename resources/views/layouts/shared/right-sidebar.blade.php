<!-- Right Sidebar -->
<div id="right-modal" class="modal fade right-modal" tabindex="-1" role="dialog" aria-hidden="true" aria-labelledby="right-modal-modalLabel">
    <div class="modal-dialog modal-sm modal-right" style="flex-flow:initial !important; ">
        <div class="modal-content">
            <div class="modal-header border-0 modal-colored-header bg-secondary">
                <h5 class="modal-title text-white" id="right-modal-modalLabel">Select company</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">

                <div class="input-group flex-nowrap mb-3">
                    <input type="text" class="form-control" placeholder="Search..." aria-label="domainSearchInput" id="domainSearchInput" aria-describedby="basic-addon1">
                    <span class="input-group-text" id="basic-addon1"> <i class="uil uil-search"></i></span>
                </div>
    
                @if (Session::get("domains"))
                <div class="list-group" id ="domainSearchList">
                    @foreach(session()->get('domains') as $domain)
                        <div class="listgroup">
                            <a href="#" class="list-group-item list-group-item-action
                                @if (Session::get("domain_uuid") === $domain->domain_uuid ) active @endif "
                                onclick="selectDomain('{{ $domain->domain_uuid }}')">
    
                                <div class="d-flex w-100 justify-content-between text-break">
                                    <h5 class="mb-1">{{ $domain->domain_description }}</h5>
                                </div>
                                <small class="text-muted">{{ $domain->domain_name }}</small>
                            </a>
                        </div>
                    @endforeach
                </div>
                @endif
            
                
               
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<!-- /End-bar -->