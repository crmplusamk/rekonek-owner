@props(['id', 'title' => '', 'size' => ''])

<div class="modal fade rk-modal" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $size ? 'modal-' . $size : '' }}" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ $title }}</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="modal-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
