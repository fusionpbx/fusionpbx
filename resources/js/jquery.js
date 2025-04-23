import $ from "jquery";
window.$ = window.jQuery= $;

$(function () {

    $(".duallistbox").bootstrapDualListbox({
        infoText: false,
        nonSelectedListLabel: 'Users',
        selectedListLabel: 'Current members',
    });

    function actualizarIndices(tableSelector) {
        $(tableSelector + ' tbody tr:not(.static-row)').each(function (index) {
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Función para crear botón de eliminar
    function crearBotonEliminar() {
        return `<td>
                    <button type="button" class="btn btn-danger btn-sm delete-row">
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </button>
                </td>`;
    }

    // Agregar fila dominio
    $('#add-domain').click(function () {
        const lastRow = $('#domains-table tbody tr:last');
        const newRow = lastRow.clone();

        // Agregar botón eliminar si no existe
        if (newRow.find('.delete-row').length === 0) {
            newRow.append(crearBotonEliminar());
        }

        newRow.insertBefore('#new-domain-row');
        actualizarIndices('#domains-table');

        // Ocultar el botón de agregar
        $('#add-domain').hide();
    });

    // 👉 Evento para eliminar fila
    $(document).on('click', '.delete-row', function () {
        $(this).closest('tr').remove();
        actualizarIndices('#domains-table');

        // Mostrar el botón de agregar si no hay filas dinámicas
        if ($('#domains-table tbody tr').length <= 1) {
            $('#add-domain').show();
        }
    });

    // Agregar fila setting
    $('#add-setting').click(function () {
        const lastRow = $('#settings-table tbody tr:last');
        const newRow = lastRow.clone();

        newRow.find('input, select').each(function () {
            if ($(this).attr('type') !== 'hidden') {
                $(this).val('');
            }
        });

        if (newRow.find('.delete-row').length === 0) {
            newRow.append(crearBotonEliminar());
        }

        newRow.insertBefore('#new-setting-row');
        actualizarIndices('#settings-table');
    });

    // Evento delegado para eliminar fila
    $(document).on('click', '.delete-row', function () {
        $(this).closest('tr').remove();
        actualizarIndices('#domains-table');
        actualizarIndices('#settings-table');
    });
});

