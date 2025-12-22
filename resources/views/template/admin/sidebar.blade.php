<div class="sidebar">
    <ul class="nav flex-column navigation-menu">
        <li class="nav-item active">
            <a href="{{ route('dashboard.index') }}" class="{{ request()->segment(1) == 'dashboard' ? 'active' : '' }}">
                <span class="link-title">Dashboard</span>
                <i class="mdi mdi-home link-icon" data-toggle="tooltip" data-placement="right" title="Dashboard"></i>
            </a>
        </li>

        <li class="nav-item dropdown {{ request()->segment(1) == 'customer' ? 'active' : '' }}">
            <a class="nav-link dropdown-toggle {{ request()->segment(1) == 'customer' ? 'active' : '' }}" data-toggle="dropdown" href="#" aria-expanded="false">
                <span class="link-title">Contact</span>
                <i class="mdi mdi-account-box-outline link-icon"></i>
            </a>
            <div class="dropdown-menu">
                <p class="menu-header">Contact</p>
                <a class="dropdown-item" href="">Lead</a>
                <a class="dropdown-item" href="{{ route('customer.index') }}">Customer</a>
                <a class="dropdown-item" href="">Setting</a>
            </div>
        </li>

        <li class="nav-item">
            <a href="">
                <span class="link-title">Chats</span>
                <i class="mdi mdi-message-text link-icon" data-toggle="tooltip" data-placement="right" title="Chats"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="">
                <span class="link-title">Task</span>
                <i class="mdi mdi-format-list-checks link-icon" data-toggle="tooltip" data-placement="right" title="Task"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="">
                <span class="link-title">Invoice</span>
                <i class="mdi mdi-cart-outline link-icon" data-toggle="tooltip" data-placement="right" title="Invoice"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->segment(1) == 'subscription' ? 'active' : '' }}">
            <a href="{{ route('subscription.index') }}" class="{{ request()->segment(1) == 'subscription' ? 'active' : '' }}">
                <span class="link-title">Subsription</span>
                <i class="mdi mdi-coin link-icon" data-toggle="tooltip" data-placement="right" title="Subsription"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->segment(1) == 'package' ? 'active' : '' }}">
            <a href="{{ route('package.index') }}" class="{{ request()->segment(1) == 'package' ? 'active' : '' }}">
                <span class="link-title">Package</span>
                <i class="mdi mdi-cube link-icon" data-toggle="tooltip" data-placement="right" title="Package"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->segment(1) == 'addon' ? 'active' : '' }}">
            <a href="{{ route('addon.index') }}" class="{{ request()->segment(1) == 'addon' ? 'active' : '' }}">
                <span class="link-title">Add On</span>
                <i class="mdi mdi-puzzle link-icon" data-toggle="tooltip" data-placement="right" title="addon"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->segment(1) == 'referral' ? 'active' : '' }}">
            <a href="{{ route('referral.index') }}" class="{{ request()->segment(1) == 'referral' ? 'active' : '' }}">
                <span class="link-title">Referral</span>
                <i class="mdi mdi-gift link-icon" data-toggle="tooltip" data-placement="right" title="Referral Code"></i>
            </a>
        </li>

        <li class="nav-item dropdown {{ request()->segment(1) == 'feature' || request()->segment(1) == 'feature-category' ? 'active' : '' }}">
            <a class="nav-link dropdown-toggle {{ request()->segment(1) == 'feature' || request()->segment(1) == 'feature-category' ? 'active' : '' }}" data-toggle="dropdown" href="#" aria-expanded="false">
                <span class="link-title">Feature</span>
                <i class="mdi mdi-widgets link-icon"></i>
            </a>
            <div class="dropdown-menu">
                <p class="menu-header">Feature</p>
                <a class="dropdown-item" href="{{ route('feature.category.index') }}">Kategori</a>
                <a class="dropdown-item" href="{{ route('feature.index') }}">Feature</a>
            </div>
        </li>

        <li class="nav-item dropdown w-80px {{ request()->segment(1) == 'user' || request()->segment(1) == 'verification' || request()->segment(1) == 'role' ? 'active' : '' }}">
            <a class="nav-link dropdown-toggle {{ request()->segment(1) == 'user' || request()->segment(1) == 'verification' || request()->segment(1) == 'role' ? 'active' : '' }}" data-toggle="dropdown" href="#" aria-expanded="false">
                <span class="link-title">Setting</span>
                <i class="mdi mdi-settings link-icon"></i>
            </a>
            <div class="dropdown-menu" style="width: 200px">
                <p class="menu-header">Setting</p>
                <a class="dropdown-item" href="{{ route('user.index') }}">User</a>
                <a class="dropdown-item" href="{{ route('role.index') }}">Role</a>
                <a class="dropdown-item" href="{{ route('developer-access.index') }}">Dev Akses</a>
                <a class="dropdown-item" href="{{ route('verification.index') }}">OTP Sender</a>
                <a class="dropdown-item" href="{{ route('otphistory.index') }}">OTP History</a>
            </div>
        </li>
    </ul>
</div>
