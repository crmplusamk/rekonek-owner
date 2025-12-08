<nav class="nav nav-pills border-bottom mb-4">
    <a href="{{ route('user.index') }}" class="nav-link {{ request()->segment(1) == 'user' ? 'active' : '' }}" style="font-size: 13px;">
        User
    </a>
    <a href="{{ route('role.index') }}" class="nav-link {{ request()->segment(1) == 'role' ? 'active' : '' }}" style="font-size: 13px;">
        Role
    </a>
    <a href="{{ route('developer-access.index') }}" class="nav-link {{ request()->segment(1) == 'developer-access' ? 'active' : '' }}" style="font-size: 13px;">
        Dev Akses
    </a>
    <a href="{{ route('verification.index') }}" class="nav-link {{ request()->segment(1) == 'verification' ? 'active' : '' }}" style="font-size: 13px;">
        OTP Sender
    </a>
    <a href="{{ route('otphistory.index') }}" class="nav-link {{ request()->segment(1) == 'otphistory' ? 'active' : '' }}" style="font-size: 13px;">
        OTP History
    </a>
    <a href="{{ route('delete-company-account.index') }}" class="nav-link {{ request()->segment(1) == 'delete-company-account' ? 'active' : '' }}" style="font-size: 13px;">
        Request Deletion
    </a>
</nav>
