const Toast = Swal.mixin({
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 5000
});

function swalCall(params, messages = 'Invalid Parameter') {
    //params is success,info,error,warning
    if (params != 'success') {
        Toast.fire({
            type: params,
            title: ' ' + messages,
            showCancelButton: true,
            timer: 10000
        })
    }else{
        Toast.fire({
            type: params,
            title: ' ' + messages
        })
    }

}

function form_key(formx, btn) {
    $('#' + formx + ' input').keydown(function (e) {
        if (e.keyCode == 13 || e.which == 13) {
            $('#' + btn).click();
        }
    });
}

function printErrorMsg(msg, id_form) {
    $(".print-error-" + id_form).find("ul").html('');
    $(".print-error-" + id_form).css('display', 'block');
    $.each(msg, function (key, value) {
        $(".print-error-" + id_form).find("ul").append('<li>' + value + '</li>');
    });
}

function form_config() {
    form_key('form_login', 'btn_login');
    form_key('form_add', 'btn_add');
    form_key('form_edit', 'btn_edit');
    // $('form').find('.form-control').after('<div class="form-control-feedback"></div>');
    // $('form').find('.input-group-append').after('<div class="form-control-feedback"></div>');
    // $('form').find('.form-group').addClass('has-feedback');
    // $('form').addClass('needs-validation');
    // (function () {
    //     'use strict';
    //     window.addEventListener('load', function () {
    //         // Fetch all the forms we want to apply custom Bootstrap validation styles to
    //         var forms = document.getElementsByClassName('needs-validation');
    //         // Loop over them and prevent submission
    //         var validation = Array.prototype.filter.call(forms, function (form) {
    //             form.addEventListener('click', function (event) {
    //                 if (form.checkValidity() === false) {
    //                     event.preventDefault();
    //                     event.stopPropagation();
    //                 }
    //                 form.classList.add('was-validated');
    //             }, false);
    //         });
    //     }, false);
    // })();
}

function load_menu(url_menu) {
    var callback = getAjaxData(url_menu, null, false, false);
    $('#load_menu').html(callback);
}
function load_settings(url_menu, uri) {
    var callback = getAjaxData(url_menu, { uri: uri }, false, false);
    $('#load_settings').html(callback);
}
function do_logout() {
    removeFromMemory('remember');
}
function storeToMemory(name, val) {
    if (typeof (Storage) !== 'undefined') {
        localStorage.setItem(name, val)
    } else {
        window.alert('Gunakan Browser terbaru untuk menampilkan tema template!')
    }
}
function removeFromMemory(name) {
    if (localStorage.getItem(name) != null) {
        localStorage.clear(name);
    }
}
var skin = [
    'skin-blue',
    'dark-mode',
]
var navbar = [
    'navbar-dark',
    'navbar-light',
    'navbar-white',
]
function changeTheme(urlx, idx, skinx) {
    var cls = $('#skinX').data('skin');
    if (cls == null) {
        cls = 'skin-blue';
    }
    $.each(navbar, function (j) {
        $('nav.navbar').removeClass(navbar[j])
    })
    $.each(skin, function (i) {
        $('body').removeClass(skin[i])
    })
    var nav = null;
    if (cls == 'dark-mode') {
        $('#skinX').data('skin', 'skin-blue');
        $('#skinX').html('<i class="fas fa-moon"></i>');
        nav = 'navbar-dark';
    } else {
        $('#skinX').data('skin', 'dark-mode');
        $('#skinX').html('<i class="fas fa-sun"></i>');
        nav = 'navbar-light';
    }
    var datax = {
        id: idx,
        skin: skinx
    };
    $('body').addClass(cls);
    $('nav').addClass(nav);
    storeToMemory('skin', cls);
    submitAjax(urlx, null, datax, "change_skin");
}


if ($.isFunction($.fn.dataTable)) {
    $.extend(true, $.fn.dataTable.defaults, {
        bDestroy: true,
        processing: true,
        deferender: true,
        "initComplete": function (settings, json) {
            $(this).wrap("<div style='overflow:auto; width:100%;'></div>");
        }
    });
}
