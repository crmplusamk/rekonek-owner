<a href="#" data-toggle="dropdown" class="text-muted" aria-expanded="false">
    <i class="mdi mdi-dots-vertical mdi-24px"></i>
</a>
<div class="dropdown-menu dropdown-menu-right" role="menu">
    <a class="dropdown-item" href="{{ route('announcement.edit', $announcement->id) }}">
        <i class="mdi mdi-pencil"></i> Edit
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#destroy-{{ $announcement->id }}">
        <i class="mdi mdi-delete"></i> Hapus
    </a>
</div>

<div class="modal fade" id="destroy-{{ $announcement->id }}" aria-labelledby="destroy" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi Hapus Data</h5>
                    <p class="mt-2 text-lg">Apakah anda yakin ingin menghapus pengumuman {{ $announcement->title }} ? </p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">Batal</a>
                    <form action="{{ route('announcement.destroy', $announcement->id) }}" method="post">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-primary">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
