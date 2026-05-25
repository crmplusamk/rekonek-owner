<span>
    {{ optional($announcement->start_at)->format('d-m-Y') }} - {{ $announcement->end_at ? $announcement->end_at->format('d-m-Y') : '-' }}
</span>
