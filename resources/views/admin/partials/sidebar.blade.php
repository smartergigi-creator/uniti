<div id="sidebar" class="sidebar active">


    <div class="sidebar-wrapper">


        <!-- Header -->
        <div class="sidebar-header">

            <div class="d-flex justify-content-between align-items-center">

                <!-- Logo -->
                <div class="logo">
                    <img style="width:80px; height:auto;" src="{{ asset('images/logo.png') }}">
                    <span class="brand-text">UNITI</span>
                </div>

                <!-- Mobile Close Button -->
                <button class="btn btn-sm btn-light d-md-none sidebar-toggle" type="button" aria-label="Close sidebar">
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- Desktop Toggle Button -->
                <button class="btn btn-sm btn-info d-none d-md-inline-flex sidebar-toggle-desktop" type="button"
                    aria-label="Toggle sidebar collapse">
                    <i class="bi bi-list"></i>
                </button>


            </div>

        </div>

        <div class="sidebar-logo-separator"></div>


        <!-- Menu -->
        <div class="sidebar-menu">

            <ul class="menu">

                <li class="sidebar-title">Menu</li>


                @auth

                    <li class="sidebar-item {{ request()->is('home') ? 'active' : '' }}">
                        <a href="{{ url('/home') }}" class="sidebar-link">
                            <i class="bi bi-house-door"></i>
                            <span>Home</span>
                        </a>
                    </li>

                    @if (auth()->user()->role === 'admin')
                        <li class="sidebar-item {{ request()->is('admin/dashboard*') ? 'active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->role === 'admin')
                        <li class="sidebar-item {{ request()->is('admin/categories*') ? 'active' : '' }}">
                            <a href="{{ route('admin.categories') }}" class="sidebar-link">
                                <i class="bi bi-tags"></i>
                                <span>Category</span>
                            </a>
                        </li>
                    @endif

                @endauth

            </ul>

        </div>

    </div>

</div>
