@if($customer->deleted_at)
    <span class="badge badge-pill badge-danger">Deleted</span>
@else
    <span class="badge badge-pill badge-{{ $customer->is_active ? 'success' : 'danger' }}">{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</span>
@endif
