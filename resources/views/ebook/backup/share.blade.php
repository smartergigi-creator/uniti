@extends('layout.app')

@section('content')

<div class="container mt-5">

    <h2>Share Ebook: {{ $ebook->title }}</h2>

    <div class="card p-3 mt-4">
        <label><strong>Sharable Link:</strong></label>

        <input type="text" id="shareLink" 
               class="form-control" 
               value="{{ $publicLink }}" readonly>

        <button class="btn btn-success mt-2" onclick="copyLink()">Copy Link</button>
    </div>

</div>

<script>
function copyLink() {
    const link = document.getElementById('shareLink');
    link.select();
    document.execCommand("copy");
    alert("Link copied!");
}
</script>

@endsection
