@extends('layout.app')

@section('title', 'Websites')
@section('body-class', 'website-page')

@section('content')
    <section class="website-content">
        <div class="website-hero">
            <div>
                <span class="website-kicker">Directory</span>
                <h1>Website List</h1>
                <p>Open any company website directly from one place.</p>
            </div>

            <div class="website-summary-card">
                <span>Total Websites</span>
                <strong>{{ $websiteLinks->count() }}</strong>
            </div>
        </div>

        <div class="website-panel">
            <div class="website-panel-head">
                <div>
                    <h2>All Websites</h2>
                    <p>Click any website below to open it in a new tab.</p>
                </div>
                <a href="{{ url('/home') }}" class="btn btn-outline-primary rounded-pill px-3">Back to Home</a>
            </div>

            <div class="website-table-wrap">
                <table class="table website-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Website</th>
                            <th scope="col">URL</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($websiteLinks as $websiteLink)
                            <tr>
                                <td>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="website-name-cell">
                                        <span class="website-dot"></span>
                                        <div>
                                            <strong>{{ $websiteLink }}</strong>
                                            <small>Company Website</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ 'https://' . $websiteLink }}" target="_blank" rel="noopener noreferrer"
                                        class="website-link-text">
                                        {{ 'https://' . $websiteLink }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    <a href="{{ 'https://' . $websiteLink }}" target="_blank" rel="noopener noreferrer"
                                        class="btn btn-sm website-open-btn">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection


