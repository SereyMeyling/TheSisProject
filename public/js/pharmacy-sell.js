$(function () {

    // ---- Toast (self-contained, no dependency on pharmacy.js) ----
    function showToast(message, type = 'success') {
        if (!$('#toastContainer').length) {
            $('body').append('<div id="toastContainer" class="toast-container-custom"></div>');
        }
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-times-circle' : 'fa-info-circle');
        const $toast = $(`<div class="toast-custom ${type}"><i class="fas ${icon}"></i><span>${message}</span></div>`);
        $('#toastContainer').append($toast);
        setTimeout(() => { $toast.fadeOut(200, function () { $(this).remove(); }); }, 3000);
    }

    function showErrors(box, xhr) {
        const box$ = $(box);
        let msg = 'មានបញ្ហា សូមព្យាយាមម្តងទៀត';
        if (xhr.responseJSON?.errors) {
            msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
        } else if (xhr.responseJSON?.message) {
            msg = xhr.responseJSON.message;
        }
        box$.html(msg).removeClass('d-none');
    }

    // ---- Medicine dropdown ----
    function medicineOptionsHtml(list) {
        return list.map(m =>
            `<option value="${m.medicine_id}" data-price="${m.selling_price}">
                ${m.medicine_name} ${m.strength ?? ''} (ស្តុក ${m.stock_total} គ្រាប់)
            </option>`
        ).join('');
    }

    function loadMedicineSelect($select) {
        $.get(routes.sellSearch).done((list) => $select.html(medicineOptionsHtml(list)));
    }

    // load once on page ready for the first row
    $('#sellItemsBody .sell-medicine').each(function () { loadMedicineSelect($(this)); });

    $('#btnAddSellRow').on('click', function () {
        const $row = $('#sellItemsBody tr:first').clone();
        $row.find('input').val('');
        $('#sellItemsBody').append($row);
        loadMedicineSelect($row.find('.sell-medicine'));
    });

    $('#sellItemsBody').on('click', '.btn-remove-row', function () {
        if ($('#sellItemsBody tr').length > 1) $(this).closest('tr').remove();
        recalcSellTotal();
    });

    $('#sellItemsBody').on('input change', '.sell-qty, .sell-medicine', recalcSellTotal);

    function recalcSellTotal() {
        let total = 0;
        $('#sellItemsBody tr').each(function () {
            const price = parseFloat($(this).find('.sell-medicine option:selected').data('price')) || 0;
            const qty = parseInt($(this).find('.sell-qty').val()) || 0;
            total += price * qty;
        });
        $('#sellTotalPreview').text(total.toFixed(2));
    }

    // ---- Sale history ----
    function loadHistory() {
        $.get(routes.sellHistory).done((res) => {
            const rows = res.data || [];
            const body = rows.map(s => `
                <tr>
                    <td>${s.sale_date}</td>
                    <td>${s.patient_name}</td>
                    <td class="text-right">$${s.total_amount.toFixed(2)}</td>
                    <td class="text-center">
                        <a href="${s.pdf_url}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="text-center text-muted">មិនទាន់មានការលក់</td></tr>';
            $('#saleHistoryBody').html(body);
        });
    }
    loadHistory();

    // ---- Submit sale ----
    $('#sellForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const items = [];
        $('#sellItemsBody tr').each(function () {
            items.push({
                medicine_id: $(this).find('.sell-medicine').val(),
                quantity: $(this).find('.sell-qty').val(),
            });
        });

        $.ajax({
            url: routes.sellStore, method: 'POST',
            data: { _token: csrf, patient_id: $('[name=patient_id]').val(), items },
        }).done((res) => {
            $form[0].reset();
            $('#sellErrors').addClass('d-none');
            $('#sellItemsBody').html($('#sellItemsBody tr:first').clone().find('input').val('').end());
            recalcSellTotal();
            loadHistory();
            showToast('លក់ជោគជ័យ — កំពុងបើក PDF', 'success');
            window.open(res.pdf_url, '_blank');
        }).fail((xhr) => { showErrors('#sellErrors', xhr); showToast('លក់មិនបានសម្រេច', 'error'); });
    });

});
