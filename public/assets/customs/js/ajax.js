var fail = '<span class="ec ec-construction"></span> ';
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
    }
});

function kode_generator(urlx, idf) {
    $.ajax({
        url: urlx,
        type: 'ajax',
        dataType: 'json',
        async: false,
        method: "POST",
        success: function (data) {
            $('#' + idf).val(data);
        }
    })
}

function select_data(id_field, urlx, table, column, name) {
    var datax = {
        table: table,
        column: column,
        name: name
    };
    getSelect2(urlx, id_field, datax);
}

function getAjaxData(urlx, where, btn = false, pace = true) {
    if (pace) {
        // Pace.restart();
    }
    if (btn) {
        $(btn).append(' <div id="loader_button spinner-border spinner-border-sm text-light" role="status"><span class="sr-only">Loading...</span></div>');
    }
    var viewx;
    $.ajax({
        url: urlx,
        method: "POST",
        data: where,
        async: false,
        dataType: 'json',
        success: function (data) {
            viewx = data;
            $('#loader_button').remove();
            // console.clear();
        },
        error: function (data) {
            swalCall('error');
        }
    });
    return viewx;
}

function submitAjax(urlx, modalx, formx, usage, progress = false, btn) {
    // Pace.restart();
    if (btn) {
        $('#' + btn).attr('disabled', 'disabled');
        $('#' + btn).append(' <div id="loader_button" class="spinner-border spinner-border-sm text-light" role="status"><span class="sr-only">Loading...</span></div>');
    }

    if (usage == 'status' || usage == 'change_skin') {
        var data = formx;
    } else {
        var data = $('#' + formx).serialize();
    }
    $.ajax({
        url: urlx,
        method: "POST",
        data: data,
        async: false,
        dataType: 'json',
        success: function (data) {
            if (usage == 'auth') {
                if (data.status_data == 'wrong') {
                    $('#forget_pass').html('<a href="auth/lupa">Lupa Password?</a>');
                    // get_captcha();
                }
                if (data.status_data == false) {
                    // get_captcha();
                }
            } else {
                if (data.status_data == true) {
                    $('#' + modalx).modal('toggle');
                }
            }
            if (!$.isEmptyObject(data.validate)) {
                $('#' + btn).removeAttr('disabled', 'disabled');
            } else {
                if (usage != 'status' && usage != 'change_skin') {
                    $(".print-error-" + formx).css('display', 'none');
                }
            }
            if (data.status_data == true) {
                swalCall('success', data.msg);
            } else if (data.status_data == 'warning') {
                swalCall('warning', data.msg);
            } else if (data.validate) {
                swalCall('warning', data.msg);
            } else {
                if (data.msg != null) {
                    swalCall('error', data.msg);
                } else {
                    swalCall('error');
                }
            }
            if (data.linkx != null) {
                window.location.href = data.linkx;
            }
            if (data.access_info != null) {
                storeToMemory('remember', JSON.stringify(data.access_info));
            }
            if (usage == 'wload') {
                $('#loading_progress').modal('hide');
            }
            if (progress) {
                $(progress).html('');
            }
            if (btn) {
                $('#' + btn).removeAttr('disabled', 'disabled');
                $('#loader_button').remove();
            }
            if (data.validate) {
                $.each(data.validate, function (indx, val) {
                    if (val[0]) {
                        var element = $('#' + formx).find('[name="' + indx + '"]');
                        if(!element.is('select')){
                            element.addClass('is-invalid').next().addClass('invalid-feedback').html('<ul class="list-unstyled"><li>' + val[0] + '</li></ul>');
                        }else{
                            element.parent().addClass('has-error');
                            element.parent().find('.form-control-feedback').addClass('invalid-feedback').html('<ul class="list-unstyled"><li>' + val[0] + '</li></ul>').css('display', 'block');
                        }
                    }
                })
            }
            // console.clear();
        },
        error: function (data) {
            swalCall('error');
            if (btn) {
                $('#' + btn).removeAttr('disabled', 'disabled');
                $('#loader_button').remove();
            }
        }
    });
}

function loadModalAjax(urlx, modalx, datax, usage) {
    // Pace.restart();
    $.ajax({
        url: urlx,
        method: "POST",
        data: datax,
        async: false,
        dataType: 'json',
        success: function (data) {
            if (usage == 'delete') {
                $('#' + modalx).html(data.modal);
                $('#delete').modal('show');
                $('#data_name_delete').html(datax['nama']);
                $('#data_column_delete').val(datax['column']);
                $('#data_id_delete').val(datax['id']);
                $('#data_classes_delete').val(datax['classes']);
                $('#data_table_delete').val(datax['table']);
                $('#data_table_drop').val(datax['nama_tabel']);
                $('#data_link_table').val(datax['del_link_tb']);
                $('#data_link_col').val(datax['del_link_col']);
                $('#data_link_data_col').val(datax['del_link_data_col']);
            } else {
                swalCall('error');
            }
            // console.clear();
        },
        error: function (data) {
            swalCall('error');
        }
    });
}
function loadMenuAjax(urlx,datax, id) {
    // Pace.restart();
    $.ajax({
        url: urlx,
        method: "POST",
        data: datax,
        async: false,
        dataType: 'json',
        success: function (data) {
            $('#'+id).html(data);
        },
        error: function (data) {
            swalCall('error');
        }
    });
}
function getAjaxCount(urlx, datax, sendx) {
    // not with php
    var nomor = datax;
    var total = parseInt(0);
    for (i = 0; i < nomor.length; i++) {
        if (nomor[i] == '') {
            total += parseInt(0);
        } else {
            total += parseInt(nomor[i]);
        }
    }
    $('#' + sendx).val(total);
    // end
}

function getSelect2(urlx, formx, datax) {
    $.ajax({
        method: "POST",
        url: urlx,
        data: datax,
        async: false,
        dataType: 'json',
        success: function (data) {
            var html = '<option></option>';
            $.each(data, function (key, value) {
                html += '<option value="' + key + '">' + value + '</option>';
            });
            $('#' + formx).html(html);
        },
        error: function (data) {
            swalCall('error');
        }
    });
}

function realtimeAjax(urlx, id) {
    // Pace.ignore(function () {
        setInterval(function () {
            $.ajax({
                type: 'ajax',
                url: urlx,
                async: false,
                dataType: 'json',
                success: function (data) {
                    $('#show_notif').html(data);
                }

            });
        }, 5000);
    // });
}

function submitAjaxFile(urlx, datax, modalx, progx, btnx) {
    $.ajax({
        url: urlx,
        type: "post",
        data: datax,
        processData: false,
        contentType: false,
        cache: false,
        async: false,
        dataType: 'json',
        success: function (data) {
            $(progx).hide();
            $(btnx).removeAttr('disabled');

            if (data.status_data == true) {
                swalCall('success', data.msg);
            } else if (data.status_data == 'warning') {
                swalCall('warning', data.msg);
            } else {
                if (data.msg != null) {
                    swalCall('error', data.msg);
                } else {
                    swalCall('error');
                }
            }
            setTimeout(function () {
                $(modalx).modal('toggle');
            }, 1000);
            $('#table_data').DataTable().ajax.reload(function () {
                // Pace.restart();
            });
        },
        error: function (data) {
            swalCall('error');
        }
    });
}

function storeToMemory(name, val) {
    if (typeof (Storage) !== 'undefined') {
        localStorage.setItem(name, val);
    } else {
        window.alert('Gunakan Browser terbaru untuk menampilkan tema template!');
    }
}

function removeFromMemory(name) {
    if (localStorage.getItem(name) != null) {
        localStorage.clear(name);
    }
}
