
    $(document).ready(function () {

        const $container = $('#billingTableContainer');
        const $search = $('#search');
        const $statusFilter = $('#statusFilter');
        const $visitTypeFilter = $('#visitTypeFilter');
        let debounceTimer;

        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });

        function currentParams(page) {
            return {
                search: $search.val(),
                status: $statusFilter.val(),
                visit_type: $visitTypeFilter.val(),
                date_from: $('#dateFrom').val(),
                date_to: $('#dateTo').val(),
                page: page || 1,
            };
        }

        function resetCreateInvoiceModal() {
            const $form = $('#createInvoiceForm');

            $form[0].reset();
            $form.find('.form-control, .custom-select').removeClass('is-invalid is-valid');
            $('#createInvoiceAlert').addClass('d-none').text('');
            $('#error_create_patient_name').text('');
            $('#error_create_admission_id').text('');

            itemIndex = 1;
            $('#invoiceItemsTbody').html(defaultCreateItemRowHtml(0));
            $('#createInvoiceGrandTotal').text('$15.00');

            $('#select_patient_id').val('');

            $('#labelVisitOPD').addClass('active');
            $('#labelVisitIPD').removeClass('active');
            $('input[name="visit_type"][value="opd"]').prop('checked', true);

            $('#admissionPickerWrap').addClass('d-none');
            $('#create_admission_id').prop('required', false).val('');

            $('#btnSubmitCreateInvoice').prop('disabled', false)
                .html('<i class="fas fa-save mr-1"></i> បង្កើតវិក្កយបត្រ (Save Invoice)');
        }

        $('#modalCreateInvoice').on('show.bs.modal hidden.bs.modal', function () {
            resetCreateInvoiceModal();
        });

        function showBillingToast(message, duration = 4000) {
            $('#billingSuccessToastMessage').text(message);
            $('#billingSuccessToast').removeClass('d-none');

            clearTimeout(window.__billingToastTimer);
            window.__billingToastTimer = setTimeout(function () {
                $('#billingSuccessToast').fadeOut(300, function () {
                    $(this).addClass('d-none').show();
                });
            }, duration);
        }

        function loadInvoices(page) {
            const params = currentParams(page);
            $container.addClass('loading');

            $.ajax({
                url: window.billingConfig.indexUrl,
                method: 'GET',
                data: params,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    $container.html(res.html);
                    $('[data-toggle="tooltip"]').tooltip();
                    $('#statTotalInvoices').text(res.totalInvoices);
                    $('#statTotalRevenue').text('$' + res.totalRevenue);
                    $('#statTotalUnpaid').text('$' + res.totalUnpaid);

                    const qs = $.param(params);
                    history.replaceState(null, '', window.billingConfig.indexUrl + '?' + qs);
                },
                error: function () {
                    console.error('Failed to load invoice list.');
                },
                complete: function () {
                    $container.removeClass('loading');
                }
            });
        }

        // Filters
        $search.on('keyup', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { loadInvoices(1); }, 400);
        });
        $statusFilter.on('change', function () { loadInvoices(1); });
        $('#dateFrom, #dateTo').on('change', function () { loadInvoices(1); });
        $visitTypeFilter.on('change', function () { loadInvoices(1); });
        $('#btnResetFilters').on('click', function () {
            $search.val('');
            $statusFilter.val('');
            $visitTypeFilter.val('');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            loadInvoices(1);
        });

        $(document).on('click', '#billingTableContainer .pagination a', function (e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href) return;
            const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
            loadInvoices(page);
        });

        // Existing patient selection auto-fill
        $('#select_patient_id').on('change', function () {
            const $opt = $(this).find(':selected');
            if ($opt.val()) {
                $('#create_patient_name').val($opt.data('name'));
                $('#create_patient_phone').val($opt.data('phone'));
            }
        });

        // Visit Type toggle: OPD vs IPD
        $('input[name="visit_type"]').on('change', function () {
            if ($(this).val() === 'ipd') {
                $('#admissionPickerWrap').removeClass('d-none');
                $('#create_admission_id').prop('required', true);
            } else {
                $('#admissionPickerWrap').addClass('d-none');
                $('#create_admission_id').prop('required', false).val('');
            }
        });

        // Admission selection auto-fills patient (IPD invoices belong to the admitted patient)
        $('#create_admission_id').on('change', function () {
            const $opt = $(this).find(':selected');
            if ($opt.val()) {
                $('#create_patient_name').val($opt.data('name'));
                $('#create_patient_phone').val($opt.data('phone'));
                $('#select_patient_id').val($opt.data('patient-id'));
            }
        });

        // ── Dynamic Invoice Item Rows (Create) ────────────────────────────
        let itemIndex = 1;

        function itemRowHtml(idx, opts = {}) {
            const { desc = '', qty = 1, price = '0.00' } = opts;
            return `
                <tr class="item-row">
                    <td>
                        <select name="items[${idx}][item_type]" class="form-control form-control-sm item-type" required>
                            <option value="consultation">ពិគ្រោះជំងឺ (Consultation)</option>
                            <option value="prescription">ថ្នាំពេទ្យ (Medicine)</option>
                            <option value="lab_test">មន្ទីរពិសោធន៍ (Lab Test)</option>
                            <option value="room">បន្ទប់សម្រាក (Room Fee)</option>
                            <option value="other">ផ្សេងៗ (Other)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${idx}][description]" class="form-control form-control-sm item-desc" placeholder="បរិយាយសេវា" value="${desc}" required>
                    </td>
                    <td>
                        <input type="number" name="items[${idx}][qty]" class="form-control form-control-sm item-qty text-center" value="${qty}" min="1" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[${idx}][unit_price]" class="form-control form-control-sm item-price text-right" value="${price}" min="0" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-subtotal text-right bg-light" value="${(qty * price).toFixed(2)}" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
        }
        function defaultCreateItemRowHtml(idx) {
            return itemRowHtml(idx, { desc: 'ថ្លៃពិគ្រោះជំងឺទូទៅ', qty: 1, price: '15.00' });
        }

        $('#btnAddInvoiceItem').on('click', function () {
            $('#invoiceItemsTbody').append(itemRowHtml(itemIndex));
            itemIndex++;
            recalculateGrandTotal();
        });

        $(document).on('click', '.btn-remove-item', function () {
            if ($('#invoiceItemsTbody .item-row').length > 1) {
                $(this).closest('tr').remove();
                recalculateGrandTotal();
            }
        });

        $(document).on('input', '.item-qty, .item-price', function () {
            const $row = $(this).closest('tr');
            const qty = parseFloat($row.find('.item-qty').val()) || 0;
            const price = parseFloat($row.find('.item-price').val()) || 0;
            $row.find('.item-subtotal').val((qty * price).toFixed(2));
            recalculateGrandTotal();
        });

        function recalculateGrandTotal() {
            let total = 0;
            $('#invoiceItemsTbody .item-row').each(function () {
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                const price = parseFloat($(this).find('.item-price').val()) || 0;
                total += (qty * price);
            });
            $('#createInvoiceGrandTotal').text('$' + total.toFixed(2));
        }

        // Submit Create Invoice
        $('#createInvoiceForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSubmitCreateInvoice');
            const $alert = $('#createInvoiceAlert');

            $form.find('.form-control, .custom-select').removeClass('is-invalid');
            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងរក្សាទុក...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> បង្កើតវិក្កយបត្រ (Save Invoice)');
                    $('#modalCreateInvoice').modal('hide');
                    loadInvoices(1);
                    showBillingToast(res.message || 'បានបង្កើតវិក្កយបត្រដោយជោគជ័យ!');
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> បង្កើតវិក្កយបត្រ (Save Invoice)');
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        $alert.removeClass('d-none').html(Object.values(errors).flat().join('<br>'));
                    } else {
                        $alert.removeClass('d-none').text(xhr.responseJSON?.message || 'An error occurred.');
                    }
                }
            });
        });

        // ── PAY INVOICE ────────────────────────────────────────────────

        $(document).on('click', '.btn-pay-now', function () {
            const id = $(this).data('id');
            const number = $(this).data('number');
            const patient = $(this).data('patient');
            const total = parseFloat($(this).data('total'));
            const balance = parseFloat($(this).data('balance'));

            $('#pay_invoice_id').val(id);  
            $('#payInvoiceNumber').text(number);
            $('#payPatientName').text(patient);
            $('#payTotalAmount').text('$' + total.toFixed(2));
            $('#payBalanceAmount').text('$' + balance.toFixed(2));
            $('#pay_amount').val(balance.toFixed(2)).attr('max', balance.toFixed(2));

            $('#payInvoiceForm').attr('action', window.billingConfig.baseUrl + "/" + id + "/pay");
            $('#modalPayInvoice').modal('show');
        });

        let khqrPollTimer = null;
        let khqrPollAttempts = 0;

        $('input[name="payment_method"]').on('change', function () {
            const isKhqr = $(this).val() === 'khqr';
            $('#khqrDisplayContainer').toggleClass('d-none', !isKhqr);
            clearInterval(khqrPollTimer);

            if (isKhqr) {
                generateInvoiceKHQR();
            } else {
                $('#khqrStatusMessage').html('');
            }
        });

        function generateInvoiceKHQR() {
            const invoiceId = $('#pay_invoice_id').val();
            const billNumber = $('#payInvoiceNumber').text().trim();
            const balance = parseFloat($('#payBalanceAmount').text().replace(/[^0-9.]/g, ''));

            if (!invoiceId || !balance || balance <= 0) {
                $('#khqrQrWrap').html('<span class="text-danger small">ទិន្នន័យវិក្កយបត្រមិនត្រឹមត្រូវ</span>');
                return;
            }

            $('#khqrQrWrap').html('<div class="spinner-border text-primary"></div>');
            $('#khqrBankInfo').addClass('d-none');
            $('#khqrStatusMessage').html('<span class="text-muted">កំពុងបង្កើត QR...</span>');

            $.post(window.billingConfig.generateKhqrUrl, {
                _token: window.billingConfig.csrfToken,
                invoice_id: invoiceId,
                amount: balance,
                currency: 'USD',
                bill_number: billNumber
            }, function (res) {
                if (!res.success) {
                    $('#khqrQrWrap').html('<span class="text-danger small">' + (res.message || 'មិនអាចបង្កើត QR បានទេ') + '</span>');
                    return;
                }

                if (res.mode === 'manual') {
                    // Static bank QR — cashier confirms via the main "Confirm Payment" button below.
                    $('#khqrQrWrap').html(`<img src="${res.qr_image_url}" alt="KHQR" style="max-width: 200px;">`);
                    $('#khqrBankName').text(res.bank_name || '-');
                    $('#khqrAccountName').text(res.account_name || '-');
                    $('#khqrAccountNumber').text(res.account_number || '-');
                    $('#khqrBankInfo').removeClass('d-none');
                    $('#khqrStatusMessage').html('');
                } else {
                    // Bakong dynamic mode — SVG/base64 image + live polling
                    const src = String(res.qr).startsWith('data:') || String(res.qr).startsWith('http')
                        ? res.qr
                        : res.qr; // qr string itself isn't an image — see note below
                    $('#khqrQrWrap').html(`<img src="data:image/svg+xml;base64,${res.qr}" alt="KHQR" style="max-width: 200px;">`);
                    pollPaymentStatus(res.md5);
                }
            }).fail(function () {
                $('#khqrQrWrap').html('<span class="text-danger small">មិនអាចបង្កើត QR បានទេ</span>');
            });
        }

        // Manual-mode confirm button — since there's no Bakong API to poll, the cashier vouches for it
        $(document).on('click', '#btnConfirmManualPaid', function () {
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងបញ្ជាក់...');

            $.post(`${window.billingConfig.baseUrl}/${$('#pay_invoice_id').val()}/pay`, {
                _token: window.billingConfig.csrfToken,
                amount: $('#pay_amount').val(),
                payment_method: 'khqr_manual',
                transaction_ref: 'MANUAL-CONFIRMED',
            }, function () {
                showBillingToast('ការទូទាត់វិក្កយបត្របានជោគជ័យ!');
                $('#modalPayInvoice').modal('hide');
                loadInvoices(1);
            }).fail(function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> បញ្ជាក់ថាបានទទួលប្រាក់');
                toastr.error(xhr.responseJSON?.message || 'មានបញ្ហា');
            });
        });

        function pollPaymentStatus(md5) {
            clearInterval(khqrPollTimer);
            khqrPollAttempts = 0;

            khqrPollTimer = setInterval(function () {
                khqrPollAttempts++;
                if (khqrPollAttempts > 60) {
                    clearInterval(khqrPollTimer);
                    $('#khqrStatusMessage').html('<span class="text-danger">QR បានផុតកំណត់ សូមព្យាយាមម្តងទៀត</span>');
                    return;
                }

                $.get(`${window.billingConfig.checkKhqrStatusUrlBase}/${md5}`, function (res) {
                    if (res.paid) {
                        clearInterval(khqrPollTimer);
                        $('#khqrStatusMessage').html('<span class="text-success"><i class="fas fa-check-circle mr-1"></i>ការទូទាត់បានជោគជ័យ</span>');
                        showBillingToast('ការទូទាត់វិក្កយបត្របានជោគជ័យ!');

                        setTimeout(function () {
                            $('#modalPayInvoice').modal('hide');
                            loadInvoices(1);
                        }, 1200);
                    } else if (res.expired) {
                        clearInterval(khqrPollTimer);
                        $('#khqrStatusMessage').html('<span class="text-danger">QR បានផុតកំណត់ សូមព្យាយាមម្តងទៀត</span>');
                    }
                });
            }, 3000);
        }

        $('#modalPayInvoice').on('hidden.bs.modal', function () {
            clearInterval(khqrPollTimer);
        });

        $('#payInvoiceForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSubmitPayInvoice');
            const $alert = $('#payInvoiceAlert');

            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងដំណើរការ...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> បញ្ជាក់ការទូទាត់ (Confirm Payment)');
                    $('#modalPayInvoice').modal('hide');
                    loadInvoices(1);
                    showBillingToast(res.message || 'បានបញ្ចប់ការទូទាត់វិក្កយបត្រដោយជោគជ័យ!');
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> បញ្ជាក់ការទូទាត់ (Confirm Payment)');
                    $alert.removeClass('d-none').text(xhr.responseJSON?.message || 'Failed to process payment.');
                }
            });
        });

        // ── RECEIPT ────────────────────────────────────────────────────

        $(document).on('click', '.btn-view-receipt', function () {
            const id = $(this).data('id');
            $('#modalReceiptBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
            $('#modalReceiptInvoice').modal('show');

            $.ajax({
                url: window.billingConfig.baseUrl + "/" + id,
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    $('#modalReceiptBody').html(res.html);
                },
                error: function () {
                    $('#modalReceiptBody').html('<div class="alert alert-danger mb-0">ពុំអាចទាញយកទិន្នន័យវិក្កយបត្របានឡើយ។</div>');
                }
            });
        });

        $('#btnPrintReceipt').on('click', function () {
            window.print();
        });

        // ── VIEW INVOICE DETAIL ───────────────────────────────────────

        function getInvoiceStatusBadge(status) {
            const statusMap = {
                paid:      { text: 'បានទូទាត់រួច(Paid)',   class: 'badge-status-paid' },
                partial:   { text: 'បង់ខ្លះ(Partial)',       class: 'badge-status-partial' },
                unpaid:    { text: 'មិនទាន់បង់(Unpaid)',     class: 'badge-status-unpaid' },
                cancelled: { text: 'បានលុបចោល(Cancelled)',  class: 'badge-status-cancelled' },
            };
            const item = statusMap[status] || { text: status, class: 'badge-status-default' };
            return `<span class="status-badge ${item.class}">${item.text}</span>`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            const pad = n => String(n).padStart(2, '0');

            let hours = d.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;

            const time = `${hours}:${pad(d.getMinutes())}:${pad(d.getSeconds())} ${ampm}`;
            const date = `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
            return `${date} ${time}`;
        }

        $(document).on('click', '.btn-view-detail', function () {
            const id = $(this).data('id');

            $('#viewInvoiceItems').html(`<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>`);
            $('#viewCancelledBanner').addClass('d-none');
            $('#modalInvoiceDetail').modal('show');

            $.ajax({
                url: window.billingConfig.baseUrl + "/" + id,
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    const inv = res.data;
                    const patient = inv.patient || {};
                    const admission = inv.admission || null;
                    const genderMap = { male: 'ប្រុស', female: 'ស្រី', other: 'ផ្សេងៗ' };

                    $('#viewInvoiceNumber').text(inv.invoice_number ?? '-');
                    $('#viewInvoiceStatus').html(getInvoiceStatusBadge(inv.status));
                    $('#viewInvoiceDate').text(formatDate(inv.created_at));
                    $('#viewCreatedBy').text(inv.creator?.name ?? '-');

                    $('#viewPatientCode').text(patient.patient_code ?? '-');
                    $('#viewPatientGender').text(genderMap[patient.sex] ?? '-');
                    $('#viewPatientName').text(inv.patient_name ?? '-');
                    $('#viewPatientPhone').text(inv.patient_phone ?? '-');

                    if (admission) {
                        $('#viewVisitType').html(`<span class="badge badge-purple">អ្នកជំងឺសម្រាកពេទ្យ (IPD)</span>`);
                        $('#viewAdmissionNumber').text(admission.admission_id ?? '-');
                        $('#viewRoomNumber').text(admission.room?.room_number ?? '-');
                        $('#viewAdmissionWrap, #viewRoomWrap').removeClass('d-none');
                    } else {
                        $('#viewVisitType').html(`<span class="badge badge-secondary">អ្នកជំងឺក្រៅ (OPD)</span>`);
                        $('#viewAdmissionWrap, #viewRoomWrap').addClass('d-none');
                    }

                    $('#viewCancelledBanner').toggleClass('d-none', inv.status !== 'cancelled');
                    if (inv.status === 'cancelled') {
                        $('#viewCancelReason').text(inv.cancel_reason ?? '-');
                        $('#viewCancelledBy').text(inv.canceller?.name ?? '-');
                        $('#viewCancelledAt').text(formatDate(inv.cancelled_at));
                    }

                    let rows = '<tr><td colspan="5" class="text-center text-muted">No items found</td></tr>';
                    if (inv.items && inv.items.length > 0) {
                        rows = inv.items.map(item => `
                            <tr>
                                <td>${item.description}</td>
                                <td><span class="badge badge-light border text-uppercase">${item.item_type}</span></td>
                                <td class="text-center">${item.qty}</td>
                                <td class="text-right">$${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td class="text-right">$${parseFloat(item.subtotal).toFixed(2)}</td>
                            </tr>
                        `).join('');
                    }
                    $('#viewInvoiceItems').html(rows);

                    $('#viewInvoiceTotal').text('$' + parseFloat(inv.total_amount).toFixed(2));
                    $('#viewInvoicePaid').text('$' + parseFloat(inv.paid_amount).toFixed(2));
                    $('#viewInvoiceBalance').text('$' + parseFloat(inv.balance).toFixed(2));

                    let paymentsHtml = '<p class="text-muted mb-0">មិនទាន់មានការទូទាត់ (No payments recorded yet)</p>';
                    if (inv.payments && inv.payments.length > 0) {
                        const rows = inv.payments.map(p => `
                            <tr>
                                <td class="text-capitalize">${p.payment_method}</td>
                                <td>$${parseFloat(p.amount).toFixed(2)}</td>
                                <td>${formatDate(p.paid_at)}</td>
                                <td>${p.transaction_ref ?? '-'}</td>
                            </tr>
                        `).join('');
                        paymentsHtml = `<table class="table table-sm table-bordered mb-0"><thead><tr>
                            <th>វិធីទូទាត់ (Method)</th><th>ចំនួន (Amount)</th><th>កាលបរិច្ឆេទ (Date)</th><th>លេខយោង (Ref)</th>
                            </tr></thead><tbody>${rows}</tbody></table>`;
                    }
                    $('#viewPaymentsList').html(paymentsHtml);

                    $('#viewNotes').text(inv.notes ?? '-');
                },
                error: function (xhr) {
                    $('#viewInvoiceItems').html(`<tr><td colspan="5" class="text-center text-danger">Failed to load invoice detail</td></tr>`);
                    console.error(xhr.responseText);
                }
            });
        });

        // ── EDIT INVOICE ─────────────────────────────────────────────────

        let editItemIndex = 0;

        function editItemRowHtml(item, idx) {
            item = item || { item_type: 'consultation', description: '', qty: 1, unit_price: 0 };
            const qty = item.qty ?? 1;
            const price = parseFloat(item.unit_price ?? 0);
            return `
                <tr class="item-row">
                    <td>
                        <select name="items[${idx}][item_type]" class="form-control form-control-sm item-type" required>
                            <option value="consultation" ${item.item_type === 'consultation' ? 'selected' : ''}>ពិគ្រោះជំងឺ (Consultation)</option>
                            <option value="prescription" ${item.item_type === 'prescription' ? 'selected' : ''}>ថ្នាំពេទ្យ (Medicine)</option>
                            <option value="lab_test" ${item.item_type === 'lab_test' ? 'selected' : ''}>មន្ទីរពិសោធន៍ (Lab Test)</option>
                            <option value="room" ${item.item_type === 'room' ? 'selected' : ''}>បន្ទប់សម្រាក (Room Fee)</option>
                            <option value="other" ${item.item_type === 'other' ? 'selected' : ''}>ផ្សេងៗ (Other)</option>
                        </select>
                    </td>
                    <td><input type="text" name="items[${idx}][description]" class="form-control form-control-sm item-desc" value="${item.description ?? ''}" required></td>
                    <td><input type="number" name="items[${idx}][qty]" class="form-control form-control-sm item-qty text-center" value="${qty}" min="1" required></td>
                    <td><input type="number" step="0.01" name="items[${idx}][unit_price]" class="form-control form-control-sm item-price text-right" value="${price.toFixed(2)}" min="0" required></td>
                    <td><input type="text" class="form-control form-control-sm item-subtotal text-right bg-light" value="${(qty * price).toFixed(2)}" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-trash-alt"></i></button></td>
                </tr>
            `;
        }

        function recalculateEditGrandTotal() {
            let total = 0;
            $('#editInvoiceItemsTbody .item-row').each(function () {
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                const price = parseFloat($(this).find('.item-price').val()) || 0;
                total += (qty * price);
            });
            $('#editInvoiceGrandTotal').text('$' + total.toFixed(2));
        }

        $('#btnAddEditInvoiceItem').on('click', function () {
            $('#editInvoiceItemsTbody').append(editItemRowHtml(null, editItemIndex));
            editItemIndex++;
            recalculateEditGrandTotal();
        });

        $(document).on('click', '#editInvoiceItemsTbody .btn-remove-item', function () {
            if ($('#editInvoiceItemsTbody .item-row').length > 1) {
                $(this).closest('tr').remove();
                recalculateEditGrandTotal();
            }
        });

        $(document).on('input', '#editInvoiceItemsTbody .item-qty, #editInvoiceItemsTbody .item-price', function () {
            const $row = $(this).closest('tr');
            const qty = parseFloat($row.find('.item-qty').val()) || 0;
            const price = parseFloat($row.find('.item-price').val()) || 0;
            $row.find('.item-subtotal').val((qty * price).toFixed(2));
            recalculateEditGrandTotal();
        });

        $(document).on('click', '.btn-edit-invoice', function () {
            const id = $(this).data('id');
            const $alert = $('#editInvoiceAlert');
            $alert.addClass('d-none').text('');

            $.ajax({
                url: window.billingConfig.baseUrl + "/" + id + "/edit",
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    if (!res.success) {
                        showBillingToast(res.message || 'Invoice can no longer be edited.');
                        loadInvoices(1);
                        return;
                    }
                    const inv = res.data;

                    $('#edit_patient_name').val(inv.patient_name ?? '');
                    $('#edit_patient_phone').val(inv.patient_phone ?? '');
                    $('#edit_notes').val(inv.notes ?? '');

                    editItemIndex = 0;
                    $('#editInvoiceItemsTbody').empty();
                    (inv.items && inv.items.length ? inv.items : [null]).forEach(function (item) {
                        $('#editInvoiceItemsTbody').append(editItemRowHtml(item, editItemIndex));
                        editItemIndex++;
                    });
                    recalculateEditGrandTotal();

                    $('#editInvoiceForm').attr('action', window.billingConfig.baseUrl + "/" + id);
                    $('#modalEditInvoice').modal('show');
                },
                error: function (xhr) {
                    showBillingToast(xhr.responseJSON?.message || 'Failed to load invoice for editing.');
                }
            });
        });

        $('#editInvoiceForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSubmitEditInvoice');
            const $alert = $('#editInvoiceAlert');

            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងរក្សាទុក...');

            $.ajax({
                url: $form.attr('action'),
                method: 'PUT',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> រក្សាទុកការកែប្រែ (Save Changes)');
                    $('#modalEditInvoice').modal('hide');
                    loadInvoices(1);
                    showBillingToast(res.message || 'បានកែប្រែវិក្កយបត្រដោយជោគជ័យ!');
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> រក្សាទុកការកែប្រែ (Save Changes)');
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        $alert.removeClass('d-none').html(Object.values(errors).flat().join('<br>'));
                    } else {
                        $alert.removeClass('d-none').text(xhr.responseJSON?.message || 'An error occurred.');
                    }
                }
            });
        });

        // ── CANCEL INVOICE ───────────────────────────────────────────────

        $(document).on('click', '.btn-cancel-invoice', function () {
            const id = $(this).data('id');
            const number = $(this).data('number');

            $('#cancelInvoiceAlert').addClass('d-none').text('');
            $('#cancel_reason').val('');
            $('#cancelInvoiceNumber').text(number);
            $('#cancelInvoiceForm').attr('action', window.billingConfig.baseUrl + "/" + id + "/cancel");
            $('#modalCancelInvoice').modal('show');
        });

        $('#cancelInvoiceForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSubmitCancelInvoice');
            const $alert = $('#cancelInvoiceAlert');

            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងដំណើរការ...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-ban mr-1"></i> បញ្ជាក់ការលុបចោល (Confirm Cancel)');
                    $('#modalCancelInvoice').modal('hide');
                    loadInvoices(1);
                    showBillingToast(res.message || 'វិក្កយបត្រត្រូវបានលុបចោល!');
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-ban mr-1"></i> បញ្ជាក់ការលុបចោល (Confirm Cancel)');
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        $alert.removeClass('d-none').html(Object.values(errors).flat().join('<br>'));
                    } else {
                        $alert.removeClass('d-none').text(xhr.responseJSON?.message || 'Failed to cancel invoice.');
                    }
                }
            });
        });
    });
