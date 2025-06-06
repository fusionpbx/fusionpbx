import $ from "jquery";
import select2 from 'select2';
window.$ = window.jQuery= $;
select2($);

document.addEventListener('livewire:init', () => {
    console.log('livewire:init');
    initSelect2();

    Livewire.on('relation-added', () => {
        console.log('relation-added');
        setTimeout(() => {
            initSelect2();

        }, 100);
    });

    Livewire.on('relation-removed', () => {
        console.log('relation-removed');
        setTimeout(() => {
            initSelect2();
        }, 100);
    });

    function initSelect2(root = document) {
        console.log('initSelect2');
        const elements = root.querySelectorAll('.select2-contact-search');

        elements.forEach(element => {
            if (element.hasAttribute('data-select2-initialized')) {
                return;
            }

            const index = element.getAttribute('data-index');
            const componentId = element.closest('[wire\\:id]')?.getAttribute('wire:id');

            if (!componentId) return;

            $(element).select2({
                placeholder: 'Search contact...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '/api/contacts/search',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(contact => {
                                return {
                                    id: contact.id,
                                    text: contact.name
                                };
                            })
                        };
                    },
                    cache: true
                }
            }).on('select2:select', function (e) {
                const data = e.params.data;
                Livewire.find(componentId).selectContact(data.id, data.text, index);
            }).on('select2:unselect', function () {
                Livewire.find(componentId).$set(`relations.${index}.relation_contact_uuid`, '');
            });

            element.setAttribute('data-select2-initialized', 'true');
        });
    }
});
