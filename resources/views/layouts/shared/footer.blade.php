<!-- Footer Start -->
      {{-- @include('layouts.shared/loading') --}}

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-4">
                <div class="text-center">
                    {{-- <i class=" dripicons-question h1 text-danger"></i> --}}
                    <i class="uil uil-times-circle h1 text-danger"></i>
                    <h3 class="mt-3">Are you sure?</h3>
                    <p class="mt-3">Do you really want to delete this? This process cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="javascript:performConfirmedDeleteAction();" class="btn btn-danger me-2">Delete</a>
            </div> <!-- end modal footer -->
        </div> <!-- end modal content-->
    </div> <!-- end modal dialog-->
</div> <!-- end modal-->


{{-- Confirm password reset Modal --}}
<div class="modal fade" id="confirmPasswordResetModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-4">
                <div class="text-center">
                    {{-- <i class=" dripicons-question h1 text-danger"></i> --}}
                    <i class="uil uil-info-circle h1 text-warning"></i>
                    <h3 class="mt-3">Are you sure?</h3>
                    <p class="mt-3">Do you really want to reset this user's password? The user get an email with a password reset link</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="javascript:performConfirmedPasswordResetAction();" class="btn btn-warning me-2">Reset</a>
            </div> <!-- end modal footer -->
        </div> <!-- end modal content-->
    </div> <!-- end modal dialog-->
</div> <!-- end modal-->


<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <script>document.write(new Date().getFullYear())</script> © {{ config('app.name', 'Laravel') }}
            </div>
            <div class="col-md-6">
                <div class="text-md-end footer-links d-none d-md-block">
                    <a href="javascript: void(0);">About</a>
                    <a href="javascript: void(0);">Support</a>
                    <a href="javascript: void(0);">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- end Footer -->