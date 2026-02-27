/* =====================================================
   FINAL CLEAN EBOOK ENGINE – STPAGEFLIP
   ✔ Mobile portrait = single page
   ✔ Landscape + desktop = double page
   ✔ No duplicate init
   ✔ No resize loop
   ✔ No unsupported API
===================================================== */

(() => {

    let pageFlip = null;
    let flipInitialized = false;
    let resizeTimer = null;

    /* =====================================
       DOM READY
    ===================================== */
    document.addEventListener("DOMContentLoaded", () => {
        setupAudioUnlock();

        if (document.getElementById("flipbook")) {
            waitForImages();
        }
    });

    /* =====================================
       AUDIO
    ===================================== */
    let flipSound = null;
    let audioUnlocked = false;

    function setupAudioUnlock() {
        flipSound = document.getElementById("flipSound");
        if (!flipSound) return;

        const unlock = () => {
            if (audioUnlocked) return;
            flipSound.volume = 0.4;
            flipSound.play().then(() => {
                flipSound.pause();
                flipSound.currentTime = 0;
                audioUnlocked = true;
            }).catch(() => {});
        };

        document.addEventListener("click", unlock, { once: true });
        document.addEventListener("touchstart", unlock, { once: true });
    }

    function playFlipSound() {
        if (!flipSound || !audioUnlocked) return;
        flipSound.currentTime = 0;
        flipSound.play().catch(() => {});
    }

    /* =====================================
       WAIT FOR IMAGES
    ===================================== */
    function waitForImages() {
        const book = document.getElementById("flipbook");
        if (!book) return;

        const images = book.querySelectorAll("img");
        if (!images.length) {
            initFlipbook();
            return;
        }

        let loaded = 0;
        images.forEach(img => {
            if (img.complete) {
                loaded++;
            } else {
                img.onload = img.onerror = () => {
                    loaded++;
                    if (loaded === images.length) initFlipbook();
                };
            }
        });

        if (loaded === images.length) initFlipbook();
    }

    /* =====================================
       INIT FLIPBOOK
    ===================================== */
    function initFlipbook() {
        if (flipInitialized) return;
        if (typeof St === "undefined") return;

        const book = document.getElementById("flipbook");
        if (!book) return;

        // EVEN PAGE FIX
        const pages = book.querySelectorAll(".page");
        if (pages.length % 2 !== 0) {
            const blank = document.createElement("div");
            blank.className = "page blank";
            book.appendChild(blank);
        }

        pageFlip = new St.PageFlip(book, {
            width: 400,
            height: 500,
            size: "fixed",
            showCover: true,
            usePortrait: false,
            maxShadowOpacity: 0.5,
            mobileScrollSupport: false,
            useMouseEvents: true
        });

        pageFlip.loadFromHTML(book.querySelectorAll(".page"));

        // 🔥 APPLY RESPONSIVE MODE ON LOAD
        applyResponsiveMode();

        // FLIP EVENT
        pageFlip.on("flip", () => {
            const page = pageFlip.getCurrentPageIndex();
            const total = pageFlip.getPageCount();

            document.body.classList.add("no-anim");
            document.body.classList.remove("book-open", "book-closed");

            if (page === 0 || page === total - 1) {
                document.body.classList.add("book-closed");
            } else {
                document.body.classList.add("book-open");
            }

            setTimeout(() => {
                document.body.classList.remove("no-anim");
            }, 80);

            playFlipSound();
            updateNavButtons();
        });

        flipInitialized = true;
        attachNavButtons();
        updateNavButtons();
        hideLoader();
    }

    /* =====================================
       RESPONSIVE MODE (SAFE VERSION)
    ===================================== */
    function applyResponsiveMode() {
    if (!pageFlip) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const isMobile = vw <= 900;
    const isLandscape = vw > vh;

    // 📱 MOBILE → ALWAYS SINGLE
    if (isMobile) {
        pageFlip.update({
            usePortrait: true,
            showCover: false
        });
    }
    // 💻 DESKTOP / LANDSCAPE → DOUBLE
    else {
        pageFlip.update({
            usePortrait: false,
            showCover: true
        });
    }
}



    window.addEventListener("resize", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(applyResponsiveMode, 200);
    });

    /* =====================================
       NAV BUTTONS
    ===================================== */
    function attachNavButtons() {
        document.getElementById("prevPage")?.addEventListener("click", e => {
            e.preventDefault();
            pageFlip?.flipPrev();
        });

        document.getElementById("nextPage")?.addEventListener("click", e => {
            e.preventDefault();
            pageFlip?.flipNext();
        });
    }

    function updateNavButtons() {
        if (!pageFlip) return;

        const page = pageFlip.getCurrentPageIndex();
        const total = pageFlip.getPageCount() - 1;

        document.getElementById("prevPage").style.display =
            page <= 0 ? "none" : "flex";

        document.getElementById("nextPage").style.display =
            page >= total ? "none" : "flex";
    }

    /* =====================================
       LOADER
    ===================================== */
    function hideLoader() {
        document.getElementById("ebookLoader")?.remove();
        document.getElementById("viewer-wrapper")?.classList.add("show");
    }
   


    
})();
