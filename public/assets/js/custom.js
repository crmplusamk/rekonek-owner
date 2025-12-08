// const loader = require("sass-loader");

//First document load
$(function () {
    js_load();
});

//Loader script for PJAX
function js_load()
{
    //Tooltip initiator
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );

    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    $('#filter > a.dropdown-toggle').on('click', function (event)
    {
        $(this).parent().toggleClass('show') //addor remove class
        $(this).attr('aria-expanded', $(this).attr('aria-expanded') == 'false' ? 'true' : 'false'); //add true or false
        $("div[aria-labelledby=" + $(this).attr('id') + "]").toggleClass('show') //add class/remove
    });

    $('body').on('click', function (e)
    {
        //check if the click occur outside `filter` tag if yes ..hide menu
        if (!$('#filter').is(e.target) &&
            $('#filter').has(e.target).length === 0 &&
            $('.show').has(e.target).length === 0
        ) {
            //remove clases and add attr
            $('#filter').removeClass('show')
            $('#filter > a').attr('aria-expanded', 'false');
            $("#filter").children('div.dropdown-menu').removeClass('show')
        }
    });

    $('.filter-group').on('click', function ()
    {
        $(this).children('i').toggleClass('mdi-chevron-right').toggleClass('mdi-chevron-down');
    })

    $("#dropzone-image").dropzone({
        url: "/file/post",
        dictDefaultMessage: "<i class='mdi mdi-cloud-upload text-primary mdi-48px'></i> <br> Drop files here or<br>click to upload...",
        addRemoveLinks: true,
        acceptedFiles: "image/*",
        uploadMultiple: false,
        maxFilesize: 1000,
        maxFiles: 1,
        parallelUploads: 1,
        thumbnailWidth: 200,
        thumbnailHeight: 200,
        maxfilesexceeded: function (file) {
            this.removeAllFiles();
            this.addFile(file);
        },
    });

    $("#dropzone-image-2").dropzone({
        url: "/file/post",
        dictDefaultMessage: "<i class='mdi mdi-cloud-upload text-primary mdi-48px'></i> <br> Drop files here or<br>click to upload...",
        addRemoveLinks: true,
        acceptedFiles: "image/*",
        uploadMultiple: false,
        maxFilesize: 1000,
        maxFiles: 1,
        parallelUploads: 1,
        thumbnailWidth: 200,
        thumbnailHeight: 200,
        maxfilesexceeded: function (file) {
            this.removeAllFiles();
            this.addFile(file);
        },
    });

    $("#dropzone-docs").dropzone({
        url: "/file/post",
        dictDefaultMessage: "<i class='mdi mdi-cloud-upload text-primary mdi-48px'></i> <br> Drop files here or<br>click to upload...",
        addRemoveLinks: true,
        acceptedFiles: ".docx,.doc",
        uploadMultiple: false,
        maxFilesize: 1000,
        maxFiles: 1,
        parallelUploads: 1,
        thumbnailWidth: 200,
        thumbnailHeight: 200,
        maxfilesexceeded: function (file) {
            this.removeAllFiles();
            this.addFile(file);
        },
    });

    $("#dropzone-pdf").dropzone({
        url: "/file/post",
        dictDefaultMessage: "<i class='mdi mdi-cloud-upload text-primary mdi-48px'></i> <br> Drop files here or<br>click to upload...",
        addRemoveLinks: true,
        acceptedFiles: ".pdf",
        uploadMultiple: false,
        maxFilesize: 1000,
        maxFiles: 1,
        parallelUploads: 1,
        thumbnailWidth: 200,
        thumbnailHeight: 200,
        maxfilesexceeded: function (file) {
            this.removeAllFiles();
            this.addFile(file);
        },
    });

    $("#dropzone-excel").dropzone({
        url: "/file/post",
        dictDefaultMessage: "<i class='mdi mdi-cloud-upload text-primary mdi-48px'></i> <br> Drop files here or<br>click to upload...",
        addRemoveLinks: true,
        acceptedFiles: ".xls,.xlsx,.csv,.ods,.ots",
        uploadMultiple: false,
        maxFilesize: 1000,
        maxFiles: 1,
        parallelUploads: 1,
        thumbnailWidth: 200,
        thumbnailHeight: 200,
        maxfilesexceeded: function (file) {
            this.removeAllFiles();
            this.addFile(file);
        },
    });

    // Image viewer

    $(".images img").on("click", function ()
    {
        $("#full-image").attr("src", $(this).attr("src"));
        $("#image-viewer").show();
    });

    $("#image-viewer .close").on("click", function ()
    {
        $("#image-viewer").hide();
    });

    // Form Validation
    // Read the documentation here https://github.com/bnabriss/jquery-form-validation

    $(document).on("blur", "[data-validator]", function ()
    {
        new Validator($(this), {
            /* your options here*/
        });
    });

    $(document).on("change", ".select-2.validate", function ()
    {
        new Validator($(this), {
            /* your options here*/
        });
    });

    $("form").on("submit", function (event)
    {
        if (form_validator($(this))) {
            return true;
        }

        event.preventDefault();
    });

    function form_validator(form)
    {
        var error_count = 0;
        var field = form.find("[data-validator]");

        field.each(function () {
            var validator = new Validator($(this), {
                /* your options here*/
            });

            if (validator.status == "error") {
                error_count++;
            }
        });

        if (error_count == 0) {
            return true;

        } else {
            return false;
        }
    }

    // Daterange picker
    var start = moment().startOf("month");
    var end = moment().endOf("month");

    function cb(start, end)
    {
        $(".daterange").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );
    }

    $(".daterange").daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                Today: [moment(), moment()],
                Yesterday: [moment().subtract(1, "days"), moment().subtract(1, "days")],
                "Last 7 Days": [moment().subtract(6, "days"), moment()],
                "Last 30 Days": [moment().subtract(29, "days"), moment()],
                "This Month": [moment().startOf("month"), moment().endOf("month")],
                "Last Month": [
                    moment().subtract(1, "month").startOf("month"),
                    moment().subtract(1, "month").endOf("month"),
                ],
            },
            alwaysShowCalendars: true,
            opens: "left",
        },
        cb
    );

    cb(start, end);
    // Daterange picker

    $(".daterangetime").daterangepicker({
        ranges: {
            Today: [moment(), moment()],
            Yesterday: [moment().subtract(1, "days"), moment().subtract(1, "days")],
            "Last 7 Days": [moment().subtract(6, "days"), moment()],
            "Last 30 Days": [moment().subtract(29, "days"), moment()],
            "This Month": [moment().startOf("month"), moment().endOf("month")],
            "Last Month": [
                moment().subtract(1, "month").startOf("month"),
                moment().subtract(1, "month").endOf("month"),
            ],
        },
        alwaysShowCalendars: true,
        opens: "left",
        timePicker: true,
        timePicker24Hour: true,
    });

    //Select2
    $(".select-2").select2({
        placeholder: "Pilih Data",
    });

    $(".select-2-allow-clear").select2({
        placeholder: "Pilih Data",
        allowClear: true,
    });

    // Show hide password
    $(".show_hide_password").each(function ()
    {
        var container = $(this);
        var link = $(this).find("a");

        link.on("click", function () {
            event.preventDefault();
            var text = $(container).find("input");
            var icon = $(container).find("i");

            if (text.attr("type") == "text") {
                text.attr("type", "password");
                icon.removeClass("mdi-eye");
                icon.addClass("mdi-eye-off");
            } else if (text.attr("type") == "password") {
                text.attr("type", "text");
                icon.removeClass("mdi-eye-off");
                icon.addClass("mdi-eye");
            }
        });
    });

    //Time Masking
    $(".time-mask").mask("00:00:00", {
        placeholder: "hh:mm:ss",
        clearIfNotMatch: true,
    });
    $(".time-mask").each(function () {
        $(this).on("keyup", function () {
            var correct = moment($(this).val(), "HH:mm:ss", true).isValid();

            if (correct == true) {
                $(this).removeClass("is-invalid");
                $(this).addClass("is-valid");
            } else {
                $(this).removeClass("is-valid");
                $(this).addClass("is-invalid");
            }
        });
        $(this).on("blur", function () {
            var correct = moment($(this).val(), "HH:mm:ss", true).isValid();

            if (correct == true) {
                $(this).removeClass("is-invalid");
                $(this).addClass("is-valid");
            } else {
                $(this).removeClass("is-valid");
                $(this).addClass("is-invalid");
                $(this).val("");
                new Validator($(this), {});
            }
        });
    });

    $(".time-mask-1").mask("00:00", {
        placeholder: "hh:mm",
        clearIfNotMatch: true,
    });
    $(".time-mask-1").each(function () {
        $(this).on("keyup", function () {
            var correct = moment($(this).val(), "HH:mm", true).isValid();

            if (correct == true) {
                $(this).removeClass("is-invalid");
                $(this).addClass("is-valid");
            } else {
                $(this).removeClass("is-valid");
                $(this).addClass("is-invalid");
            }
        });
        $(this).on("blur", function () {
            var correct = moment($(this).val(), "HH:mm", true).isValid();

            if (correct == true) {
                $(this).removeClass("is-invalid");
                $(this).addClass("is-valid");
            } else {
                $(this).removeClass("is-valid");
                $(this).addClass("is-invalid");
                $(this).val("");
                new Validator($(this), {});
            }
        });
    });

    //Rupiah Masking
    $(".rupiah").maskMoney({
        prefix: "Rp ",
        thousands: ".",
        decimal: ",",
        affixesStay: false,
        precision: 2,
    });

    //Select all
    $("#checkAll").click(function () {
        $(".check").prop("checked", $(this).prop("checked"));
    });

    //Datepicker
    $(".datepicker").daterangepicker({
        singleDatePicker: true,
    });

    $(".datetimepicker").daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        locale: {
            format: "DD/MM/YYYY HH:mm", // Format tanggal dan waktu yang diinginkan
        },
    });

    //Sifat Tunjangan
    $("#sifat-tunjangan").on("change", function () {
        var value = $(this).find(":selected").text();

        if (value == "Tetap") {
            $("#pph").hide();
            $("#upah").hide();
            $(".select-2").select2();
        } else {
            $("#pph").show();
            $("#upah").show();
            $(".select-2").select2();
        }
    });

    //Colorpicker
    $(".color-picker").colorpicker();

    // Hapus Data
    $(".dataTable").on("click", ".dropdown-item.trash", function () {
        // your code goes here
        var dataname = $(this).data("name");
        var datalink = $(this).data("link");

        $("#HapusData").modal("show");
        $("#HapusData").on("shown.bs.modal", function () {
            $("#dataname").html(dataname);
            $("#buttonlink").attr("href", datalink);
        });
    });

    // shortcut add customer
    $(".shortcut-add-customer").on("click", function () {
        $("#addCustomer").modal("show");
    });

    $("body").on("click", ".close", function () {
        $('.modal').modal('hide');
    });

    $("body").on("click", ".close-modal", function () {
        $('.modal').modal('hide');
    });

    $('.select-2').each(function () {
        $(this).select2({
            placeholder: "Pilih salah satu",
            dropdownParent: $(this).parent(),
        });
    })

    $("body").on("click", ".process_close", function () {
        $(this).closest('.process_').remove();
    });
}

function formatNumber(value, prefix = "Rp", suffix = "")
{
    var parsedVal = parseFloat(parseFloat(value).toFixed(2));

    var formattedNumber =
        prefix + " " + parsedVal.toLocaleString('id-ID');

    if (prefix == "none") {
        formattedNumber = parsedVal
            .toLocaleString('id-ID');
    }

    if (suffix) {
        formattedNumber =
            parsedVal.toLocaleString('id-ID') + " " + suffix;
    }

    return formattedNumber;
}

function unformatNumber(value)
{
    if (value) {
        var unmaskedValue = value.replace(/\./g, '');
        unmaskedValue = unmaskedValue.replace(/,/g, '.');
        return parseFloat(unmaskedValue);
    }

    return 0;
}

function validateCurrencyInput(event)
{
    var keyCode = event.keyCode || event.which;
    var keyValue = String.fromCharCode(keyCode);

    var validChars = /[0-9]|[\b]|[\t]|[\r]|[\n]|\.|,/;

    if (!validChars.test(keyValue)) {
        event.preventDefault();
    }
}

function handleTextNotAllowedPaste(event)
{
    var clipboardData = event.clipboardData || window.clipboardData;
    var pastedData = clipboardData.getData('text/plain');

    // Hapus karakter yang tidak valid dari data yang ditempelkan
    var filteredData = pastedData.replace(/[^0-9.,]+/g, '');

    // Tempatkan data yang telah difilter ke dalam input
    var inputElement = event.target;
    var currentCursorPosition = inputElement.selectionStart;
    var inputValue = inputElement.value;
    var newValue = inputValue.slice(0, currentCursorPosition) + filteredData + inputValue.slice(inputElement.selectionEnd);

    inputElement.value = "";
    inputElement.value = newValue;

    // Hentikan peristiwa paste agar konten yang tidak valid tidak ditampilkan
    event.preventDefault();
}

function convertDate(date)
{
    // Buat objek Date berdasarkan tanggal yang diberikan
    const tanggal = new Date(date);

    // Ambil nilai tanggal, bulan, tahun, jam, menit, dan detik
    const tgl = tanggal.getDate();
    const bln = tanggal.getMonth() + 1; // Ingat: indeks bulan dimulai dari 0
    const thn = tanggal.getFullYear();
    const jam = tanggal.getHours();
    const menit = tanggal.getMinutes();

    // Buat string dalam format yang diinginkan
    const stringTanggal = `${tgl.toString().padStart(2, "0")}/${bln
        .toString()
        .padStart(2, "0")}/${thn.toString()} ${jam
        .toString()
        .padStart(2, "0")}:${menit.toString().padStart(2, "0")}`;

    // Tampilkan string tanggal
    return stringTanggal;
}

function debounce(func, wait, immediate)
{
    var timeout;
    return function () {
        var context = this,
            args = arguments;
        var later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

const backgroundColor = [
    'rgba(75, 192, 192, 0.2)',
    'rgba(255, 99, 132, 0.2)',
    'rgba(255, 205, 86, 0.2)',
    'rgba(54, 69, 235, 0.2)',
    'rgba(153, 102, 255, 0.2)',
    'rgba(255, 0, 0, 0.2)',
    'rgba(0, 255, 0, 0.2)',
    'rgba(0, 0, 255, 0.2)',
    'rgba(255, 255, 0, 0.2)',
    'rgba(255, 0, 255, 0.2)',
    'rgba(0, 255, 255, 0.2)',
    'rgba(128, 0, 0, 0.2)',
    'rgba(0, 128, 0, 0.2)',
    'rgba(0, 0, 128, 0.2)',
    'rgba(128, 128, 0, 0.2)',
    'rgba(128, 0, 128, 0.2)',
    'rgba(0, 128, 128, 0.2)',
    'rgba(192, 192, 192, 0.2)',
    'rgba(128, 128, 128, 0.2)',
    'rgba(64, 64, 64, 0.2)',
    'rgba(255, 128, 0, 0.2)',
    'rgba(128, 255, 0, 0.2)',
    'rgba(0, 128, 255, 0.2)',
    'rgba(255, 0, 128, 0.2)',
    'rgba(128, 0, 255, 0.2)',
    'rgba(0, 255, 128, 0.2)',
    'rgba(192, 0, 0, 0.2)',
    'rgba(0, 192, 0, 0.2)',
    'rgba(0, 0, 192, 0.2)',
    'rgba(192, 192, 0, 0.2)',
    'rgba(192, 0, 192, 0.2)',
    'rgba(0, 192, 192, 0.2)',
    'rgba(255, 128, 128, 0.2)',
    'rgba(128, 255, 128, 0.2)',
];

function skeletonLoader(element, active = false)
{
    if (active) {
        if ($(`#${element}`).hasClass('skeleton-loader-v2')) {
            $(`#${element}`).append(`<span class="skeleton-loader-custom"></span>`);
        } else {
            $(`#${element} .skeleton-loader-v2`).each(function () {
                const $element = $(this);
                $element.append(`<span class="skeleton-loader-custom"></span>`);
            });
        }
    } else {
        $(`#${element} .skeleton-loader-custom`).each(function () {
            const $element = $(this);
            $element.remove();
        });
    }
}

function capitalizeFirstLetter(string)
{
    return string.toLowerCase().replace(/\b\w/g, function (char) {
        return char.toUpperCase();
    });
}
