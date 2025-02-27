@php
    if (isset($extension) && $extension) {
        if (isset($device) && $device) {
            $actionUrl = route('extensions.update-device', [$extension, $device]);
        } else {
            $actionUrl = route('extensions.store-device', [$extension]);
        }
    }
@endphp
<form id="device_form" method="POST" action="{{$actionUrl}}">
    @if (isset($device) && $device)
        @method('put')
    @endif
    @csrf
    @if(isset($extension) && $extension->extension_uuid)
        <input type="hidden" id="extension_uuid" name="extension_uuid" value="{{$extension->extension_uuid}}" />
        <input type="hidden" id="device_uuid" name="device_uuid" value="" />
    @endif
    <div class="mb-3">
        <label for="device_address" class="col-form-label">Mac Address</label>
        <input type="text" class="form-control" id="device_address" name="device_address" placeholder="Enter the MAC address" value="{{$device->device_address ?? ''}}"
               @if (isset($device) && $device) readonly @endif
        />
        <div class="error text-danger" id="device_address_error"></div>
        <div class="error text-danger" id="device_address_modified_error"></div>
    </div>
    <div class="mb-3 position-relative">
        <label for="template-select" class="col-form-label">Template</label>
        @php $templateDir = public_path('resources/templates/provision'); @endphp
        <select name="device_template" class="form-select" id="template-select">
            <option value="" selected>Choose template</option>
            @foreach($vendors ?? [] as $vendor)
                <optgroup label='{{$vendor->name}}'>
                    @if (is_dir($templateDir.'/'.$vendor->name))
                        @php $templates = scandir($templateDir.'/'.$vendor->name); @endphp
                        @foreach($templates as $dir)
                            @if ($dir != "." && $dir != ".." && $dir[0] != '.' && is_dir($templateDir.'/'.$vendor->name.'/'.$dir))
                                <option @if (isset($device->device_template) && $device->device_template == $vendor->name."/".$dir) selected @endif value='{{$vendor->name."/".$dir}}'>{{$vendor->name."/".$dir}}</option>
                            @endif
                        @endforeach
                    @endif
                </optgroup>
            @endforeach
        </select>
        <div class="error text-danger" id="device_template_error"></div>
    </div>
    {{--
    <div class="mb-3 position-relative text-center">
        <img src="https://dummyimage.com/400x300/d4d4d4/2e2e2e.png&text=Phone+Picture" />
    </div>
    --}}
    <div class="mb-3 position-relative">
        <label for="profile-select" class="col-form-label">Profile</label>
        <select name="device_profile_uuid" class="form-select" id="profile-select">
            <option value="" selected>Choose profile</option>
            @foreach($profiles ?? [] as $profile)
                <option @if (isset($device->device_profile_uuid) && $device->device_profile_uuid == $profile->device_profile_uuid) selected @endif value='{{$profile->device_profile_uuid}}'>{{$profile->device_profile_name}}</option>
            @endforeach
        </select>
        <div class="error text-danger" id="device_profile_uuid_error"></div>
    </div>
    @if($extensions)
        <div class="mb-3 position-relative">
            <label for="extension-select" class="col-form-label">Extension</label>
            <select name="extension_uuid" class="form-select" id="extension-select">
                <option value="" selected>Choose extension</option>
                @foreach($extensions as $extensionItem)
                    <option @php
                                if($device && $device->extension() && $device->extension()->extension == $extensionItem->extension) {
                                    print 'selected';
                                }
                            @endphp value='{{$extensionItem->extension_uuid}}'>{{$extensionItem->extension}}</option>
                @endforeach
            </select>
            <div class="error text-danger" id="device_profile_uuid_error"></div>
        </div>
    @endif
    <div>
        {{-- Assuming that's modal if the extension variable exists --}}
        @if(isset($extension) && $extension->extension_uuid)
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        @else
            <a href="{{ route('devices.index') }}" class="btn btn-light me-2">Cancel</a>
        @endif
        <button type="button" class="btn btn-primary save-device-btn">Save</button>
    </div>
</form>
