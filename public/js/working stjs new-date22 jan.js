(() => {


/* =====================================
   DOM READY
===================================== */
document.addEventListener("DOMContentLoaded", () => {
    initFileUpload();
   
});

/* =====================================
   FILE UPLOAD
===================================== */
function initFileUpload() {

    const pdfInput        = document.getElementById("pdfInput");
    const folderInput     = document.getElementById("folderInput");
    const selectFilesBtn  = document.getElementById("selectFiles");
    const selectFolderBtn = document.getElementById("selectFolder");

    const fileList  = document.getElementById("fileList");
    const fileItems = document.getElementById("fileItems");
    const fileCount = document.getElementById("fileCount");

    if (!pdfInput && !folderInput) return;

    function updateFileList(files) {
        fileItems.innerHTML = "";
        let count = 0;
        let folderName = null;

        [...files].forEach(file => {
            if (file.type === "application/pdf") {
                count++;
                if (!folderName && file.webkitRelativePath) {
                    folderName = file.webkitRelativePath.split("/")[0];
                }
                const li = document.createElement("li");
                li.textContent = file.name;
                fileItems.appendChild(li);
            }
        });

        if (count > 0) {
            fileList.style.display = "block";
            fileCount.textContent = count;
            if (folderName) {
                const title = document.createElement("li");
                title.style.fontWeight = "bold";
                title.textContent = `📁 Folder: ${folderName}`;
                fileItems.prepend(title);
            }
        }
    }

    selectFilesBtn?.addEventListener("click", () => pdfInput.click());
    selectFolderBtn?.addEventListener("click", () => folderInput.click());
    pdfInput?.addEventListener("change", () => updateFileList(pdfInput.files));
    folderInput?.addEventListener("change", () => updateFileList(folderInput.files));
}


 


    let pageFlip = null;
    let currentMode = null; // "single" | "double"
    let resizeTimer = null;

    /* ===============================
       CONFIG
    =============================== */
    // const PAGE_RATIO = 700 / 440; // height / width
    let PAGE_RATIO = 700 / 440; // default (portrait)

    let zoomLevel = 1;

    const ZOOM_MIN = 1;
    const ZOOM_MAX = 2.5;
    const ZOOM_STEP = 0.2;

    /* ===============================
       DOM READY
    =============================== */
    document.addEventListener("DOMContentLoaded", () => {
        const flipbook = document.getElementById("flipbook");
        if (flipbook) waitForImages();
        setupFullscreen();
        setupZoomControls();
    });

    //detectPageRatio

function detectPageRatio() {
    const firstPage = document.querySelector(".page img, .page canvas");

    if (!firstPage) return;

    const w = firstPage.naturalWidth || firstPage.width;
    const h = firstPage.naturalHeight || firstPage.height;

    if (w && h) {
        PAGE_RATIO = h / w; // auto adjust for landscape/portrait
        console.log("PAGE_RATIO detected:", PAGE_RATIO);
    }
}

    /* ===============================
       IMAGE WAIT
    =============================== */
    function waitForImages() {
        const book = document.getElementById("flipbook");
        const imgs = book.querySelectorAll("img");

        let loaded = 0;
        if (!imgs.length) return init();

        imgs.forEach(img => {
            if (img.complete) loaded++;
            else img.onload = img.onerror = () => {
                loaded++;
                // if (loaded === imgs.length) init();
                if (loaded === imgs.length) {
    detectPageRatio(); // 👈 important
    init();
}

            };
        });

        // if (loaded === imgs.length) init();
        if (loaded === imgs.length) {
    detectPageRatio(); // 👈 important
    init();
}

    }

    /* ===============================
       INIT
    =============================== */
    function init() {
        applyMode(true);
        window.addEventListener("resize", onResize);
        hideLoader();
    }

    function onResize() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => applyMode(false), 200);
    }

    /* ===============================
       MODE LOGIC
       Mobile = Single
       Desktop = Double
    =============================== */
function applyMode(initial) {

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const isMobile = vw <= 900;
    const isLandscape = vw > vh;

    let requiredMode;

    // 📱 Mobile portrait → single
    if (isMobile && !isLandscape) {
        requiredMode = "single";
    }
    // 📱 Mobile landscape + Desktop → double
    else {
        requiredMode = "double";
    }

    if (currentMode === requiredMode && !initial) {
        updateSize();
        return;
    }

    currentMode = requiredMode;

    destroyFlipbook();
    initFlipbook(requiredMode);
}




    /* ===============================
       SIZE CALCULATION
    =============================== */
function calculateSize() {
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    let maxW = vw * 0.9;
    let maxH = vh * 0.9;

    maxW = Math.min(maxW, 1800);
    maxH = Math.min(maxH, 1200);

    let width = maxW;
    let height = width * PAGE_RATIO;

    // If too tall, resize
    if (height > maxH) {
        height = maxH;
        width = height / PAGE_RATIO;
    }

    return {
        width: Math.floor(width),
        height: Math.floor(height)
    };
}



    function updateSize() {
        if (!pageFlip) return;
        const { width, height } = calculateSize();
        pageFlip.update({ width, height });
    }

    /* ===============================
       DESTROY
    =============================== */
    function destroyFlipbook() {
        if (pageFlip) {
            pageFlip.destroy();
            pageFlip = null;
        }
    }

    /* ===============================
       INIT FLIPBOOK
    =============================== */
   function initFlipbook(mode) {

    const book = document.getElementById("flipbook");
    if (!book) return;


    /* ===== REMOVE OLD FAKE PAGES ===== */
    book.querySelectorAll(".page.fake").forEach(p => p.remove());


    /* ===== ADD FAKE PAGES ONLY IN DOUBLE MODE ===== */
    if (mode === "double") {

        let pages = book.querySelectorAll(".page");

        // Fake first
        if (pages.length && !pages[0].classList.contains("fake")) {

            const fakeStart = document.createElement("div");
            fakeStart.className = "page fake";

            book.insertBefore(fakeStart, pages[0]);
        }

        pages = book.querySelectorAll(".page");

        // Fake last (if odd)
        if (pages.length % 2 !== 0) {

            const fakeEnd = document.createElement("div");
            fakeEnd.className = "page fake";

            book.appendChild(fakeEnd);
        }
    }


    /* ===== SIZE ===== */
    const { width, height } = calculateSize();


    /* ===== INIT PAGEFLIP ===== */
    pageFlip = new St.PageFlip(book, {

        width,
        height,

        size: "fixed",

        // 🔥 BOTH FIXES
        showCover: mode === "double",
        usePortrait: mode === "single",

        maxShadowOpacity: 0.3,
        mobileScrollSupport: true,

        flippingTime: 600
    });


    pageFlip.loadFromHTML(book.querySelectorAll(".page"));


    /* ===== START PAGE ===== */
    setTimeout(() => {

        if (mode === "double") {
            pageFlip.turnToPage(1); // skip fake
        } else {
            pageFlip.turnToPage(0); // portrait
        }

    }, 250);


    /* ===== EVENTS ===== */

    pageFlip.on("flip", updateNavButtons);

    attachNavButtons();
    updateNavButtons();
    adjustToolbarPosition();
}


    /* ===============================
       NAV BUTTONS
    =============================== */
    function attachNavButtons() {
        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");

        if (prev) {
            prev.onclick = e => {
                e.preventDefault();
                pageFlip.flipPrev();
            };
        }

        if (next) {
            next.onclick = e => {
                e.preventDefault();
                pageFlip.flipNext();
            };
        }
    }

    function updateNavButtons() {
        if (!pageFlip) return;

        const page = pageFlip.getCurrentPageIndex();
        const total = pageFlip.getPageCount() - 1;

        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");

        if (prev) prev.style.display = page <= 0 ? "none" : "flex";
        if (next) next.style.display = page >= total ? "none" : "flex";
    }

    /* ===============================
       LOADER
    =============================== */
    function hideLoader() {
        const loader = document.getElementById("ebookLoader");
        const viewer = document.getElementById("viewer-wrapper");

        if (loader) loader.remove();
        if (viewer) viewer.classList.add("show");
    }

    /* ===============================
       FULLSCREEN
    =============================== */
    function setupFullscreen() {
        const fullscreenBtn = document.getElementById("fullscreenToggle");
        const viewerWrapper = document.getElementById("viewer-wrapper");

        if (!fullscreenBtn || !viewerWrapper) return;

        fullscreenBtn.addEventListener("click", () => {
            if (!document.fullscreenElement) {
                viewerWrapper.requestFullscreen();
                document.body.classList.add("fullscreen-active");
            } else {
                document.exitFullscreen();
                document.body.classList.remove("fullscreen-active");
            }
        });

        document.addEventListener("fullscreenchange", () => {
            if (!document.fullscreenElement) {
                document.body.classList.remove("fullscreen-active");
            }
        });
    }

    /* ===============================
       ZOOM
    =============================== */
    function applyZoom(value) {
        zoomLevel = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, value));
        const flipbook = document.getElementById("flipbook");
        if (!flipbook) return;
        flipbook.style.transform = `scale(${zoomLevel})`;
    }

    function setupZoomControls() {
        const zoomInBtn = document.getElementById("zoomIn");
        const zoomOutBtn = document.getElementById("zoomOut");
        const zoomResetBtn = document.getElementById("zoomReset");

        if (zoomInBtn) zoomInBtn.onclick = () => applyZoom(zoomLevel + ZOOM_STEP);
        if (zoomOutBtn) zoomOutBtn.onclick = () => applyZoom(zoomLevel - ZOOM_STEP);
        if (zoomResetBtn) zoomResetBtn.onclick = () => applyZoom(1);
    }

function adjustToolbarPosition() {
    const toolbar = document.querySelector(".viewer-toolbar");
    if (!toolbar) return;

    const isMobile = window.innerWidth <= 900;

    /* ✅ ALWAYS TOP-RIGHT (DESKTOP + MOBILE + FULLSCREEN) */
    toolbar.style.top = "16px";
    toolbar.style.right = "16px";
    toolbar.style.left = "auto";
    toolbar.style.bottom = "auto";
    toolbar.style.transform = "none";

    /* Slightly tighter on mobile */
    if (isMobile) {
        toolbar.style.top = "12px";
        toolbar.style.right = "12px";
    }
}

window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        applyMode(false);
        adjustToolbarPosition();
    }, 200);
});
document.addEventListener("fullscreenchange", () => {
    adjustToolbarPosition();
});





})();
/* =====================================
   SHARE LINK (GLOBAL)
===================================== */
function openShareModal(ebookId) {
    fetch(`/ebook/share/${ebookId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById("shareLinkInput").value = data.publicLink;
            new bootstrap.Modal(
                document.getElementById("shareModal")
            ).show();
        })
        .catch(() => alert("Failed to generate share link"));
}

function copyShareLink() {
    const input = document.getElementById("shareLinkInput");
    input.select();
    document.execCommand("copy");
    alert("Link copied!");
}
   
// let lastOrientation = window.innerWidth > window.innerHeight ? "landscape" : "portrait";

// window.addEventListener("resize", () => {
//     const currentOrientation =
//         window.innerWidth > window.innerHeight ? "landscape" : "portrait";

//     if (currentOrientation !== lastOrientation) {
//         location.reload(); 
//     }
// });
window.addEventListener("orientationchange", () => {
    setTimeout(() => {
        applyMode(false);
        updateSize();
    }, 400);
});
function handleOrientationFix() {
    const isMobile = window.innerWidth <= 900;

    if (isMobile) {
        applyMode(false); // always single
    }
}

window.addEventListener("orientationchange", () => {
    setTimeout(handleOrientationFix, 300);
});

window.addEventListener("resize", () => {
    setTimeout(handleOrientationFix, 300);
});
