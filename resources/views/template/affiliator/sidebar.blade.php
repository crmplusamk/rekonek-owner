<div class="sidebar">
    <ul class="nav flex-column navigation-menu">
        <li class="nav-item active">
            <a href="{{ route('affiliator.index') }}" class="{{ request()->segment(1) == 'affiliator' ? 'active' : '' }}">
                <span class="link-title">Dashboard</span>
                <i class="mdi mdi-home link-icon" data-toggle="tooltip" data-placement="right" title="Dashboard"></i>
            </a>
        </li>
    </ul>
</div>
