/**
 * File Path: public/assets/custom/js/app.js
 *
 * SIB-K Custom Application JavaScript
 * Template: Qovex Admin Template
 * Framework: Bootstrap 5, jQuery
 *
 * Consolidate semua custom JavaScript di sini untuk mengurangi inline scripts
 *
 * @package    SIB-K
 * @category   JavaScript
 * @author     Development Team
 * @created    2025-01-07
 * @version    1.0.0
 */

"use strict";

/**
 * ==========================================================================
 * TABLE OF CONTENTS
 * ==========================================================================
 *
 * 1. Global Variables & Configuration
 * 2. Initialization
 * 3. Alert & Notification Handlers
 * 4. Form Validation Helpers
 * 5. AJAX Utilities
 * 6. DataTable Helpers
 * 7. Modal Helpers
 * 8. File Upload Handlers
 * 9. Chart Initialization Helpers
 * 10. Date & Time Utilities
 * 11. Number & Currency Utilities
 * 12. String Utilities
 * 13. Confirmation Dialogs
 * 14. Session & Counseling Helpers
 * 15. Assessment Helpers
 * 16. Violation Helpers
 * 17. Search & Filter Helpers
 * 18. Export Helpers
 * 19. Utility Functions
 * 20. Event Handlers
 */

/**
 * ==========================================================================
 * 1. GLOBAL VARIABLES & CONFIGURATION
 * ==========================================================================
 */

const SIBK = {
  version: "1.0.0",
  baseUrl: window.location.origin,
  apiUrl: window.location.origin + "/api",
  csrfToken: null,
  currentUser: {},
  config: {
    alertDuration: 5000,
    loadingDelay: 300,
    datatablePageLength: 10,
    chartColors: ["#556ee6", "#34c38f", "#f46a6a", "#f1b44c", "#50a5f1", "#74788d"],
  },
};

/**
 * ==========================================================================
 * 2. INITIALIZATION
 * ==========================================================================
 */

$(document).ready(function () {
  SIBK.init();
});

SIBK.init = function () {
  // Get CSRF token
  SIBK.csrfToken = $('meta[name="csrf-token"]').attr("content");

  // Initialize components
  SIBK.initAlerts();
  SIBK.initTooltips();
  SIBK.initPopovers();
  SIBK.initFormValidation();
  SIBK.initAjaxSetup();
  SIBK.initDeleteConfirmation();
  SIBK.initSelectAll();
  SIBK.initSearchDebounce();

  console.log("SIBK App v" + SIBK.version + " initialized");
};

/**
 * ==========================================================================
 * 3. ALERT & NOTIFICATION HANDLERS
 * ==========================================================================
 */

/**
 * Initialize auto-dismiss alerts
 */
SIBK.initAlerts = function () {
  // Auto-hide alerts after duration
  setTimeout(function () {
    $(".alert:not(.alert-permanent)").fadeOut("slow", function () {
      $(this).remove();
    });
  }, SIBK.config.alertDuration);

  // Close button handler
  $(document).on("click", ".alert .btn-close", function () {
    $(this)
      .closest(".alert")
      .fadeOut("slow", function () {
        $(this).remove();
      });
  });
};

/**
 * Show alert message
 * @param {string} message - Alert message
 * @param {string} type - Alert type (success, danger, warning, info)
 * @param {string} container - Container selector (default: '#alert-container')
 */
SIBK.showAlert = function (message, type = "success", container = "#alert-container") {
  type = type || "success";
  const iconMap = {
    success: "mdi-check-circle",
    danger: "mdi-alert-circle",
    warning: "mdi-alert",
    info: "mdi-information",
  };

  const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="mdi ${iconMap[type]} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

  $(container).prepend(alertHtml);

  // Auto-hide after duration
  setTimeout(function () {
    $(container + " .alert")
      .first()
      .fadeOut("slow", function () {
        $(this).remove();
      });
  }, SIBK.config.alertDuration);
};

/**
 * Show toast notification
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, danger, warning, info)
 */
SIBK.showToast = function (message, type = "success") {
  // Implement toast notification if needed
  // For now, use alert
  SIBK.showAlert(message, type);
};

/**
 * Initialize tooltips
 */
SIBK.initTooltips = function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
};

/**
 * Initialize popovers
 */
SIBK.initPopovers = function () {
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
};

/**
 * ==========================================================================
 * 4. FORM VALIDATION HELPERS
 * ==========================================================================
 */

/**
 * Initialize form validation
 */
SIBK.initFormValidation = function () {
  // Bootstrap validation
  const forms = document.querySelectorAll(".needs-validation");
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false
    );
  });
};

/**
 * Validate form before submit
 * @param {string} formId - Form ID
 * @returns {boolean}
 */
SIBK.validateForm = function (formId) {
  const form = document.getElementById(formId);
  if (!form) return false;

  if (!form.checkValidity()) {
    form.classList.add("was-validated");
    return false;
  }
  return true;
};

/**
 * Show field error
 * @param {string} fieldName - Field name
 * @param {string} message - Error message
 */
SIBK.showFieldError = function (fieldName, message) {
  const field = $('[name="' + fieldName + '"]');
  field.addClass("is-invalid");
  field.siblings(".invalid-feedback").remove();
  field.after('<div class="invalid-feedback d-block">' + message + "</div>");
};

/**
 * Clear field errors
 * @param {string} formId - Form ID
 */
SIBK.clearFieldErrors = function (formId) {
  $("#" + formId + " .is-invalid").removeClass("is-invalid");
  $("#" + formId + " .invalid-feedback").remove();
};

/**
 * ==========================================================================
 * 5. AJAX UTILITIES
 * ==========================================================================
 */

/**
 * Setup AJAX defaults
 */
SIBK.initAjaxSetup = function () {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": SIBK.csrfToken,
      "X-Requested-With": "XMLHttpRequest",
    },
  });
};

/**
 * Generic AJAX request
 * @param {string} url - Request URL
 * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
 * @param {object} data - Request data
 * @param {function} successCallback - Success callback
 * @param {function} errorCallback - Error callback
 */
SIBK.ajax = function (url, method, data, successCallback, errorCallback) {
  $.ajax({
    url: url,
    type: method,
    data: data,
    dataType: "json",
    beforeSend: function () {
      SIBK.showLoading();
    },
    success: function (response) {
      SIBK.hideLoading();
      if (successCallback) {
        successCallback(response);
      }
    },
    error: function (xhr, status, error) {
      SIBK.hideLoading();
      if (errorCallback) {
        errorCallback(xhr, status, error);
      } else {
        SIBK.handleAjaxError(xhr);
      }
    },
  });
};

/**
 * Handle AJAX errors
 * @param {object} xhr - XMLHttpRequest object
 */
SIBK.handleAjaxError = function (xhr) {
  let message = "Terjadi kesalahan. Silakan coba lagi.";

  if (xhr.responseJSON) {
    if (xhr.responseJSON.message) {
      message = xhr.responseJSON.message;
    }

    // Handle validation errors
    if (xhr.responseJSON.errors) {
      const errors = xhr.responseJSON.errors;
      for (const field in errors) {
        SIBK.showFieldError(field, errors[field][0]);
      }
      return;
    }
  } else if (xhr.status === 404) {
    message = "Halaman tidak ditemukan.";
  } else if (xhr.status === 500) {
    message = "Terjadi kesalahan pada server.";
  } else if (xhr.status === 401) {
    message = "Sesi Anda telah berakhir. Silakan login kembali.";
    setTimeout(function () {
      window.location.href = "/login";
    }, 2000);
  }

  SIBK.showAlert(message, "danger");
};

/**
 * Show loading overlay
 */
SIBK.showLoading = function () {
  if ($("#loading-overlay").length === 0) {
    $("body").append(`
            <div id="loading-overlay" class="spinner-overlay">
                <div class="spinner-border-custom"></div>
            </div>
        `);
  }
  $("#loading-overlay").fadeIn(SIBK.config.loadingDelay);
};

/**
 * Hide loading overlay
 */
SIBK.hideLoading = function () {
  $("#loading-overlay").fadeOut(SIBK.config.loadingDelay);
};

/**
 * ==========================================================================
 * 6. DATATABLE HELPERS
 * ==========================================================================
 */

/**
 * Initialize DataTable with default options
 * @param {string} tableId - Table ID
 * @param {object} options - Custom options
 * @returns {object} DataTable instance
 */
SIBK.initDataTable = function (tableId, options = {}) {
  const defaultOptions = {
    pageLength: SIBK.config.datatablePageLength,
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Semua"],
    ],
    language: {
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data per halaman",
      zeroRecords: "Data tidak ditemukan",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      infoFiltered: "(difilter dari _MAX_ total data)",
      paginate: {
        first: "Pertama",
        last: "Terakhir",
        next: "Selanjutnya",
        previous: "Sebelumnya",
      },
    },
    responsive: true,
    autoWidth: false,
  };

  const mergedOptions = $.extend({}, defaultOptions, options);
  return $("#" + tableId).DataTable(mergedOptions);
};

/**
 * Reload DataTable
 * @param {object} table - DataTable instance
 */
SIBK.reloadDataTable = function (table) {
  if (table && typeof table.ajax === "object") {
    table.ajax.reload(null, false);
  } else if (table) {
    table.draw(false);
  }
};

/**
 * ==========================================================================
 * 7. MODAL HELPERS
 * ==========================================================================
 */

/**
 * Show modal
 * @param {string} modalId - Modal ID
 */
SIBK.showModal = function (modalId) {
  const modal = new bootstrap.Modal(document.getElementById(modalId));
  modal.show();
};

/**
 * Hide modal
 * @param {string} modalId - Modal ID
 */
SIBK.hideModal = function (modalId) {
  const modalEl = document.getElementById(modalId);
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) {
    modal.hide();
  }
};

/**
 * Load content into modal via AJAX
 * @param {string} modalId - Modal ID
 * @param {string} url - Content URL
 */
SIBK.loadModalContent = function (modalId, url) {
  SIBK.ajax(url, "GET", {}, function (response) {
    $("#" + modalId + " .modal-body").html(response.html || response);
    SIBK.showModal(modalId);
  });
};

/**
 * ==========================================================================
 * 8. FILE UPLOAD HANDLERS
 * ==========================================================================
 */

/**
 * Handle file input change
 * @param {object} input - File input element
 * @param {string} previewId - Preview container ID
 */
SIBK.handleFileUpload = function (input, previewId) {
  const file = input.files[0];
  if (!file) return;

  // Show file name
  const fileName = file.name;
  $(input).next(".custom-file-label").html(fileName);

  // Preview image if applicable
  if (file.type.match("image.*") && previewId) {
    const reader = new FileReader();
    reader.onload = function (e) {
      $("#" + previewId)
        .attr("src", e.target.result)
        .show();
    };
    reader.readAsDataURL(file);
  }
};

/**
 * Validate file size
 * @param {object} input - File input element
 * @param {number} maxSizeMB - Maximum size in MB
 * @returns {boolean}
 */
SIBK.validateFileSize = function (input, maxSizeMB) {
  const file = input.files[0];
  if (!file) return false;

  const maxSize = maxSizeMB * 1024 * 1024; // Convert to bytes
  if (file.size > maxSize) {
    SIBK.showAlert(`Ukuran file maksimal ${maxSizeMB}MB`, "danger");
    $(input).val("");
    return false;
  }
  return true;
};

/**
 * Validate file type
 * @param {object} input - File input element
 * @param {array} allowedTypes - Allowed MIME types
 * @returns {boolean}
 */
SIBK.validateFileType = function (input, allowedTypes) {
  const file = input.files[0];
  if (!file) return false;

  if (!allowedTypes.includes(file.type)) {
    SIBK.showAlert("Tipe file tidak diizinkan", "danger");
    $(input).val("");
    return false;
  }
  return true;
};

/**
 * ==========================================================================
 * 9. CHART INITIALIZATION HELPERS
 * ==========================================================================
 */

/**
 * Initialize line chart
 * @param {string} canvasId - Canvas element ID
 * @param {array} labels - Chart labels
 * @param {array} datasets - Chart datasets
 * @param {object} options - Chart options
 * @returns {object} Chart instance
 */
SIBK.initLineChart = function (canvasId, labels, datasets, options = {}) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;

  const defaultOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: true,
        position: "bottom",
      },
    },
    scales: {
      y: {
        beginAtZero: true,
      },
    },
  };

  const mergedOptions = $.extend(true, {}, defaultOptions, options);

  return new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: datasets,
    },
    options: mergedOptions,
  });
};

/**
 * Initialize bar chart
 * @param {string} canvasId - Canvas element ID
 * @param {array} labels - Chart labels
 * @param {array} datasets - Chart datasets
 * @param {object} options - Chart options
 * @returns {object} Chart instance
 */
SIBK.initBarChart = function (canvasId, labels, datasets, options = {}) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;

  return new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: datasets,
    },
    options: options,
  });
};

/**
 * Initialize doughnut chart
 * @param {string} canvasId - Canvas element ID
 * @param {array} labels - Chart labels
 * @param {array} data - Chart data
 * @param {array} colors - Chart colors
 * @returns {object} Chart instance
 */
SIBK.initDoughnutChart = function (canvasId, labels, data, colors = null) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;

  colors = colors || SIBK.config.chartColors;

  return new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: labels,
      datasets: [
        {
          data: data,
          backgroundColor: colors,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: "bottom",
        },
      },
    },
  });
};

/**
 * ==========================================================================
 * 10. DATE & TIME UTILITIES
 * ==========================================================================
 */

/**
 * Format date to Indonesian format
 * @param {string} dateString - Date string
 * @returns {string}
 */
SIBK.formatDate = function (dateString) {
  if (!dateString) return "-";

  const date = new Date(dateString);
  const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

  return `${days[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

/**
 * Format datetime to Indonesian format
 * @param {string} datetimeString - Datetime string
 * @returns {string}
 */
SIBK.formatDateTime = function (datetimeString) {
  if (!datetimeString) return "-";

  const date = new Date(datetimeString);
  const dateStr = SIBK.formatDate(datetimeString);
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");

  return `${dateStr}, ${hours}:${minutes}`;
};

/**
 * Get relative time (time ago)
 * @param {string} datetimeString - Datetime string
 * @returns {string}
 */
SIBK.timeAgo = function (datetimeString) {
  if (!datetimeString) return "-";

  const date = new Date(datetimeString);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000); // in seconds

  if (diff < 60) return "Baru saja";
  if (diff < 3600) return Math.floor(diff / 60) + " menit yang lalu";
  if (diff < 86400) return Math.floor(diff / 3600) + " jam yang lalu";
  if (diff < 604800) return Math.floor(diff / 86400) + " hari yang lalu";
  if (diff < 2592000) return Math.floor(diff / 604800) + " minggu yang lalu";
  if (diff < 31536000) return Math.floor(diff / 2592000) + " bulan yang lalu";
  return Math.floor(diff / 31536000) + " tahun yang lalu";
};

/**
 * ==========================================================================
 * 11. NUMBER & CURRENCY UTILITIES
 * ==========================================================================
 */

/**
 * Format number with thousand separator
 * @param {number} num - Number to format
 * @returns {string}
 */
SIBK.formatNumber = function (num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

/**
 * Format currency (Indonesian Rupiah)
 * @param {number} amount - Amount to format
 * @returns {string}
 */
SIBK.formatCurrency = function (amount) {
  return "Rp " + SIBK.formatNumber(amount);
};

/**
 * ==========================================================================
 * 12. STRING UTILITIES
 * ==========================================================================
 */

/**
 * Truncate text
 * @param {string} text - Text to truncate
 * @param {number} length - Maximum length
 * @returns {string}
 */
SIBK.truncate = function (text, length) {
  if (text.length <= length) return text;
  return text.substring(0, length) + "...";
};

/**
 * Escape HTML
 * @param {string} text - Text to escape
 * @returns {string}
 */
SIBK.escapeHtml = function (text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
};

/**
 * ==========================================================================
 * 13. CONFIRMATION DIALOGS
 * ==========================================================================
 */

/**
 * Initialize delete confirmation
 */
SIBK.initDeleteConfirmation = function () {
  $(document).on("click", ".btn-delete", function (e) {
    e.preventDefault();
    const url = $(this).data("url") || $(this).attr("href");
    const message = $(this).data("message") || "Apakah Anda yakin ingin menghapus data ini?";

    SIBK.confirm(message, function () {
      SIBK.deleteRecord(url);
    });
  });
};

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @param {function} confirmCallback - Callback on confirm
 * @param {function} cancelCallback - Callback on cancel
 */
SIBK.confirm = function (message, confirmCallback, cancelCallback) {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      title: "Konfirmasi",
      text: message,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#f46a6a",
      cancelButtonColor: "#74788d",
      confirmButtonText: "Ya, Hapus!",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) {
        if (confirmCallback) confirmCallback();
      } else {
        if (cancelCallback) cancelCallback();
      }
    });
  } else {
    if (confirm(message)) {
      if (confirmCallback) confirmCallback();
    } else {
      if (cancelCallback) cancelCallback();
    }
  }
};

/**
 * Delete record via AJAX
 * @param {string} url - Delete URL
 */
SIBK.deleteRecord = function (url) {
  SIBK.ajax(url, "POST", { _method: "DELETE" }, function (response) {
    if (response.success) {
      SIBK.showAlert(response.message, "success");
      setTimeout(function () {
        location.reload();
      }, 1500);
    } else {
      SIBK.showAlert(response.message, "danger");
    }
  });
};

/**
 * ==========================================================================
 * 14. SESSION & COUNSELING HELPERS
 * ==========================================================================
 */

/**
 * Load students by class
 * @param {number} classId - Class ID
 * @param {string} targetSelectId - Target select element ID
 */
SIBK.loadStudentsByClass = function (classId, targetSelectId) {
  if (!classId) {
    $("#" + targetSelectId)
      .html('<option value="">Pilih Siswa</option>')
      .prop("disabled", true);
    return;
  }

  SIBK.ajax("/counselor/sessions/students-by-class", "GET", { class_id: classId }, function (response) {
    let options = '<option value="">Pilih Siswa</option>';
    if (response.students && response.students.length > 0) {
      response.students.forEach(function (student) {
        options += `<option value="${student.id}">${student.nisn} - ${student.full_name}</option>`;
      });
    }
    $("#" + targetSelectId)
      .html(options)
      .prop("disabled", false);
  });
};

/**
 * ==========================================================================
 * 15. ASSESSMENT HELPERS
 * ==========================================================================
 */

/**
 * Timer for assessment
 * @param {number} duration - Duration in minutes
 * @param {string} displayId - Timer display element ID
 * @param {function} onExpire - Callback when time expires
 */
SIBK.assessmentTimer = function (duration, displayId, onExpire) {
  let timer = duration * 60;
  const display = document.getElementById(displayId);

  const interval = setInterval(function () {
    const minutes = Math.floor(timer / 60);
    const seconds = timer % 60;

    display.textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

    // Warning when 5 minutes left
    if (timer === 300) {
      SIBK.showAlert("Waktu tersisa 5 menit!", "warning");
    }

    if (--timer < 0) {
      clearInterval(interval);
      if (onExpire) onExpire();
    }
  }, 1000);
};

/**
 * Auto-save assessment answer
 * @param {string} formId - Form ID
 * @param {string} url - Save URL
 * @param {number} interval - Auto-save interval in seconds
 */
SIBK.autoSaveAssessment = function (formId, url, interval = 30) {
  setInterval(function () {
    const formData = $("#" + formId).serialize();
    $.post(url, formData, function (response) {
      if (response.success) {
        console.log("Auto-saved at " + new Date().toLocaleTimeString());
      }
    });
  }, interval * 1000);
};

/**
 * ==========================================================================
 * 16. VIOLATION HELPERS
 * ==========================================================================
 */

/**
 * Calculate violation points
 * @param {number} categoryId - Violation category ID
 * @param {string} displayId - Points display element ID
 */
SIBK.calculateViolationPoints = function (categoryId, displayId) {
  if (!categoryId) {
    $("#" + displayId).text("0");
    return;
  }

  SIBK.ajax("/api/violations/points/" + categoryId, "GET", {}, function (response) {
    if (response.points) {
      $("#" + displayId).text(response.points);
    }
  });
};

/**
 * ==========================================================================
 * 17. SEARCH & FILTER HELPERS
 * ==========================================================================
 */

/**
 * Initialize search with debounce
 */
SIBK.initSearchDebounce = function () {
  let searchTimeout;
  $(document).on("keyup", ".search-input", function () {
    clearTimeout(searchTimeout);
    const $input = $(this);
    const url = $input.data("url");

    searchTimeout = setTimeout(function () {
      const query = $input.val();
      if (url && query.length >= 3) {
        SIBK.performSearch(url, query);
      }
    }, 500);
  });
};

/**
 * Perform search
 * @param {string} url - Search URL
 * @param {string} query - Search query
 */
SIBK.performSearch = function (url, query) {
  SIBK.ajax(url, "GET", { q: query }, function (response) {
    if (response.html) {
      $(".search-results").html(response.html);
    }
  });
};

/**
 * ==========================================================================
 * 18. EXPORT HELPERS
 * ==========================================================================
 */

/**
 * Export table to Excel
 * @param {string} tableId - Table ID
 * @param {string} filename - Export filename
 */
SIBK.exportToExcel = function (tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
  XLSX.writeFile(wb, filename + ".xlsx");
};

/**
 * Print page
 */
SIBK.printPage = function () {
  window.print();
};

/**
 * ==========================================================================
 * 19. UTILITY FUNCTIONS
 * ==========================================================================
 */

/**
 * Initialize select all checkbox
 */
SIBK.initSelectAll = function () {
  $(document).on("change", ".select-all-checkbox", function () {
    const isChecked = $(this).prop("checked");
    $(this).closest("table").find(".item-checkbox").prop("checked", isChecked);
  });

  $(document).on("change", ".item-checkbox", function () {
    const $table = $(this).closest("table");
    const totalItems = $table.find(".item-checkbox").length;
    const checkedItems = $table.find(".item-checkbox:checked").length;
    $table.find(".select-all-checkbox").prop("checked", totalItems === checkedItems);
  });
};

/**
 * Get selected checkboxes
 * @param {string} className - Checkbox class name
 * @returns {array} Array of selected values
 */
SIBK.getSelectedCheckboxes = function (className) {
  const selected = [];
  $("." + className + ":checked").each(function () {
    selected.push($(this).val());
  });
  return selected;
};

/**
 * Copy to clipboard
 * @param {string} text - Text to copy
 */
SIBK.copyToClipboard = function (text) {
  const $temp = $("<textarea>");
  $("body").append($temp);
  $temp.val(text).select();
  document.execCommand("copy");
  $temp.remove();
  SIBK.showAlert("Berhasil disalin!", "success");
};

/**
 * Scroll to top
 */
SIBK.scrollToTop = function () {
  $("html, body").animate({ scrollTop: 0 }, "smooth");
};

/**
 * Back to previous page
 */
SIBK.goBack = function () {
  window.history.back();
};

/**
 * ==========================================================================
 * 20. EVENT HANDLERS
 * ==========================================================================
 */

// Prevent double form submission
$(document).on("submit", "form", function () {
  const $form = $(this);
  if ($form.data("submitted") === true) {
    return false;
  }
  $form.data("submitted", true);

  // Re-enable after 3 seconds (in case of validation error)
  setTimeout(function () {
    $form.data("submitted", false);
  }, 3000);
});

// Handle file input change
$(document).on("change", 'input[type="file"]', function () {
  const $input = $(this);
  const previewId = $input.data("preview");
  SIBK.handleFileUpload(this, previewId);
});

// Handle number input (prevent non-numeric)
$(document).on("keypress", 'input[type="number"]', function (e) {
  if (e.which < 48 || e.which > 57) {
    e.preventDefault();
  }
});

// Handle class change for student filter
$(document).on("change", 'select[name="class_id"]', function () {
  const classId = $(this).val();
  const targetSelect = $(this).data("target") || "student_id";
  SIBK.loadStudentsByClass(classId, targetSelect);
});

// Handle scroll to top button
$(window).scroll(function () {
  if ($(this).scrollTop() > 100) {
    $(".scroll-to-top").fadeIn();
  } else {
    $(".scroll-to-top").fadeOut();
  }
});

$(document).on("click", ".scroll-to-top", function (e) {
  e.preventDefault();
  SIBK.scrollToTop();
});

// Console log for debugging
if (typeof console !== "undefined") {
  console.log("%c SIBK App Ready ", "background: #556ee6; color: #fff; padding: 5px; border-radius: 3px;");
}
