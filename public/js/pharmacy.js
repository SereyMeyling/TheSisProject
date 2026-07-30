$(function () {
    const csrf = "{{ csrf_token() }}";

    let currentFilter = null;
    let appliedFilters = {};

    const filterLabels = {
        f_name: "ឈ្មោះ",
        f_price_min: "តម្លៃពី",
        f_price_max: "តម្លៃដល់",
        f_stock: "ស្តុក",
        f_expiry_days: "ជិតផុតកំណត់",
    };
    const stockLabels = { normal: "ធម្មតា", low: "ជិតអស់", out: "អស់ស្តុក" };

    // ---- Toast ----
    function showToast(message, type = "success") {
        if (!$("#toastContainer").length) {
            $("body").append(
                '<div id="toastContainer" class="toast-container-custom"></div>',
            );
        }
        const icon =
            type === "success"
                ? "fa-check-circle"
                : type === "error"
                  ? "fa-times-circle"
                  : "fa-info-circle";
        const $toast = $(
            `<div class="toast-custom ${type}"><i class="fas ${icon}"></i><span>${message}</span></div>`,
        );
        $("#toastContainer").append($toast);
        setTimeout(() => {
            $toast.fadeOut(200, function () {
                $(this).remove();
            });
        }, 3000);
    }

    // ---- Refresh stat cards without full reload ----
    function refreshStats() {
        $.get(routes.stats).done((s) => {
            $("#statTotalMedicine").text(
                new Intl.NumberFormat().format(s.totalMedicine),
            );
            $("#statStockValue").text(
                "$" +
                    Number(s.stockValue).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }),
            );
            $("#statLowStock").text(new Intl.NumberFormat().format(s.lowStock));
            $("#statExpiringSoon").text(
                new Intl.NumberFormat().format(s.expiringSoon),
            );
        });
    }

    // ---- DataTable (single declaration) ----
    const table = $("#medicineTable").DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: {
            url: routes.data,
            type: "GET",
            data: function (d) {
                d.filter = currentFilter;
                Object.assign(d, appliedFilters);
            },
        },
        columns: [
            { data: "medicine_name", name: "medicine_name" },
            { data: "category", name: "category" },
            {
                data: "strength_display",
                name: "strength_display",
                orderable: false,
                searchable: false,
            },
            {
                data: "stock_total",
                name: "stock_total",
                className: "text-right",
            },
            {
                data: "selling_price",
                name: "selling_price",
                className: "text-right",
                render: function (val) {
                    return "$" + parseFloat(val).toFixed(2);
                },
            },
            { data: "nearest_expiry", name: "nearest_expiry" },
            {
                data: "actions",
                name: "actions",
                orderable: false,
                searchable: false,
            },
        ],
        createdRow: function (row, data) {
            if (data.is_low_stock && data.is_expiring_soon) {
                $(row).addClass("row-both-alert");
            } else if (data.is_low_stock) {
                $(row).addClass("row-low-stock");
            } else if (data.is_expiring_soon) {
                $(row).addClass("row-expiring");
            }
        },
    });

    // ---- Dashboard-card quick filter ----
    $(".stat-card-filter").on("click", function () {
        const filter = $(this).data("filter") || null;
        currentFilter = currentFilter === filter ? null : filter;
        $(".stat-card-filter").removeClass("stat-card-active");
        if (currentFilter) $(this).addClass("stat-card-active");
        table.ajax.reload();
    });

    // ---- Filter FAB open/close ----
    function openFilterPanel() {
        $("#filterPanel").removeClass("d-none");
        $("#filterBackdrop").removeClass("d-none");
        $("#filterFab").addClass("is-open");
    }
    function closeFilterPanel() {
        $("#filterPanel").addClass("d-none");
        $("#filterBackdrop").addClass("d-none");
        $("#filterFab").removeClass("is-open");
    }
    $("#filterFab").on("click", function () {
        $("#filterPanel").hasClass("d-none")
            ? openFilterPanel()
            : closeFilterPanel();
    });
    $("#filterPanelClose, #filterBackdrop").on("click", closeFilterPanel);

    // ---- Render active-filter chips ----
    function renderActiveFilters() {
        const keys = Object.keys(appliedFilters).filter(
            (k) => appliedFilters[k],
        );
        $("#filterBadge")
            .text(keys.length)
            .toggleClass("d-none", keys.length === 0);
        $("#filterFab").toggleClass("has-active", keys.length > 0);

        if (keys.length === 0) {
            $("#activeFilterBar").addClass("d-none");
            $("#activeFilterChips").empty();
            return;
        }

        const chips = keys
            .map((key) => {
                let val = appliedFilters[key];
                if (key === "f_stock") val = stockLabels[val] || val;
                if (key === "f_expiry_days") val = val + " ថ្ងៃ";
                return `<span class="filter-chip" data-key="${key}">${filterLabels[key]}: ${val} <i class="fas fa-times" data-remove="${key}"></i></span>`;
            })
            .join("");
        $("#activeFilterChips").html(chips);
        $("#activeFilterBar").removeClass("d-none");
    }

    // ---- Apply filter ----
    $("#filterApply").on("click", function () {
        appliedFilters = {
            f_name: $("#filterName").val(),
            f_price_min: $("#filterPriceMin").val(),
            f_price_max: $("#filterPriceMax").val(),
            f_stock: $("#filterStock").val(),
            f_expiry_days: $("#filterExpiry").val(),
        };
        Object.keys(appliedFilters).forEach((k) => {
            if (!appliedFilters[k]) delete appliedFilters[k];
        });

        renderActiveFilters();
        table.ajax.reload();
        closeFilterPanel();
        showToast("បានត្រងទិន្នន័យ", "info");
    });

    // ---- Clear (from panel) ----
    $("#filterClear").on("click", function () {
        $("#filterName, #filterPriceMin, #filterPriceMax").val("");
        $("#filterStock, #filterExpiry").val("");
        appliedFilters = {};
        renderActiveFilters();
        table.ajax.reload();
    });

    // ---- Clear all (from active-filter bar) ----
    $("#filterClearAll").on("click", function () {
        $("#filterName, #filterPriceMin, #filterPriceMax").val("");
        $("#filterStock, #filterExpiry").val("");
        appliedFilters = {};
        renderActiveFilters();
        table.ajax.reload();
        closeFilterPanel();
        showToast("សម្អាតតម្រងទាំងអស់", "info");
    });

    // ---- Remove single chip ----
    $("#activeFilterChips").on("click", "[data-remove]", function () {
        const key = $(this).data("remove");
        delete appliedFilters[key];
        const inputMap = {
            f_name: "#filterName",
            f_price_min: "#filterPriceMin",
            f_price_max: "#filterPriceMax",
            f_stock: "#filterStock",
            f_expiry_days: "#filterExpiry",
        };
        $(inputMap[key]).val("");
        renderActiveFilters();
        table.ajax.reload();
    });

    function showErrors(box, xhr) {
        const box$ = $(box);
        let msg = "មានបញ្ហា សូមព្យាយាមម្តងទៀត";
        if (xhr.responseJSON?.errors) {
            msg = Object.values(xhr.responseJSON.errors).flat().join("<br>");
        } else if (xhr.responseJSON?.message) {
            msg = xhr.responseJSON.message;
        }
        box$.html(msg).removeClass("d-none");
    }

    // ---- Create ----
    $("#createForm").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        $.ajax({ url: routes.store, method: "POST", data: $form.serialize() })
            .done(() => {
                $("#modalCreate").modal("hide");
                $form[0].reset();
                $("#createErrors").addClass("d-none");
                table.ajax.reload();
                refreshStats();
                showToast("បន្ថែមថ្នាំជោគជ័យ", "success");
            })
            .fail((xhr) => {
                showErrors("#createErrors", xhr);
                showToast("រក្សាទុកមិនបានសម្រេច", "error");
            });
    });

    // ---- Edit: open ----
    $("#medicineTable").on("click", ".btn-edit", function () {
        const id = $(this).data("id");
        $.get(`${routes.editBase}/${id}/edit`)
            .done((med) => {
                $("#edit_medicine_id").val(med.medicine_id);
                $("#edit_medicine_name").val(med.medicine_name);
                $("#edit_ndc_code").val(med.ndc_code);
                $("#edit_category").val(med.category);
                $("#edit_unit").val(med.unit);
                $("#edit_dosage_unit").val(med.dosage_unit);
                $("#edit_strength").val(med.strength);
                $("#edit_pieces_per_unit").val(med.pieces_per_unit);
                $("#edit_unit_price").val(med.unit_price);
                $("#edit_selling_price").val(med.selling_price);
                $("#edit_reorder_level").val(med.reorder_level);

                const rows =
                    (med.batches || [])
                        .map(
                            (b) =>
                                `<div>#${b.batch_number} — ${b.remaining_quantity}/${b.quantity_initial} គ្រាប់, ផុតកំណត់ ${b.expiry_date}</div>`,
                        )
                        .join("") || "<em>មិនទាន់មានស្តុក</em>";
                $("#edit_batches_list").html(rows);

                $("#editErrors").addClass("d-none");
                $("#modalEdit").modal("show");
            })
            .fail(() => showToast("មិនអាចទាញទិន្នន័យបានទេ", "error"));
    });

    // ---- Edit: submit ----
    $("#editForm").on("submit", function (e) {
        e.preventDefault();
        const id = $("#edit_medicine_id").val();
        $.ajax({
            url: `${routes.updateBase}/${id}`,
            method: "PUT",
            data: $(this).serialize(),
        })
            .done(() => {
                $("#modalEdit").modal("hide");
                table.ajax.reload(null, false);
                refreshStats();
                showToast("កែប្រែជោគជ័យ", "success");
            })
            .fail((xhr) => {
                showErrors("#editErrors", xhr);
                showToast("កែប្រែមិនបានសម្រេច", "error");
            });
    });

    // ---- Restock: open ----
    $("#medicineTable").on("click", ".btn-restock", function () {
        const id = $(this).data("id");
        $("#restock_medicine_id").val(id);
        $("#restock_name").text($(this).data("name"));
        $("#restockErrors").addClass("d-none");
        $("#restockForm")[0].reset();
        $("#restock_medicine_id").val(id); // reset() clears the hidden id too, so set it again after
        $("#restockLastBatchHint").addClass("d-none").empty();

        $.get(`${routes.editBase}/${id}/edit`)
            .done((med) => {
                const lastBatch =
                    med.batches && med.batches.length ? med.batches[0] : null;
                if (lastBatch) {
                    if (lastBatch.supplier_id) {
                        $('#restockForm [name="supplier_id"]').val(
                            lastBatch.supplier_id,
                        );
                    }
                    if (lastBatch.purchase_price) {
                        $('#restockForm [name="purchase_price"]').val(
                            lastBatch.purchase_price,
                        );
                    }
                    $("#restockLastBatchHint")
                        .html(
                            `<i class="fas fa-info-circle mr-1"></i>លេខបាច់លើកមុន៖ <strong>#${lastBatch.batch_number}</strong> — បំពេញអ្នកផ្គត់ផ្គង់/តម្លៃពីលើកមុនរួចហើយ អាចកែបានប្រសិនចង់ប្តូរ`,
                        )
                        .removeClass("d-none");
                }
                $("#modalRestock").modal("show");
            })
            .fail(() => {
                $("#modalRestock").modal("show"); // still open the modal even if the prefill fails
            });
    });
    // ---- Restock: submit ----
    $("#restockForm").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const id = $("#restock_medicine_id").val();
        $.ajax({
            url: `${routes.restockBase}/${id}/restock`,
            method: "POST",
            data: $form.serialize(),
        })
            .done(() => {
                $("#modalRestock").modal("hide");
                $form[0].reset();
                table.ajax.reload(null, false);
                refreshStats();
                showToast("បន្ថែមស្តុកជោគជ័យ", "success");
            })
            .fail((xhr) => {
                showErrors("#restockErrors", xhr);
                showToast("បន្ថែមស្តុកមិនបានសម្រេច", "error");
            });
    });

    // ---- Expiring detail ----
    $("#btnExpiringDetail").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $.get(routes.expiringDetail)
            .done((res) => {
                const rows = res.data || [];
                const body =
                    rows
                        .map((r, i) => {
                            let cls =
                                r.days_left <= 7
                                    ? "table-danger"
                                    : r.days_left <= 15
                                      ? "table-warning"
                                      : "table-light";
                            return `<tr class="${cls}">
                    <td>${i + 1}</td>
                    <td>${r.medicine_name}</td>
                    <td>${r.batch_number}</td>
                    <td class="text-right">${r.remaining_quantity}</td>
                    <td>${r.expiry_date}</td>
                    <td class="text-right">${r.days_left} ថ្ងៃ</td>
                </tr>`;
                        })
                        .join("") ||
                    '<tr><td colspan="6" class="text-center text-muted">មិនមានថ្នាំជិតផុតកំណត់ទេ</td></tr>';
                $("#expiringDetailBody").html(body);
                $("#modalExpiringDetail").modal("show");
            })
            .fail(() => showToast("មិនអាចទាញទិន្នន័យបានទេ", "error"));
    });

    // ---- Supplier: create ----
    $("#supplierForm").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        $.ajax({
            url: routes.supplierStore,
            method: "POST",
            data: $form.serialize(),
        })
            .done((res) => {
                $("#modalSupplier").modal("hide");
                $form[0].reset();
                $("#supplierErrors").addClass("d-none");
                const opt = `<option value="${res.supplier.supplier_id}">${res.supplier.name}</option>`;
                $('select[name="supplier_id"]').append(opt);
                showToast("បន្ថែមអ្នកផ្គត់ផ្គង់ជោគជ័យ", "success");
            })
            .fail((xhr) => {
                showErrors("#supplierErrors", xhr);
                showToast("រក្សាទុកមិនបានសម្រេច", "error");
            });
    });

    // ---- Detail: open ----
    $("#medicineTable").on("click", ".btn-detail", function () {
        const id = $(this).data("id");
        $.get(`${routes.detailsBase}/${id}/details`)
            .done((res) => {
                $("#detail_medicine_name").text(res.medicine_name);

                const rows =
                    (res.batches || [])
                        .map((b) => {
                            const outsHtml = b.outs.length
                                ? b.outs
                                      .map(
                                          (o) =>
                                              `<div>${o.date}: <span class="text-danger">-${o.quantity}</span></div>`,
                                      )
                                      .join("")
                                : '<span class="text-muted">—</span>';

                            return `<tr>
                        <td>${b.batch_number}</td>
                        <td>${b.date_in}</td>
                        <td class="text-right">${b.quantity_initial}</td>
                        <td>${b.expiry_date}</td>
                        <td class="text-right">$${parseFloat(b.purchase_price).toFixed(2)}</td>
                        <td>${outsHtml}</td>
                        <td class="text-right"><strong>${b.remaining_quantity}</strong></td>
                    </tr>`;
                        })
                        .join("") ||
                    '<tr><td colspan="7" class="text-center text-muted">មិនទាន់មានទិន្នន័យ</td></tr>';

                $("#detailBatchesBody").html(rows);
                $("#modalDetail").modal("show");
            })
            .fail(() => showToast("មិនអាចទាញទិន្នន័យលម្អិតបានទេ", "error"));
    });
});
