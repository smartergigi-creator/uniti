<nav class="navbar navbar-expand-lg nova-navbar fixed-top">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="{{ asset('images/logo.png') }}" width="50" alt="">
            <span class="brand-text ms-2">UNITI</span>
        </a>

        <!-- Mobile Right (User + Offcanvas Toggle) -->
        <div class="d-flex align-items-center d-lg-none">

            @auth
                <div class="dropdown me-2">
                    <button class="btn user-menu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        {{ auth()->user()->name ?? auth()->user()->email }}
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        @if (auth()->user()->role === 'admin')
                            <li>
                                <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                                    Dashboard
                                </a>
                            </li>
                        @endif
                        <li><a href="{{ url('/home') }}" class="dropdown-item">Home</a></li>
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
            @endauth

            <!-- ✅ OFFCANVAS TOGGLE -->
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Desktop Menu Only -->
        <div class="collapse navbar-collapse d-none d-lg-flex">

            @php
                $homeUrl = url('/home');
                $menuCategories = isset($categories)
                    ? $categories
                    : \App\Models\Category::whereNull('parent_id')
                        ->with([
                            'children' => function ($query) {
                                $query->orderBy('name')
                                    ->with([
                                        'children' => function ($childQuery) {
                                            $childQuery->orderBy('name');
                                        },
                                    ]);
                            },
                        ])
                        ->orderBy('name')
                        ->get();
                if ($menuCategories instanceof \Illuminate\Database\Eloquent\Collection) {
                    $menuCategories->loadMissing([
                        'children' => function ($query) {
                            $query->orderBy('name')
                                ->with([
                                    'children' => function ($childQuery) {
                                        $childQuery->orderBy('name');
                                    },
                                ]);
                        },
                    ]);
                }
                $websiteLinks = [
                    'blogs.tourlytours.com',
                    'asi.com.ph',
                    'my.pesocard.ph',
                    'ustdi.com.ph',
                    'my.bisita.com.ph',
                    'ecapp.uniti.com.ph',
                    'ikak.net',
                    'murakami.com.ph',
                    'autogate.net.ph',
                    'powerboard.com.ph',
                    'smartertrack.com.ph',
                    'kainan.ph',
                    'ocs.com.ph',
                    'bisita.com.ph',
                    'smarter.com.ph',
                ];
                $websiteDropdownClass = count($websiteLinks) > 10
                    ? 'dropdown-menu website-links-menu multi-column-menu'
                    : 'dropdown-menu website-links-menu';
            @endphp

            <!-- Center Menu -->
            <ul class="navbar-nav mx-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="{{ $homeUrl }}">Home</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        Website
                    </a>
                    <ul class="{{ $websiteDropdownClass }}">
                        @foreach ($websiteLinks as $websiteLink)
                            <li>
                                <a class="dropdown-item" href="{{ 'https://' . $websiteLink }}" target="_blank"
                                    rel="noopener noreferrer">
                                    {{ $websiteLink }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                @foreach ($menuCategories as $menuCategory)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            {{ $menuCategory->name }}
                        </a>

                        @if ($menuCategory->children->isNotEmpty())
                            @php
                                $hasGrandChildren = $menuCategory->children->contains(function ($childCategory) {
                                    return $childCategory->children->isNotEmpty();
                                });
                                $categoryMenuClass = !$hasGrandChildren && $menuCategory->children->count() > 10
                                    ? 'dropdown-menu category-dropdown-menu category-dropdown-columns'
                                    : 'dropdown-menu category-dropdown-menu';
                            @endphp
                            <ul class="{{ $categoryMenuClass }}">
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ $homeUrl }}?category={{ $menuCategory->id }}#ebooksSection">
                                        All {{ $menuCategory->name }}
                                    </a>
                                </li>
                                @foreach ($menuCategory->children as $menuSubcategory)
                                    @if ($menuSubcategory->children->isNotEmpty())
                                        <li class="dropdown-submenu">
                                            <a class="dropdown-item dropdown-toggle"
                                                href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}#ebooksSection">
                                                {{ $menuSubcategory->name }}
                                            </a>
                                            @php
                                                $subcategoryMenuClass = $menuSubcategory->children->count() > 8
                                                    ? 'dropdown-menu category-submenu-menu category-submenu-columns'
                                                    : 'dropdown-menu category-submenu-menu';
                                            @endphp
                                            <ul class="{{ $subcategoryMenuClass }}">
                                                @foreach ($menuSubcategory->children as $menuRelatedSubcategory)
                                                    <li>
                                                        <a class="dropdown-item related-subcategory-item"
                                                            href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}&related_subcategory={{ $menuRelatedSubcategory->id }}#ebooksSection">
                                                            {{ $menuRelatedSubcategory->name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @else
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ $homeUrl }}?category={{ $menuCategory->id }}&subcategory={{ $menuSubcategory->id }}#ebooksSection">
                                                {{ $menuSubcategory->name }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach

            </ul>

            <!-- Desktop User -->
            <div class="ms-auto d-flex align-items-center">
                @auth
                    <div class="dropdown">
                        <button class="btn user-menu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            {{ auth()->user()->name ?? auth()->user()->email }}
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            @if (auth()->user()->role === 'admin')
                                <li>
                                    <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                                        Dashboard
                                    </a>
                                </li>
                            @endif
                            <li><a href="{{ url('/home') }}" class="dropdown-item">Home</a></li>
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
                @endauth
            </div>

        </div>
    </div>
</nav>
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-semibold">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        @php
            $homeUrl = url('/home');
            $menuCategories = isset($categories)
                ? $categories
                : \App\Models\Category::whereNull('parent_id')
                    ->with([
                        'children' => function ($query) {
                            $query->orderBy('name')
                                ->with([
                                    'children' => function ($childQuery) {
                                        $childQuery->orderBy('name');
                                    },
                                ]);
                        },
                    ])
                    ->orderBy('name')
                    ->get();
            if ($menuCategories instanceof \Illuminate\Database\Eloquent\Collection) {
                $menuCategories->loadMissing([
                    'children' => function ($query) {
                        $query->orderBy('name')
                            ->with([
                                'children' => function ($childQuery) {
                                    $childQuery->orderBy('name');
                                },
                            ]);
                    },
                ]);
            }
            $websiteLinks = [
                'blogs.tourlytours.com',
                'asi.com.ph',
                'my.pesocard.ph',
                'ustdi.com.ph',
                'my.bisita.com.ph',
                'ecapp.uniti.com.ph',
                'ikak.net',
                'murakami.com.ph',
                'autogate.net.ph',
                'powerboard.com.ph',
                'smartertrack.com.ph',
                'kainan.ph',
                'ocs.com.ph',
                'bisita.com.ph',
                'smarter.com.ph',
            ];
            $websiteDropdownClass = count($websiteLinks) > 10
                ? 'dropdown-menu website-links-menu multi-column-menu'
                : 'dropdown-menu website-links-menu';
        @endphp

        <ul class="navbar-nav mobile-menu-list">

            <li class="nav-item">
                <a class="nav-link" href="{{ $homeUrl }}">Home</a>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    Website
                </a>
                <ul class="{{ $websiteDropdownClass }}">
                    @foreach ($websiteLinks as $websiteLink)
                        <li>
                            <a class="dropdown-item" href="{{ 'https://' . $websiteLink }}" target="_blank"
                                rel="noopener noreferrer">
                                {{ $websiteLink }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>

            @foreach ($menuCategories as $menuCategory)
                <li class="nav-item">
                    <a class="nav-link" href="{{ $homeUrl }}?category={{ $menuCategory->id }}#ebooksSection">
                        {{ $menuCategory->name }}
                    </a>
                </li>
            @endforeach

        </ul>

    </div>
</div>
