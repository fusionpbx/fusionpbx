@php
$isCustomPlaceholder = isset($placeholder);
@endphp

@props([
    'multiple' => false,
    'required' => false,
    'disabled' => false,
    'placeholder' => __('Drag & Drop your files or <span class="filepond--label-action"> Browse </span>'),
])

@php
if (! $wireModelAttribute = $attributes->whereStartsWith('wire:model')->first()) {
    throw new Exception("You must wire:model to the filepond input.");
}

$pondProperties = $attributes->except([
    'class',
    'placeholder',
    'required',
    'disabled',
    'multiple',
    'wire:model',
]);

// convert keys from kebab-case to camelCase
$pondProperties = collect($pondProperties)
    ->mapWithKeys(fn ($value, $key) => [Illuminate\Support\Str::camel($key) => $value])
    ->toArray();

$pondLocalizations = __('livewire-filepond::filepond');
@endphp

<div
    class="{{ $attributes->get('class') }}"
    wire:ignore
    x-cloak
    x-data="{
        model: @entangle($wireModelAttribute),
        isMultiple: @js($multiple),
        current: undefined,
        files: [],
        async loadModel() {
            if (! this.model) {
              return;
            }

            if (this.isMultiple) {
              await Promise.all(Object.values(this.model).map(async (picture) => this.files.push(await URLtoFile(picture))))
              return;
            }

            this.files.push(await URLtoFile(this.model))
        }
    }"
    x-init="async () => {
      await loadModel();

      const pond = LivewireFilePond.create($refs.input);

      pond.setOptions({
          allowMultiple: isMultiple,
          server: {
              process: async (fieldName, file, metadata, load, error, progress) => {
                  $dispatch('filepond-upload-started', '{{ $wireModelAttribute }}');
                  await @this.upload('{{ $wireModelAttribute }}', file, async (response) => {
                    let validationResult  = await @this.call('validateUploadedFile', response);
                        // Check server validation result
                        if (validationResult === true) {
                            // File is valid, dispatch the upload-finished event
                            load(response);
                            $dispatch('filepond-upload-finished', { '{{ $wireModelAttribute }}': response });
                        } else {
                            // Throw error after validating server side
                            error('Filepond Api Ignores This Message');
                            $dispatch('filepond-upload-reset', '{{ $wireModelAttribute }}');
                        }
                  }, error, progress);
              },
              revert: async (filename, load) => {
                  await @this.revert('{{ $wireModelAttribute }}', filename, load);
                  $dispatch('filepond-upload-reverted', {'attribute' : '{{ $wireModelAttribute }}'});
              },
              remove: async (file, load) => {
                  await @this.remove('{{ $wireModelAttribute }}', file.name);
                  load();
                  $dispatch('filepond-upload-file-removed', {'attribute' : '{{ $wireModelAttribute }}'});
              },
          },
          required: @js($required),
          disabled: @js($disabled),
      });

      pond.setOptions(@js($pondLocalizations));

      pond.setOptions(@js($pondProperties));

      @if($isCustomPlaceholder)
      pond.setOptions({ labelIdle: @js($placeholder) });
      @endif

      pond.addFiles(files)
      pond.on('addfile', (error, file) => {
          if (error) console.log(error);
      });

      $wire.on('filepond-reset-{{ $wireModelAttribute }}', () => {
          pond.removeFiles();
      });
    }"
>
    <input type="file" x-ref="input">
</div>
