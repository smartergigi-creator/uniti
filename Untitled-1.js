/* =================================================
   GLOBAL SCRIPT – SAFE FOR ALL PAGES
   - Upload (PDF / Folder)
   - Flipbook (Desktop + Mobile)
   - Page Flip Sound
   - Share Link
================================================= */

let flipSound;
let audioUnlocked = false;

/* =====================================
   DOM READY
===================================== */
document.addEventListener("DOMContentLoaded", () => {
    initFileUpload();
    setupAudioUnlock();
    waitForImagesThenInit();
});

/* =====================================
   FILE UPLOAD – SAFE (INDEX PAGE ONLY)
===================================== */
function initFileUpload() {

    const pdfInput        = document.getElementById("pdfInput");
    const folderInput     = document.getElementById("folderInput");
    const selectFilesBtn  = document.getElementById("selectFiles");
    const selectFolderBtn = document.getElementById("selectFolder");

    const fileList  = document.getElementById("fileList");
    const fileItems = document.getElementById("fileItems");
    const fileCount = document.getElementById("fileCount");

    if (!pdfInput && !folderInput) return; // 🚨 not upload page

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

    if (selectFilesBtn && pdfInput) {
        selectFilesBtn.addEventListener("click", () => pdfInput.click());
        pdfInput.addEventListener("change", () => updateFileList(pdfInput.files));
    }

    if (selectFolderBtn && folderInput) {
        selectFolderBtn.addEventListener("click", () => folderInput.click());
        folderInput.addEventListener("change", () => updateFileList(folderInput.files));
    }
}

/* =====================================
   AUDIO UNLOCK (MOBILE SAFE)
===================================== */
function setupAudioUnlock() {

    const unlock = () => {
        if (audioUnlocked) return;

        flipSound = document.getElementById("flipSound");
        if (!flipSound) return;

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
   WAIT FOR FLIPBOOK IMAGES
===================================== */
function waitForImagesThenInit() {

    const flipbookEl = document.getElementById("flipbook");
    if (!flipbookEl) return;

    const images = flipbookEl.querySelectorAll("img");
    if (!images.length) return;

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

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const isMobile = window.innerWidth <= 900;

    const width  = isMobile ? Math.min(window.innerWidth - 20, 420) : 370;
    const height = isMobile ? Math.min(window.innerHeight - 60, 600) : 450;

    flipbook.turn({
        width,
        height,
        display: "single",
        autoCenter: true,
        page: 1, // ✅ Always start safely
        gradients: true,
        acceleration: true
    });

    /* =====================================
       PAGE / SPREAD LABEL FIX
    ===================================== */

    const pageEl = document.getElementById("currentPage");
    const BLANK_OFFSET = 1; // 👈 because page 2 is blank

    flipbook.on("turned", function (event, page) {

        if (!pageEl) return;

        const totalPages = flipbook.turn("pages");
        const display = flipbook.turn("display");

        // ✅ MOBILE / SINGLE PAGE
        if (display === "single") {
            const logicalPage = Math.max(1, page - BLANK_OFFSET);
            pageEl.textContent = `Page ${logicalPage}`;
            return;
        }

        // ✅ DESKTOP / DOUBLE PAGE
        let left = page - BLANK_OFFSET;
        let right = left + 1;

        // Clamp values
        if (left < 1) left = 1;
        if (right > totalPages - BLANK_OFFSET) {
            right = totalPages - BLANK_OFFSET;
        }

        // Cover case
        if (page === 1) {
            pageEl.textContent = "Page 1";
        } else {
            pageEl.textContent = `Page ${left} – ${right}`;
        }
    });

    /* =====================================
       AUTO OPEN TO FIRST REAL CONTENT
    ===================================== */

    setTimeout(() => {
        flipbook.turn("page", 3); // 👈 first REAL page
    }, 300);

    isMobile
        ? attachMobileEvents(flipbook)
        : attachDesktopEvents(flipbook);
}



/* =====================================
   MOBILE EVENTS
===================================== */
function attachMobileEvents(flipbook) {

    let startX = 0;

    flipbook.on("touchstart", e => {
        startX = e.originalEvent.touches[0].clientX;
    });

    flipbook.on("touchend", e => {
        const diff = e.originalEvent.changedTouches[0].clientX - startX;
        if (Math.abs(diff) < 40) return;

        diff < 0 ? flipbook.turn("next") : flipbook.turn("previous");
        playFlipSound();
    });

    flipbook.on("click", e => {
        const x = e.pageX - flipbook.offset().left;
        x > flipbook.width() / 2 ? flipbook.turn("next") : flipbook.turn("previous");
        playFlipSound();
    });
}

/* =====================================
   DESKTOP EVENTS
===================================== */
function attachDesktopEvents(flipbook) {

    let isOpen = false;
    let resizing = false;

    flipbook.on("turning", function (e, page) {

        playFlipSound();

        if (resizing) return false;

        if (page > 1 && !isOpen) {
            resizing = true;
            e.preventDefault();

            flipbook.turn("display", "double");
            flipbook.turn("size", 740, 450);
            flipbook.addClass("open-book");

            isOpen = true;

            setTimeout(() => {
                resizing = false;
                flipbook.turn("page", page);
            }, 60);

            return false;
        }

        if (page === 1 && isOpen) {
            resizing = true;
            e.preventDefault();

            flipbook.turn("display", "single");
            flipbook.turn("size", 370, 450);
            flipbook.removeClass("open-book");

            isOpen = false;

            setTimeout(() => {
                resizing = false;
                flipbook.turn("page", 1);
            }, 60);

            return false;
        }
    });

    $("#nextPage").on("click", () => {
        flipbook.turn("next");
        playFlipSound();
    });

    $("#prevPage").on("click", () => {
        flipbook.turn("previous");
        playFlipSound();
    });
}

/* =====================================
   SHARE LINK
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
    input.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Link copied!");
}
/* =====================================
   FULLSCREEN (DESKTOP + MOBILE)
===================================== */

const fullscreenBtn = document.getElementById("fullscreenToggle");
const viewerWrapper = document.getElementById("viewer-wrapper");

if (fullscreenBtn && viewerWrapper) {

    fullscreenBtn.addEventListener("click", () => {

        if (!document.fullscreenElement) {

            viewerWrapper.requestFullscreen?.() ||
            viewerWrapper.webkitRequestFullscreen?.() ||
            viewerWrapper.msRequestFullscreen?.();

            viewerWrapper.classList.add("fullscreen-active");

        } else {

            document.exitFullscreen?.() ||
            document.webkitExitFullscreen?.() ||
            document.msExitFullscreen?.();

            viewerWrapper.classList.remove("fullscreen-active");
        }
    });

    // Cleanup on ESC / exit
    document.addEventListener("fullscreenchange", () => {
        if (!document.fullscreenElement) {
            viewerWrapper.classList.remove("fullscreen-active");

            // 🔍 Reset zoom when exiting fullscreen
            if (panzoomInstance) {
                panzoomInstance.reset();
                $("#flipbook").turn("disable", false);
            }
        }
    });
}
/* =================================================
   🔍 ZOOM IN / OUT + GRAB (DESKTOP) + PINCH (MOBILE)
================================================= */

let scale = 1;
let translateX = 0;
let translateY = 0;

const MIN_SCALE = 1;
const MAX_SCALE = 3;
const SCALE_STEP = 0.15;

const flipbook = document.getElementById("flipbook");
const zoomInBtn = document.getElementById("zoomIn");
const zoomOutBtn = document.getElementById("zoomOut");
const zoomResetBtn = document.getElementById("zoomReset");

/* ===============================
   APPLY TRANSFORM
================================ */
function updateTransform() {
    flipbook.style.transform =
        `translate(${translateX}px, ${translateY}px) scale(${scale})`;
}

/* ===============================
   APPLY ZOOM
================================ */
function applyScale(value) {
    scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, value));

    if (scale === 1) {
        translateX = 0;
        translateY = 0;
        flipbook.classList.remove("zoomed");
        $("#flipbook").turn("disable", false);
    } else {
        flipbook.classList.add("zoomed");
        $("#flipbook").turn("disable", true);
    }

    updateTransform();
}

/* ===============================
   BUTTON ZOOM
================================ */
zoomInBtn?.addEventListener("click", () => {
    applyScale(scale + SCALE_STEP);
});

zoomOutBtn?.addEventListener("click", () => {
    applyScale(scale - SCALE_STEP);
});

zoomResetBtn?.addEventListener("click", () => {
    applyScale(1);
});

/* ===============================
   DESKTOP: CTRL + WHEEL ZOOM
================================ */
flipbook?.addEventListener("wheel", (e) => {
    if (!e.ctrlKey) return;

    e.preventDefault();
    const delta = e.deltaY < 0 ? SCALE_STEP : -SCALE_STEP;
    applyScale(scale + delta);
}, { passive: false });

/* ===============================
   DESKTOP: GRAB & DRAG (PAN)
================================ */
let isDragging = false;
let startX = 0;
let startY = 0;

flipbook.addEventListener("mousedown", (e) => {
    if (scale <= 1) return;

    isDragging = true;
    startX = e.clientX - translateX;
    startY = e.clientY - translateY;
    e.preventDefault();
});

document.addEventListener("mousemove", (e) => {
    if (!isDragging) return;

    translateX = e.clientX - startX;
    translateY = e.clientY - startY;
    updateTransform();
});

document.addEventListener("mouseup", () => {
    isDragging = false;
});

/* ===============================
   MOBILE: PINCH ZOOM
================================ */
let pinchStartDistance = 0;

function touchDistance(t1, t2) {
    const dx = t1.clientX - t2.clientX;
    const dy = t1.clientY - t2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

flipbook.addEventListener("touchstart", (e) => {
    if (e.touches.length === 2) {
        pinchStartDistance = touchDistance(e.touches[0], e.touches[1]);
    }
}, { passive: true });

flipbook.addEventListener("touchmove", (e) => {
    if (e.touches.length !== 2) return;

    e.preventDefault();

    const newDistance = touchDistance(e.touches[0], e.touches[1]);
    const zoomDelta = (newDistance - pinchStartDistance) * 0.004;

    applyScale(scale + zoomDelta);
    pinchStartDistance = newDistance;
}, { passive: false });

/* ===============================
   MOBILE: DOUBLE TAP RESET
================================ */
let lastTap = 0;

flipbook.addEventListener("touchend", () => {
    const now = Date.now();
    if (now - lastTap < 300) {
        applyScale(1);
    }
    lastTap = now;
}); dont change any thing just update the code when loads page coverpage shows then after turn pages at last i need closed book page above code when i click share ebook it not worked no code loss important just update and give entire code 