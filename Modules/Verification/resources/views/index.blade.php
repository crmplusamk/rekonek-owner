@extends('template.admin.main')

@section('content')
@include('tabs.setting')

<div class="d-flex justify-content-end">
    <a class="btn btn-primary mr-2" id="addwhatsappotpnumber">
        Tambah Sender
    </a>
</div>

<div class="modal fade" id="whatsappOtpQrCode" aria-labelledby="whatsappOtpQrCode" tabindex="-1" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Whatsapp Authentication </h5>
            </div>
            <div class="modal-body text-center">
                <div class="p-3">
                    <div class="d-flex justify-content-center align-content-center">
                        <div class="card border bg-light" style="height: 260px; width: 260px">
                            <img id="otpQrCode" src="" alt="">
                        </div>
                    </div>
                    <div class="mt-5">
                        <h6 class="fw-bold"> WhatsApp's QR Code Scanner </h6>
                        <h6 class="mt-1"> Whatsapp > Pengaturan > Hubungkan Perangkat </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 pt-1">
    <div class="border-1">
        <div class="p-3 border-bottom">
            <div class="row flex-column-reverse flex-md-row">
                <div class="col-md-3 mb-2">
                    <input type="text" id="search" class="form-control" placeholder="Pencarian">
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control select-2" id="showCount">
                        <option value="10" selected>Tampil 10 data</option>
                        <option value="25">Tampil 25 data</option>
                        <option value="50">Tampil 50 data</option>
                        <option value="100">Tampil 100 data</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2"></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table otpsender-table" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center">Nomor</th>
                        <th class="text-center">Session</th>
                        <th class="text-center">Status</th>
                        <th data-orderable="false"></th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>

    const user = @json(Auth::user());

    let otptable;
    let searchOtp = '';

    let otpsendertable;
    let search = '';

    $(document).ready(function ()
    {
        otpsenderTable();
        subscribeToPrivateChannel();
    });

    $('body').on('click', '#addwhatsappotpnumber', async function(e)
    {
        try {

            const response = await createSession();
            showMessage('success', 'Sedang menunggu qr code');
            $('#whatsappOtpQrCode').modal('show');
            $('#otpQrCode').attr('src', response.data.qrcode.base64);

        } catch (e) {

            showMessage('error', 'Gagal membuat qrcode, silahkan coba lagi.');
            console.error(e);
        }
    });

    function createSession()
    {
        return new Promise((resolve, reject) => {
            $.ajax({
                url  : '{{ route("verification.create.session") }}',
                type : 'GET',
                beforeSend: () => {
                    $('#loader').css('display', 'block');
                },
                complete: () => {
                    $('#loader').css('display', 'none');
                },
                success: function(response) {
                    resolve(response);
                },
                error: function(err) {
                    reject(err);
                }
            });
        });
    }

    function subscribeToPrivateChannel()
    {
        connection.subscribe(`private-notification.${user.id}`).bind(`App\\Events\\PrivateNotificationEvent`, (content) =>
        {
            if (content.data.context == 'whatsapp-otp') {
                waOtpSocketEvent(content);
            }
        });
    }

    function waOtpSocketEvent(content)
    {
        console.log(content);

        if (content.data.process == 'qr-code')
        {
            $('#otpQrCode').attr('src', content.data.data);
        }

        if (content.data.process == 'whatsapp-open')
        {
            $('#whatsappOtpQrCode').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();

            otpsenderTable();
            showMessage('success', 'Whatsapp telah terhubung.');
        }

        if (content.data.process == 'whatsapp-close')
        {
            otpsenderTable();
            showMessage('success', 'Whatsapp telah terputus.');
        }

        // if (content.data.process == 'session-update')
        // {
        //     $('#whatsappOtpQrCode').modal('hide');
        //     $('body').removeClass('modal-open');
        //     $('.modal-backdrop').remove();

        //     showMessage('success', 'Nomor berhasil terhubung.');
        //     otpsenderTable();
        // }

        // if (content.data.process == "session-duplicate")
        // {
        //     showMessage('error', 'Nomor sudah terhubung. Tidak bisa scan qr dengan nomor whatsapp yang sama.');
        //     otpsenderTable();
        // }

        // if (content.data.process == "session-update-old")
        // {
        //     $('#whatsappOtpQrCode').modal('hide');
        //     $('body').removeClass('modal-open');
        //     $('.modal-backdrop').remove();

        //     showMessage('success', 'Nomor telah diperbaharui dan terhubung.');
        //     otpsenderTable();
        // }

        // if (content.data.process == "session-logout") {
        //     showMessage('success', 'Nomor telah dilogout.');
        //     otpsenderTable();
        // }

    }

    function otpsenderTable()
    {
        if ($.fn.DataTable.isDataTable('.otpsender-table')) {
            $('.otpsender-table').DataTable().destroy();
            otpsendertable = '';
        }

        otpsendertable = $('.otpsender-table').DataTable({
            destroy: true,
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('verification.otpsender.table') }}",
                dataType: 'json',
                data: function(d) {
                    d.search = search;
                },
            },
            columns: [
                {
                    data: "number",
                },
                {
                    data: "session",
                    sortable: false,
                },
                {
                    data: "status",
                    sortable: false,
                },
                {
                    data: "action",
                    sortable: false,
                },
            ],
            columnDefs: [
                {
                    className: 'dt-center',
                    targets: [0, 1, 2, 3]
                }
            ],
            dom: 'lrtip',
            order: [
                [0, 'asc']
            ],
            length: 10,
            lengthChange: false,
            rowCallback: function(row, data) {
                $(row).attr('id', 'row_' + data.id);
            }
        });
    }

    $('#search').on('keyup', debounce(function() {
        search = this.value;
        otpsendertable.ajax.reload();
    }, 500));

</script>
@endpush
