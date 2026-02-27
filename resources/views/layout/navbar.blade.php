<nav class="navbar navbar-expand-lg nova-navbar fixed-top">


    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">

            <img src="{{ asset('images/logo.png') }}" width="60" alt="">



            <span class="brand-text">UNITI</span>
        </a>

        <!-- Toggle (Mobile) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            @php
                $homeUrl = url('/home');
                $homeSectionUrl = url('/home#ebooksSection');
                $menuCategories = isset($categories)
                    ? $categories
                    : \App\Models\Category::whereNull('parent_id')
                        ->with([
                            'children' => fn($query) => $query->orderBy('name'),
                            'children.children' => fn($query) => $query->orderBy('name'),
                        ])
                        ->orderBy('name')
                        ->get();
            @endphp

            <ul class="navbar-nav ms-4 align-items-center">


                <li class="nav-item">
                    <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="{{ $homeUrl }}">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ $homeSectionUrl }}">Ebooks</a>
                </li>

                @foreach ($menuCategories as $menuCategory)
                    @if ($menuCategory->children->isEmpty())
                        <li class="nav-item">
                            <a class="nav-link"
                                href="{{ $homeUrl }}?category={{ $menuCategory->id }}#ebooksSection">{{ $menuCategory->name }}</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                {{ $menuCategory->name }}
                            </a>

                            <ul
                                class="dropdown-menu {{ $menuCategory->children->count() > 10 ? 'dropdown-menu-columns' : '' }}">
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ $homeUrl }}?category={{ $menuCategory->id }}#ebooksSection">
                                        All {{ $menuCategory->name }}
                                    </a>
                                </li>

                                @foreach ($menuCategory->children as $menuSubcategory)
                                    @if ($menuSubcategory->children->isEmpty())
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}#ebooksSection">
                                                {{ $menuSubcategory->name }}
                                            </a>
                                        </li>
                                    @else
                                        <li class="dropdown-submenu position-relative">
                                            <a class="dropdown-item dropdown-toggle"
                                                href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}#ebooksSection">
                                                {{ $menuSubcategory->name }}
                                            </a>
                                            <ul class="dropdown-menu position-absolute start-100 top-0">
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}#ebooksSection">
                                                        All {{ $menuSubcategory->name }}
                                                    </a>
                                                </li>
                                                @foreach ($menuSubcategory->children as $menuRelatedSubcategory)
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}&related_subcategory={{ $menuRelatedSubcategory->id }}#ebooksSection">
                                                            {{ $menuRelatedSubcategory->name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach




            </ul>

        </div>

        <!-- Right Side -->
        <div class="d-flex align-items-center ms-lg-3">
            @auth
                <div class="dropdown user-menu-dropdown">
                    <button class="btn user-menu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <span class="user-name">{{ auth()->user()->name ?? auth()->user()->email }}</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end user-menu-list">
                        @if (auth()->user()->role === 'admin')
                            <li>
                                <a href="{{ route('admin.dashboard') }}" class="dropdown-item">Dashboard</a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ url('/home') }}" class="dropdown-item">Home</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <span class="user-name">Guest!</span>
            @endauth
        </div>


    </div>

</nav>


