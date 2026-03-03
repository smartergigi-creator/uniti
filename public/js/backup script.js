/* =================================================
   GLOBAL SCRIPT – SAFE FOR ALL PAGES
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

/* =====================================
   AUDIO UNLOCK
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

function unlockAudioOnInteraction() {
    if (!audioUnlocked) setupAudioUnlock();
}

function playFlipSound() {
    if (!flipSound || !audioUnlocked) return;
    flipSound.currentTime = 0;
    flipSound.play().catch(() => {});
}

/* =====================================
   WAIT FOR IMAGES
===================================== */
function waitForImagesThenInit() {

    const flipbookEl = document.getElementById("flipbook");
    if (!flipbookEl) return;

    const images = flipbookEl.querySelectorAll("img");
    if (!images.length) return;

    let loaded = 0;
    images.forEach(img => {
        if (img.complete) loaded++;
        else img.onload = img.onerror = () => {
            loaded++;
            if (loaded === images.length) initFlipbook();
        };
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

    flipbook.turn({
        width:  isMobile ? Math.min(window.innerWidth - 20, 480) : 440,
        height: isMobile ? Math.min(window.innerHeight - 60, 650) : 560,
        display: "single",
        autoCenter: true,
        page: 1,
        gradients: true,
        acceleration: true
    });

    const pageEl = document.getElementById("currentPage");
    const BLANK_OFFSET = 1;

    flipbook.on("turned", function (e, page) {
        playFlipSound();

        if (!pageEl) return;

        const totalPages = flipbook.turn("pages");
        const display = flipbook.turn("display");

        if (display === "single") {
            pageEl.textContent = `Page ${Math.max(1, page - BLANK_OFFSET)}`;
            return;
        }

        let left = page - BLANK_OFFSET;
        let right = Math.min(left + 1, totalPages - BLANK_OFFSET);
        pageEl.textContent = `Page ${left} – ${right}`;
    });

    if (isMobile) {
        attachMobileEvents(flipbook);
        attachMobileNavButtons(flipbook);
    } else {
        attachDesktopEvents(flipbook);
    }
}

/* =====================================
   MOBILE SWIPE EVENTS
===================================== */
function attachMobileEvents(flipbook) {

    let startX = 0;

    flipbook.on("touchstart", e => {
        unlockAudioOnInteraction();
        startX = e.originalEvent.touches[0].clientX;
    });

    flipbook.on("touchend", e => {
        const diff = e.originalEvent.changedTouches[0].clientX - startX;
        if (Math.abs(diff) < 40) return;
        diff < 0 ? flipbook.turn("next") : flipbook.turn("previous");
    });
}

/* =====================================
   MOBILE PREV / NEXT FIX ✅
===================================== */
function attachMobileNavButtons(flipbook) {

    const prevBtn = document.getElementById("prevPage");
    const nextBtn = document.getElementById("nextPage");

    if (!prevBtn || !nextBtn) return;

    const goPrev = e => {
        e.preventDefault();
        e.stopPropagation();
        flipbook.turn("previous");
    };

    const goNext = e => {
        e.preventDefault();
        e.stopPropagation();
        flipbook.turn("next");
    };

    prevBtn.addEventListener("touchstart", goPrev, { passive: false });
    nextBtn.addEventListener("touchstart", goNext, { passive: false });

    prevBtn.addEventListener("click", goPrev);
    nextBtn.addEventListener("click", goNext);
}

/* =====================================
   DESKTOP EVENTS
===================================== */
function attachDesktopEvents(flipbook) {

    let isOpen = false;
    let resizing = false;

    flipbook.on("turning", function (e, page) {

        if (resizing) {
            e.preventDefault();
            return false;
        }

        const totalPages = flipbook.turn("pages");

        // 🔓 OPEN BOOK (single → double)
        if (!isOpen && page > 1) {
            e.preventDefault();     // ⛔ stop original turn
            resizing = true;

            flipbook.turn("display", "double");
            flipbook.turn("size", 880, 560);

            isOpen = true;

            setTimeout(() => {
                resizing = false;
                flipbook.turn("page", page);
            }, 80);

            return false;
        }

        // 🔒 CLOSE BOOK (double → single)
        if (isOpen && (page === 1 || page === totalPages)) {
            e.preventDefault();     // ⛔ stop original turn
            resizing = true;

            flipbook.turn("display", "single");
            flipbook.turn("size", 440, 560);

            isOpen = false;

            setTimeout(() => {
                resizing = false;
                flipbook.turn("page", page === 1 ? 1 : totalPages);
            }, 80);

            return false;
        }
    });

    $("#nextPage").on("click", () => flipbook.turn("next"));
    $("#prevPage").on("click", () => flipbook.turn("previous"));
}


/* =====================================
   FULLSCREEN
===================================== */
const fullscreenBtn = document.getElementById("fullscreenToggle");
const viewerWrapper = document.getElementById("viewer-wrapper");

fullscreenBtn?.addEventListener("click", () => {
    if (!document.fullscreenElement) {
        viewerWrapper.requestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
});

/* =====================================
   ZOOM / PINCH / DRAG
===================================== */
let scale = 1, translateX = 0, translateY = 0;
const MIN_SCALE = 1, MAX_SCALE = 3, SCALE_STEP = 0.15;

const flipbookEl = document.getElementById("flipbook");

function updateTransform() {
    flipbookEl.style.transform =
        `translate(${translateX}px, ${translateY}px) scale(${scale})`;
}

function applyScale(value) {
    scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, value));
    if (scale === 1) {
        translateX = translateY = 0;
        $("#flipbook").turn("disable", false);
    } else {
        $("#flipbook").turn("disable", true);
    }
    updateTransform();
}

document.getElementById("zoomIn")?.addEventListener("click", () => applyScale(scale + SCALE_STEP));
document.getElementById("zoomOut")?.addEventListener("click", () => applyScale(scale - SCALE_STEP));
document.getElementById("zoomReset")?.addEventListener("click", () => applyScale(1));

flipbookEl?.addEventListener("wheel", e => {
    if (!e.ctrlKey) return;
    e.preventDefault();
    applyScale(scale + (e.deltaY < 0 ? SCALE_STEP : -SCALE_STEP));
}, { passive: false });
// function resizeFlipbookOnOrientation() {

//     const flipbook = $("#flipbook");
//     if (!flipbook.length || !flipbook.data("turn")) return;

//     const isMobile = window.innerWidth <= 900;
//     if (!isMobile) return;

//     const isLandscape = window.innerWidth > window.innerHeight;

//     let width, height;

//     if (isLandscape) {
//         // 📱 Mobile Landscape
//         width  = Math.min(window.innerWidth - 40, 700);
//         height = Math.min(window.innerHeight - 80, 420);
//     } else {
//         // 📱 Mobile Portrait
//         width  = Math.min(window.innerWidth - 20, 480);
//         height = Math.min(window.innerHeight - 60, 650);
//     }

//     flipbook.turn("display", "single");
//     flipbook.turn("size", width, height);
// }
// window.addEventListener("resize", () => {
//     resizeFlipbookOnOrientation();
// });

// window.addEventListener("orientationchange", () => {
//     setTimeout(resizeFlipbookOnOrientation, 200);
// });
/* =====================================
   SHARE LINK (UNCHANGED – WORKS)
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
function updateSideNavButtons() {
    const prevBtn = document.getElementById("prevPage");
    const nextBtn = document.getElementById("nextPage");

    if (!prevBtn || !nextBtn || !$("#flipbook").length) return;

    const currentPage = $("#flipbook").turn("page");
    const totalPages  = $("#flipbook").turn("pages");

    // Hide previous on first page
    prevBtn.style.display = currentPage <= 1 ? "none" : "flex";

    // Hide next on last page
    nextBtn.style.display = currentPage >= totalPages ? "none" : "flex";
}

// Run on load
updateSideNavButtons();

// Run on every page turn
$("#flipbook").bind("turned", function () {
    updateSideNavButtons();
});

// function updateFlipbookLayout() {
//     const flipbook = $("#flipbook");
//     if (!flipbook.length || !flipbook.data("turn")) return;

//     const w = window.innerWidth;
//     const h = window.innerHeight;

//     const isMobile = w <= 900;
//     const isLandscape = w > h;

//     let width, height, display;

//     if (isMobile && !isLandscape) {
//         // 📱 MOBILE PORTRAIT → SINGLE PAGE
//         display = "single";
//         width  = Math.min(w - 20, 480);
//         height = Math.min(h - 60, 650);

//     } else {
//         // 📱 MOBILE LANDSCAPE + DESKTOP → DOUBLE PAGE
//         display = "double";

//         width  = Math.min(w * 0.9, 1100);
//         height = Math.min(h * 0.8, 720);
//     }

//     flipbook.turn("display", display);
//     flipbook.turn("size", width, height);

//     // keep current page centered
//     setTimeout(() => {
//         flipbook.turn("page", flipbook.turn("page"));
//         flipbook.turn("center");
//     }, 50);
// }
let lastOrientation =
    window.innerWidth > window.innerHeight ? "landscape" : "portrait";

window.addEventListener("resize", () => {

    const currentOrientation =
        window.innerWidth > window.innerHeight ? "landscape" : "portrait";

    if (currentOrientation !== lastOrientation) {
        lastOrientation = currentOrientation;

        location.reload(); // ✅ HARD REFRESH (ONLY ON ROTATE)
    }
});

function applyResponsiveFlipbook() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || !flipbook.data("turn")) return;

    const w = window.innerWidth;
    const h = window.innerHeight;

    const isMobile = w <= 900;
    const isLandscape = w > h;

    let width, height, display;

    if (isMobile && !isLandscape) {
        // 📱 MOBILE PORTRAIT → SINGLE PAGE
        display = "single";
        width  = Math.min(w - 20, 480);
        height = Math.min(h - 60, 650);

    } else {
        // 📱 MOBILE LANDSCAPE + DESKTOP → DOUBLE PAGE
        display = "double";
        width  = Math.min(w * 0.95, 1100);
        height = Math.min(h * 0.8, 720);
    }

    flipbook.turn("display", display);
    flipbook.turn("size", width, height);

    // keep current page
    setTimeout(() => {
        flipbook.turn("page", flipbook.turn("page"));
        flipbook.turn("center");
    }, 50);
}
window.addEventListener("resize", applyResponsiveFlipbook);
window.addEventListener("orientationchange", () => {
    setTimeout(applyResponsiveFlipbook, 200);
});
function forceMobileLandscapeDoublePageFit() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || !flipbook.data("turn")) return;

    const w = window.innerWidth;
    const h = window.innerHeight;

    const isMobile = w <= 900;
    const isLandscape = w > h;

    if (!isMobile || !isLandscape) return;

    // 📱 MOBILE LANDSCAPE → DOUBLE PAGE + FULL FIT
    flipbook.turn("display", "double");

    flipbook.turn(
        "size",
        Math.floor(w * 0.98),   // almost full width
        Math.floor(h * 0.85)    // safe height
    );

    setTimeout(() => {
        flipbook.turn("center");
    }, 50);
}
window.addEventListener("resize", forceMobileLandscapeDoublePageFit);

window.addEventListener("orientationchange", () => {
    setTimeout(forceMobileLandscapeDoublePageFit, 300);
});






//backup

function initFlipbook() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const w = window.innerWidth;
    const h = window.innerHeight;

    const isMobile = w <= 900;
    const isLandscape = w > h;

    const PAGE_RATIO = 700 / 440;

    let width, height, display;

    /* 📱 MOBILE PORTRAIT → SINGLE */
    if (isMobile && !isLandscape) {
        display = "single";
        // height  = Math.min(h - 60, 600);
        // height  = Math.floor(h * 0.9);   // 90% screen

        // width   = Math.floor(height / PAGE_RATIO);

        height = Math.floor(h * 0.9);
width  = Math.floor((height / PAGE_RATIO) * 1.05); // 🔼 5% wider

    }

    /* 📱 MOBILE LANDSCAPE → START WITH DOUBLE */
  else if (isMobile && isLandscape) {

    display = "double";

    height = Math.floor(h * 0.95);  // full height feel
    // width  = Math.floor((height / PAGE_RATIO) * 2 * 1.15); // 🔥 wider
    width = Math.floor((height / PAGE_RATIO) * 2 * 1.08);

}





    /* 💻 DESKTOP → OLD BEHAVIOUR */
    else {
        display = "single";
        width  = 440;
        height = 560;
    }

    flipbook.turn({
        width,
        height,
        display,
        autoCenter: false,
        page: 1,
        gradients: true,
        acceleration: true
    });

    // ✅ FIX INITIAL WHITE PAGE ON LOAD (MOBILE)
if (isMobile) {
    setTimeout(() => {

        const totalPages = flipbook.turn("pages");

        // Always start with CLOSED single page
        flipbook.turn("display", "single");

        // Resize to single page size
        flipbook.turn(
            "size",
            Math.floor(height / PAGE_RATIO),
            height
        );

        // Force page 1 again
        flipbook.turn("page", 1);

        updateSideNavButtons();

    }, 60); // ⏱ small delay = IMPORTANT
}


    /* 🔊 SOUND + PAGE NUMBER */
    const pageEl = document.getElementById("currentPage");
    const BLANK_OFFSET = 1;

    flipbook.off("turned").on("turned", function (e, page) {
        playFlipSound();

        if (!pageEl) return;

        const totalPages = flipbook.turn("pages");
        const d = flipbook.turn("display");

        if (d === "single") {
            pageEl.textContent = `Page ${Math.max(1, page - BLANK_OFFSET)}`;
        } else {
            let left = page - BLANK_OFFSET;
            let right = Math.min(left + 1, totalPages - BLANK_OFFSET);
            pageEl.textContent = `Page ${left} – ${right}`;
        }

        // updateSideNavButtons();
    });

    /* 🔥 FIX WHITE PAGE (FIRST / LAST) + NAV ISSUE */
    if (isMobile && isLandscape) {

        let resizing = false;

        flipbook.on("turning", function (e, page) {

            if (resizing) {
                e.preventDefault();
                // return false;
                return ;
            }

            const totalPages = flipbook.turn("pages");

            // FIRST PAGE → SINGLE
            if (page === 1) {
                resizing = true;

                flipbook.turn("display", "single");
                flipbook.turn(
                    "size",
                    Math.floor(height / PAGE_RATIO),
                    height
                );

                setTimeout(() => {
                    resizing = false;
                    flipbook.turn("page", 1);
                }, 60);

                return false;
            }

            // LAST PAGE → SINGLE
            if (page === totalPages) {
                resizing = true;

                flipbook.turn("display", "single");
                flipbook.turn(
                    "size",
                    Math.floor(height / PAGE_RATIO),
                    height
                );

                setTimeout(() => {
                    resizing = false;
                    flipbook.turn("page", totalPages);
                }, 60);

                return false;
            }

            // MIDDLE PAGES → DOUBLE
            if (flipbook.turn("display") !== "double") {
                resizing = true;

                flipbook.turn("display", "double");
                flipbook.turn(
                    "size",
                    Math.floor((height / PAGE_RATIO) * 2),
                    height
                );

                setTimeout(() => {
                    resizing = false;
                    flipbook.turn("page", page);
                }, 60);

                return false;
            }
        });
    }

    /* EVENTS */
    if (isMobile) {
        attachMobileEvents(flipbook);
        attachMobileNavButtons(flipbook);
    } else {
        attachDesktopEvents(flipbook);
    }

    // updateSideNavButtons();
}