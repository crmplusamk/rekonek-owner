<script src="{{ asset('assets/vendors/popper/popper.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/core.js') }}"></script>
<script src="{{ asset('assets/vendors/datatable/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendors/datatable/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('assets/vendors/datatable/dataTables.fixedColumns.min.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.rowGroup.min.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.select.min.js') }}"></script>
<script src="{{ asset('assets/js/template.js') }}"></script>
<script src="{{ asset('assets/vendors/mask-money/jquery.maskMoney.min.js') }}"></script>
<script src="{{ asset('assets/vendors/dropzone/dropzone-min.js') }}"></script>
<script src="{{ asset('assets/vendors/form-validation/jquery.form-validation.min.js') }}"></script>
<script src="{{ asset('assets/vendors/moment/moment.min.js') }}"></script>
<script src="{{ asset('assets/vendors/moment/moment.locale.min.js') }}"></script>
<script src="{{ asset('assets/vendors/daterangepicker/daterangepicker.min.js') }}"></script>
<script src="{{ asset('assets/vendors/select2/select2.min.js') }}"></script>
<script src="{{ asset('assets/vendors/jquery-mask/jquery.mask.min.js') }}"></script>
<script src="{{ asset('assets/vendors/colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
<script src="{{ asset('assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('assets/vendors/treeview/jstree.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/region.js') }}"></script>
<script src="{{ asset('assets/js/message.js') }}"></script>
<script src="{{ asset('assets/js/custom.js') }}"></script>

<script src="{{ asset('assets/js/pusher.min.js') }}"></script>
<script src="{{ asset('assets/js/emojionearea.min.js') }}"></script>

<script>

    Pusher.logToConsole = true;

    const connection = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
        cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
        encrypted: true,
        wsHost: '{{ config("broadcasting.connections.pusher.options.host") }}',
        wsPort: '{{ config("broadcasting.connections.pusher.options.port") }}',
        wssPort: '{{ config("broadcasting.connections.pusher.options.port") }}',
        enabledTransports: ['ws'],
        forceTLS: '{{ config("broadcasting.connections.pusher.options.scheme") }}' == 'https' ? true : false,
        channelAuthorization: {
            endpoint: "/broadcasting/auth",
        },
    });

</script>
