<footer class="site-footer">
    <div class="site-footer-top">
        <div class="site-footer-brand">
            <h5 class="site-footer-logo">
                <img src="{{ asset('images/logo.png') }}" alt="UNITI Logo" class="site-footer-logo-img">
                UNITI
            </h5>
            <p>Your premium digital library for ebooks, manuals, policies, and documentation.</p>
        </div>

        <div class="site-footer-links">
            <h6>Quick Links</h6>
            <a href="{{ url('/home') }}">Home</a>
            <a href="{{ route('admin.categories') }}">Category</a>
        </div>

        <div class="site-footer-links">
            <h6>Support & Info</h6>
            <a href="#">Help Center</a>
            <a href="#">Contact Us</a>
            <a href="#">Knowledge Base</a>
        </div>

        <div class="site-footer-links">
            <h6>Legal</h6>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Disclaimer</a>
        </div>
    </div>

    <div class="site-footer-bottom">
        <div class="site-footer-meta">
            <span>&copy; {{ date('Y') }} UNITI. All rights reserved.</span>
            <div class="site-footer-social">
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>
    </div>
</footer>
