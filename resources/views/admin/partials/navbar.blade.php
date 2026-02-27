<nav class="navbar navbar-expand-lg custom-navbar px-4">


    <div class="container-fluid d-flex justify-content-between align-items-center">

        <!-- LEFT SIDE -->
        <div class="d-flex align-items-center gap-3">

            <!-- Sidebar Toggle -->
            <button class="btn toggle-btn d-md-none sidebar-toggle">
                <i class="bi bi-list"></i>
            </button>

            <!-- Page Title -->
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('images/dashboard.png') }}" alt="Dashboard" class="dashboard-title-icon">
                <span class="fw-semibold text-white fs-5">Dashboard</span>
            </div>

        </div>

        <!-- RIGHT SIDE -->
        <div class="d-flex align-items-center gap-4">

            <!-- Search Box -->
            <div class="search-box d-none d-md-block">
                <input type="text" class="form-control" placeholder="Search...">
            </div>

            <!-- Notification -->
            <div class="position-relative">
                <i class="bi bi-bell text-white fs-5"></i>
                <span class="notify-badge">3</span>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a class="d-flex align-items-center gap-2 text-white text-decoration-none dropdown-toggle"
                    href="#" role="button" data-bs-toggle="dropdown">

                    <div class="user-avatar">
                        <img src="{{ asset('admin/dist/assets/images/logo/userlogo.webp') }}" alt="User"
                            class="user-avatar-img">
                    </div>

                    <span class="fw-semibold">
                        {{ auth()->user()->name ?? 'Admin' }}
                    </span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

        </div>

    </div>

</nav>
