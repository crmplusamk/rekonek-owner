<nav class="t-header border-bottom">
    <div class="t-header-brand-wrapper border-bottom">
        <a href="">
            <img class="logo" style="width: 150px;" src="{{ URL::to('assets/images/logo.png') }}" alt="">
        </a>
    </div>
    <div class="t-header-content-wrapper">
        <div class="t-header-content">
            <button class="t-header-toggler t-header-mobile-toggler d-block">
                <i class="mdi mdi-menu"></i>
            </button>
            <a class="brand-logo" href="">
                <img class="logo" src="{{ URL::to('assets/images/logo.png') }}" alt="">
            </a>
            <ul class="nav ml-auto">
                <li class="nav-item dropdown" style="margin-right: -15px;">
                    <a class="nav-link" href="#" id="notificationDropdown" data-toggle="dropdown"
                        aria-expanded="false">
                        <i class="mdi mdi-bell-outline mdi-24px"></i>
                        <span class="notification-indicator notification-indicator-danger counter-notification">0</span>
                    </a>
                    <div class="dropdown-menu navbar-dropdown dropdown-menu-right" aria-labelledby="notificationDropdown">
                        <div class="dropdown-header bg-light">
                            <h6 class="dropdown-title">Notifikasi</h6>
                            <div>
                                <p class="dropdown-title-text">Anda mempunyai <span class="counter-notification">0</span> Notifikasi</p>
                                <p class="dropdown-title"><a href="#" id="read-all-notification-bell-btn">Tandai Semua Dibaca</a></p>
                            </div>
                        </div>
                        <div style="tools-box" style="max-height: 500px">
                            <div class="dropdown-body" id="my-notifications"></div>
                        </div>
                        <div class="dropdown-footer">
                            <a href="">Lihat Semua Notifikasi</a>
                        </div>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="profileDropdown" data-toggle="dropdown"
                        aria-expanded="false">
                        <div class="d-flex">
                            <img src="{{ asset('assets/images/profile/male/image_7.png') }}" class="img-user mr-2">
                            <div class="user-profile-nav">
                                <p>{{ Auth::user()->name }} <i class="mdi mdi-chevron-down"></i></p>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu navbar-dropdown dropdown-menu-right" aria-labelledby="profileDropdown">
                        <div class="dropdown-body">
                            <a href="" class="dropdown-list">
                                <i class="mdi mdi-account mdi-1x mr-2"></i>Profile
                            </a>
                            <a href="" class="dropdown-list">
                                <i class="mdi mdi-lock mdi-1x mr-2"></i>Ubah Password
                            </a>
                        </div>
                        <div class="dropdown-footer p-3">
                            <form method="post" action="{{ route('auth.logout') }}">
                                @csrf
                                @method('post')
                                <button type="submit" class="btn btn-primary btn-block">Keluar</button>
                            </form>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
