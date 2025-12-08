<span class="badge badge-pill badge-{{ $otp->status ? 'success' : 'danger' }}">
    {{ $otp->status ? 'Used' : 'Not Used' }}
</span>

