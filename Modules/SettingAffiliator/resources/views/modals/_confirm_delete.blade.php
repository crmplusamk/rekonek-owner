<div class="modal fade" id="destroy-{{ $user->id }}" tabindex="-1" aria-labelledby="destroy-{{ $user->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi Hapus Data</h5>
                    <p class="mt-2 text-lg">Apakah Anda yakin ingin menghapus affiliator {{ $user->name }}?</p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">Batal</a>
                    <form action="{{ route('setting-affiliator.destroy', $user->id) }}" method="post" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
