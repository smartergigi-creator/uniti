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
    if (audioUnlocked) return;

    flipSound = document.getElementById("flipSound");
    if (!flipSound) return;

    flipSound.volume = 0.4;
    flipSound.play().then(() => {
        flipSound.pause();
        flipSound.currentTime = 0;
        audioUnlocked = true;
    }).catch(() => {});
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
   INIT FLIPBOOK (CLOSED)
===================================== */
function initFlipbook() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const isMobile = window.innerWidth <= 900;

    flipbook.turn({
        width:  isMobile ? Math.min(window.innerWidth - 20, 420) : 370,
        height: isMobile ? Math.min(window.innerHeight - 60, 600) : 450,
        display: "single",
        autoCenter: true,
        page: 1,
        gradients: true,
        acceleration: true
    });

    const pageEl = document.getElementById("currentPage");
    const BLANK_OFFSET = 1;

    /* 🔊 SOUND ONLY WHEN PAGE REALLY TURNS */
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

    isMobile
        ? attachMobileEvents(flipbook)
        : attachDesktopEvents(flipbook);
}

/* =====================================
   MOBILE EVENTS (AUDIO SAFE)
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

    flipbook.on("click", () => {
        unlockAudioOnInteraction();
    });
}

/* =====================================
   DESKTOP OPEN / CLOSE BOOK
===================================== */
function attachDesktopEvents(flipbook) {

    let isOpen = false;
    let resizing = false;

    flipbook.on("turning", function (e, page) {

        if (resizing) return false;

        const totalPages = flipbook.turn("pages");

        if (page > 1 && !isOpen) {
            resizing = true;
            e.preventDefault();

            flipbook.turn("display", "double");
            flipbook.turn("size", 740, 450);
            isOpen = true;

            setTimeout(() => {
                resizing = false;
                flipbook.turn("page", page);
            }, 60);
            return false;
        }

        if ((page === 1 || page >= totalPages) && isOpen) {
            resizing = true;
            e.preventDefault();

            flipbook.turn("display", "single");
            flipbook.turn("size", 370, 450);
            isOpen = false;

            setTimeout(() => {
                resizing = false;
                flipbook.turn("page", page === 1 ? 1 : totalPages);
            }, 60);
            return false;
        }
    });

    $("#nextPage").on("click", () => flipbook.turn("next"));
    $("#prevPage").on("click", () => flipbook.turn("previous"));
}

/* =====================================
   SHARE LINK
===================================== */
function openShareModal(ebookId) {
    fetch(`/ebook/share/${ebookId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById("shareLinkInput").value = data.publicLink;
            new bootstrap.Modal(document.getElementById("shareModal")).show();
        });
}

function copyShareLink() {
    const input = document.getElementById("shareLinkInput");
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Link copied!");
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
   ZOOM / PINCH / GRAB
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

let isDragging = false, startX = 0, startY = 0;

flipbookEl.addEventListener("mousedown", e => {
    if (scale <= 1) return;
    isDragging = true;
    startX = e.clientX - translateX;
    startY = e.clientY - translateY;
});

document.addEventListener("mousemove", e => {
    if (!isDragging) return;
    translateX = e.clientX - startX;
    translateY = e.clientY - startY;
    updateTransform();
});

document.addEventListener("mouseup", () => isDragging = false);

/* MOBILE PINCH */
let pinchStartDistance = 0;
function dist(a, b) {
    return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
}

flipbookEl.addEventListener("touchstart", e => {
    if (e.touches.length === 2)
        pinchStartDistance = dist(e.touches[0], e.touches[1]);
}, { passive: true });

flipbookEl.addEventListener("touchmove", e => {
    if (e.touches.length !== 2) return;
    e.preventDefault();
    const d = dist(e.touches[0], e.touches[1]);
    applyScale(scale + (d - pinchStartDistance) * 0.004);
    pinchStartDistance = d;
}, { passive: false });
