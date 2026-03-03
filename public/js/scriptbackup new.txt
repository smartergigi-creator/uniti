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
    // waitForImagesThenInit();
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
// function waitForImagesThenInit() {

//     const flipbookEl = document.getElementById("flipbook");
//     if (!flipbookEl) return;

//     const images = flipbookEl.querySelectorAll("img");
//     if (!images.length) return;

//     let loaded = 0;
//     images.forEach(img => {
//         if (img.complete) loaded++;
//         else img.onload = img.onerror = () => {
//             loaded++;
//             if (loaded === images.length) initFlipbook();
//         };
//     });

//     if (loaded === images.length) initFlipbook();
// }

/* =====================================
   INIT FLIPBOOK
===================================== */
function initFlipbook2() {

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
    width = Math.floor((height / PAGE_RATIO) * 2 * 1.28);

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

function initFlipbook3() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const PAGE_RATIO = 700 / 440; // height / width

    const isMobile  = vw <= 768;

    let singleWidth, singleHeight;
    let doubleWidth, doubleHeight;

    /* =========================
       SIZE CALCULATION
    ========================= */

    if (isMobile) {

        // 📱 MOBILE → HEIGHT BASED
        singleHeight = Math.floor(vh * 0.88);
        singleWidth  = Math.floor(singleHeight / PAGE_RATIO);

        if (singleWidth > vw * 0.95) {
            singleWidth  = Math.floor(vw * 0.95);
            singleHeight = Math.floor(singleWidth * PAGE_RATIO);
        }

        doubleWidth  = singleWidth * 2;
        doubleHeight = singleHeight;

    } else {

        // 💻 DESKTOP → WIDTH BASED (KEY FIX 🔥)
        doubleWidth  = Math.floor(vw * 0.78);
        singleWidth  = Math.floor(doubleWidth / 2);

        singleHeight = Math.floor(singleWidth * PAGE_RATIO);
        doubleHeight = singleHeight;

        // Prevent vertical overflow
        if (singleHeight > vh * 0.85) {
            singleHeight = Math.floor(vh * 0.85);
            singleWidth  = Math.floor(singleHeight / PAGE_RATIO);
            doubleWidth  = singleWidth * 2;
            doubleHeight = singleHeight;
        }
    }

    /* =========================
       INIT TURN.JS
    ========================= */
    flipbook.turn({
        width: singleWidth,
        height: singleHeight,
        display: "single",
        autoCenter: true,
        page: 1,
        gradients: true,
        acceleration: true
    });

    /* =========================
       LOAD STABILIZER
    ========================= */
    requestAnimationFrame(() => {
        flipbook.turn("size", singleWidth, singleHeight);
        flipbook.turn("display", "single");
        flipbook.turn("page", 1);
        flipbook.turn("center");
        updateSideNavButtons();
    });

    /* =========================
       DESKTOP OPEN / CLOSE
    ========================= */
    if (!isMobile) {

    let isOpen = false;
    let resizing = false;

    flipbook.off("turning").on("turning", function (e, page) {

        const totalPages = flipbook.turn("pages");

        // 🔒 First page → single
        if (page === 1 && isOpen) {
            e.preventDefault();
            resizing = true;

            flipbook.turn("display", "single");

            setTimeout(() => {
                flipbook.turn("size", singleWidth, singleHeight);
                flipbook.turn("page", 1);
                isOpen = false;
                resizing = false;
            }, 80);

            return false;
        }

        // 🔒 Last page → single
        if (page === totalPages && isOpen) {
            e.preventDefault();
            resizing = true;

            flipbook.turn("display", "single");

            setTimeout(() => {
                flipbook.turn("size", singleWidth, singleHeight);
                flipbook.turn("page", totalPages);
                isOpen = false;
                resizing = false;
            }, 80);

            return false;
        }

        // 🔓 First open → double (ONLY ONCE)
        if (!isOpen && page > 1) {
            e.preventDefault();
            resizing = true;

            flipbook.turn("display", "double");

            setTimeout(() => {
                flipbook.turn("size", doubleWidth, doubleHeight);
                flipbook.turn("page", page);
                isOpen = true;
                resizing = false;
            }, 80);

            return false;
        }

    });
}


    /* =========================
       EVENTS
    ========================= */
    if (isMobile) {
        attachMobileEvents(flipbook);
        attachMobileNavButtons(flipbook);
    } else {
        attachDesktopEvents(flipbook);
    }

    updateSideNavButtons();
}

function initFlipbook4() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const PAGE_RATIO = 700 / 440; // height / width
    const isMobile = vw <= 900;

    let singleWidth, singleHeight;
    let doubleWidth, doubleHeight;

    /* =================================================
       SIZE CALCULATION (STABLE)
    ================================================= */

    if (isMobile) {
        // 📱 MOBILE → HEIGHT BASED
        singleHeight = Math.floor(vh * 0.9);
        singleWidth  = Math.floor(singleHeight / PAGE_RATIO);

        if (singleWidth > vw * 0.95) {
            singleWidth  = Math.floor(vw * 0.95);
            singleHeight = Math.floor(singleWidth * PAGE_RATIO);
        }

        doubleWidth  = singleWidth * 2;
        doubleHeight = singleHeight;

    } else {
        // 💻 DESKTOP → WIDTH BASED (NO JUMP 🔥)
        doubleWidth  = Math.floor(vw * 0.78);
        singleWidth  = Math.floor(doubleWidth / 2);

        singleHeight = Math.floor(singleWidth * PAGE_RATIO);
        doubleHeight = singleHeight;

        // Prevent vertical overflow
        if (singleHeight > vh * 0.85) {
            singleHeight = Math.floor(vh * 0.85);
            singleWidth  = Math.floor(singleHeight / PAGE_RATIO);
            doubleWidth  = singleWidth * 2;
            doubleHeight = singleHeight;
        }
    }

    /* =================================================
       INIT TURN.JS (ALWAYS START SINGLE)
    ================================================= */
    flipbook.turn({
        width: singleWidth,
        height: singleHeight,
        display: "single",
        autoCenter: true,
        page: 1,
        gradients: true,
        acceleration: true
    });

    /* =================================================
       LOAD STABILIZER (VERY IMPORTANT)
    ================================================= */
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            flipbook.turn("size", singleWidth, singleHeight);
            flipbook.turn("display", "single");
            flipbook.turn("page", 1);
            flipbook.turn("center");
            updateSideNavButtons();
        });
    });

    /* =================================================
       PAGE TURN + SOUND
    ================================================= */
    flipbook.off("turned").on("turned", function () {
        playFlipSound();
        updateSideNavButtons();
    });

    /* =================================================
       DESKTOP OPEN / CLOSE LOGIC
    ================================================= */
    if (!isMobile) {

        let isOpen = false;
        let resizing = false;

        flipbook.off("turning").on("turning", function (e, page) {

            if (resizing) {
                e.preventDefault();
                return false;
            }

            const totalPages = flipbook.turn("pages");

            // 🔒 FIRST PAGE → SINGLE
            if (page === 1 && isOpen) {
                e.preventDefault();
                resizing = true;

                flipbook.turn("display", "single");

                setTimeout(() => {
                    flipbook.turn("size", singleWidth, singleHeight);
                    flipbook.turn("page", 1);
                    isOpen = false;
                    resizing = false;
                    updateSideNavButtons();
                }, 80);

                return false;
            }

            // 🔒 LAST PAGE → SINGLE
            if (page === totalPages && isOpen) {
                e.preventDefault();
                resizing = true;

                flipbook.turn("display", "single");

                setTimeout(() => {
                    flipbook.turn("size", singleWidth, singleHeight);
                    flipbook.turn("page", totalPages);
                    isOpen = false;
                    resizing = false;
                    updateSideNavButtons();
                }, 80);

                return false;
            }

            // 🔓 OPEN BOOK → DOUBLE (ONLY ONCE)
            if (!isOpen && page > 1) {
                e.preventDefault();
                resizing = true;

                flipbook.turn("display", "double");

                setTimeout(() => {
                    flipbook.turn("size", doubleWidth, doubleHeight);
                    flipbook.turn("page", page);
                    isOpen = true;
                    resizing = false;
                    updateSideNavButtons();
                }, 80);

                return false;
            }
        });
    }

    /* =================================================
       EVENTS
    ================================================= */
    if (isMobile) {
        attachMobileEvents(flipbook);
        attachMobileNavButtons(flipbook);
    } else {
        attachDesktopEvents(flipbook);
    }

    updateSideNavButtons();
}

function initFlipbook5() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const PAGE_RATIO = 700 / 440; // height / width

    const isMobile = vw <= 900;
    const isLandscape = vw > vh;

    let singleWidth, singleHeight;
    let doubleWidth, doubleHeight;

    /* =========================
       SIZE CALCULATION
    ========================= */

    if (isMobile) {
        // 📱 MOBILE (medium-large)
        singleHeight = Math.floor(vh * 0.9);
        singleWidth  = Math.floor(singleHeight / PAGE_RATIO);

        if (singleWidth > vw * 0.95) {
            singleWidth  = Math.floor(vw * 0.95);
            singleHeight = Math.floor(singleWidth * PAGE_RATIO);
        }

        doubleWidth  = singleWidth * 2;
        doubleHeight = singleHeight;

    } else {
        // 💻 DESKTOP
        doubleWidth  = Math.floor(vw * 0.82);
        singleWidth  = Math.floor(doubleWidth / 2);

        singleHeight = Math.floor(singleWidth * PAGE_RATIO);
        doubleHeight = singleHeight;

        if (singleHeight > vh * 0.88) {
            singleHeight = Math.floor(vh * 0.88);
            singleWidth  = Math.floor(singleHeight / PAGE_RATIO);
            doubleWidth  = singleWidth * 2;
            doubleHeight = singleHeight;
        }
    }

    /* =========================
       INIT – ALWAYS SINGLE FIRST
       (IMPORTANT)
    ========================= */
    flipbook.turn({
        width: singleWidth,
        height: singleHeight,
        display: "single",
        autoCenter: true,
        page: 1,
        gradients: true,
        acceleration: true
    });

    /* =========================
       LOAD STABILIZER
    ========================= */
    setTimeout(() => {
        flipbook.turn("display", "single");
        flipbook.turn("size", singleWidth, singleHeight);
        flipbook.turn("page", 1);
        flipbook.turn("center");
        // updateSideNavButtons();
    }, 80);

    /* =========================
       PAGE TURN EVENT
    ========================= */
    flipbook.off("turned").on("turned", function (e, page) {
        playFlipSound();
        updateSideNavButtons();
    });

    /* =========================
       OPEN / CLOSE LOGIC
       (MOBILE LANDSCAPE + DESKTOP)
    ========================= */
    let isOpen = false;
    let resizing = false;

    flipbook.off("turning").on("turning", function (e, page) {

        if (resizing) {
            e.preventDefault();
            return false;
        }

        const totalPages = flipbook.turn("pages");

        /* 🔒 FRONT PAGE → SINGLE */
        if (page === 1 && isOpen) {
            e.preventDefault();
            resizing = true;

            flipbook.turn("display", "single");

            setTimeout(() => {
                flipbook.turn("size", singleWidth, singleHeight);
                flipbook.turn("page", 1);
                isOpen = false;
                resizing = false;
            }, 80);

            return false;
        }

        /* 🔓 OPEN BOOK → DOUBLE
           (Mobile Landscape + Desktop only)
        */
        if (
            page > 1 &&
            !isOpen &&
            (!isMobile || isLandscape)
        ) {
            e.preventDefault();
            resizing = true;

            flipbook.turn("display", "double");

            setTimeout(() => {
                flipbook.turn("size", doubleWidth, doubleHeight);
                flipbook.turn("page", page);
                isOpen = true;
                resizing = false;
            }, 80);

            return false;
        }

        /* 🔒 LAST PAGE → SINGLE */
        if (page === totalPages && isOpen) {
            e.preventDefault();
            resizing = true;

            flipbook.turn("display", "single");

            setTimeout(() => {
                flipbook.turn("size", singleWidth, singleHeight);
                flipbook.turn("page", totalPages);
                isOpen = false;
                resizing = false;
            }, 80);

            return false;
        }
    });

    /* =========================
       EVENTS
    ========================= */
    if (isMobile) {
        attachMobileEvents(flipbook);
        attachMobileNavButtons(flipbook);
    } else {
        attachDesktopEvents(flipbook);
    }

    // updateSideNavButtons();
}
function normalizePages() {

    const $fb = $("#flipbook");
    let pages = $fb.children(".page").length;

    // Always make total EVEN + last page safe
    if (pages % 2 !== 0) {
        $fb.append(createBlankPage());
        pages++;
    }

    // Add ONE extra blank for last single page
    $fb.append(createBlankPage());
}

function createBlankPage() {
    return `
        <div class="page blank">
            <div style="
                width:100%;
                height:100%;
                background:#fff;
            "></div>
        </div>
    `;
}



function initFlipbook() {

    const $fb = $("#flipbook");
    if (!$fb.length || $fb.data("turn")) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const PAGE_RATIO = 700 / 440;
    const isMobile = vw <= 900;
    const isLandscape = vw > vh;

    /* =========================
       SIZE
    ========================= */
    let singleH = Math.floor(vh * 0.88);
    let singleW = Math.floor(singleH / PAGE_RATIO);

    if (singleW > vw * 0.9) {
        singleW = Math.floor(vw * 0.9);
        singleH = Math.floor(singleW * PAGE_RATIO);
    }

    let doubleW = singleW * 2;
    let doubleH = singleH;

    /* =========================
       PAGE COUNT FIX
    ========================= */
    let total = $fb.children(".page").length;
    if (total % 2 !== 0) {
        $fb.append('<div class="page blank"></div>');
        total++;
    }

    /* =========================
       INIT
    ========================= */
    $fb.turn({
        width: singleW,
        height: singleH,
        display: "single",
        page: 1,
        autoCenter: true,
        gradients: true,
        acceleration: true
    });

    /* =========================
       DISPLAY CONTROL (SAFE)
    ========================= */
    function applyLayout(page) {

        // FIRST PAGE
        if (page === 1) {
            $fb.turn("display", "single");
            $fb.turn("size", singleW, singleH);
            return;
        }

        // LAST PAGE
        if (page === total) {
            $fb.turn("display", "single");
            $fb.turn("size", singleW, singleH);
            return;
        }

        // INSIDE PAGES
        if (!isMobile || isLandscape) {
            $fb.turn("display", "double");
            $fb.turn("size", doubleW, doubleH);
        } else {
            $fb.turn("display", "single");
            $fb.turn("size", singleW, singleH);
        }
    }

    /* =========================
       EVENTS
    ========================= */
    $fb.on("turning", function (e, page) {

        // ❌ BLOCK INVALID
        if (page < 1 || page > total) {
            e.preventDefault();
            return false;
        }

        applyLayout(page);
    });

    $fb.on("turned", function () {
        playFlipSound();
        updateSideNavButtons();
    });

    applyLayout(1);

    if (isMobile) attachMobileEvents($fb);
}












function initFlipbook1() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || flipbook.data("turn")) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const PAGE_RATIO = 700 / 440;
    const isMobile = vw <= 900;
    const isLandscape = vw > vh;

    let singleWidth, singleHeight;
    let doubleWidth, doubleHeight;

    /* =========================
       SIZE CALCULATION (SAFE)
    ========================= */

    // SINGLE PAGE
    singleHeight = Math.floor(vh * 0.88);
    singleWidth  = Math.floor(singleHeight / PAGE_RATIO);

    if (singleWidth > vw * 0.9) {
        singleWidth  = Math.floor(vw * 0.9);
        singleHeight = Math.floor(singleWidth * PAGE_RATIO);
    }

    // DOUBLE PAGE (LIMITED TO SCREEN)
    doubleWidth  = Math.min(singleWidth * 2, Math.floor(vw * 0.9));
    doubleHeight = Math.min(singleHeight, Math.floor(vh * 0.88));

    /* =========================
       INIT – ALWAYS SINGLE FIRST
    ========================= */
    flipbook.turn({
        width: singleWidth,
        height: singleHeight,
        display: "single",
        page: 1,
        autoCenter: true,
        gradients: true,
        acceleration: true
    });

    setTimeout(() => {
        flipbook.turn("display", "single");
        flipbook.turn("size", singleWidth, singleHeight);
        flipbook.turn("page", 1);
        flipbook.turn("center");
        updateSideNavButtons();
    }, 60);

    /* =========================
       PAGE TURN EVENT
    ========================= */
    flipbook.off("turned").on("turned", function () {
        playFlipSound();
        updateSideNavButtons();
    });

    /* =========================
       OPEN / CLOSE LOGIC (FIXED)
    ========================= */
    let isOpen = false;
    let locking = false;

    flipbook.off("turning").on("turning", function (e, page) {

        if (locking) {
            e.preventDefault();
            return false;
        }

        const totalPages = flipbook.turn("pages");

        /* 🔓 OPEN BOOK → DOUBLE
           (DESKTOP + MOBILE LANDSCAPE)
        */
        if (page > 1 && !isOpen && (!isMobile || isLandscape)) {
            e.preventDefault();
            locking = true;

            flipbook.turn("display", "double");

            setTimeout(() => {
                const target = page % 2 === 0 ? page - 1 : page;
                flipbook.turn("size", doubleWidth, doubleHeight);
                flipbook.turn("page", target);
                flipbook.turn("center");
                isOpen = true;
                locking = false;
            }, 80);

            return false;
        }

        /* 🔒 LAST PAGE → SINGLE (FIXED CONDITION) */
        if (page >= totalPages - 1 && isOpen) {
            e.preventDefault();
            locking = true;

            flipbook.turn("display", "single");

            setTimeout(() => {
                flipbook.turn("size", singleWidth, singleHeight);
                flipbook.turn("page", totalPages);
                flipbook.turn("center");
                isOpen = false;
                locking = false;
            }, 80);

            return false;
        }
    });

    /* =========================
       EVENTS
    ========================= */
    if (isMobile) {
        attachMobileEvents(flipbook);
        attachMobileNavButtons(flipbook);
    }
}





window.addEventListener("resize", () => {
    const flipbook = $("#flipbook");
    if (flipbook.data("turn")) {
        flipbook.turn("destroy");
        initFlipbook();
    }
});


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
function attachDesktopEvents(fb) {

    let isOpen = false;
    let locking = false;

    fb.off("turning").on("turning", function (e, page) {

        if (locking) {
            e.preventDefault();
            return false;
        }

        const total = fb.turn("pages");

        /* ======================
           COVER → OPEN BOOK
        ====================== */
        if (!isOpen && page > 1) {
            e.preventDefault();
            locking = true;

            fb.turn("display", "double");

            setTimeout(() => {
                fb.turn("page", page % 2 === 0 ? page - 1 : page);
                isOpen = true;
                locking = false;
            }, 80);

            return false;
        }

        /* ======================
           LAST PAGE (ODD)
        ====================== */
        if (page === total && total % 2 === 1) {
            e.preventDefault();
            locking = true;

            fb.turn("display", "single");

            setTimeout(() => {
                fb.turn("page", total);
                isOpen = false;
                locking = false;
            }, 80);

            return false;
        }

        /* ======================
           BACK TO COVER
        ====================== */
        if (isOpen && page === 1) {
            e.preventDefault();
            locking = true;

            fb.turn("display", "single");

            setTimeout(() => {
                fb.turn("page", 1);
                isOpen = false;
                locking = false;
            }, 80);

            return false;
        }
    });
}
document.getElementById("nextPage")?.addEventListener("click", () => {
    $("#flipbook").turn("next");
});

document.getElementById("prevPage")?.addEventListener("click", () => {
    $("#flipbook").turn("previous");
});



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
// function updateSideNavButtons() {

//     const flipbook = $("#flipbook");
//     if (!flipbook.length || !flipbook.data("turn")) return;

//     const page       = flipbook.turn("page");
//     const totalPages = flipbook.turn("pages");
//     const display    = flipbook.turn("display");

//     const prevBtn = document.getElementById("prevPage");
//     const nextBtn = document.getElementById("nextPage");

//     if (!prevBtn || !nextBtn) return;

//     /* =========================
//        PREV BUTTON
//     ========================= */
//     if (page <= 1) {
//         prevBtn.style.display = "none";
//     } else {
//         prevBtn.style.display = "flex";
//     }

//     /* =========================
//        NEXT BUTTON
//     ========================= */
//     if (
//         (display === "single" && page >= totalPages) ||
//         (display === "double" && page >= totalPages - 1)
//     ) {
//         nextBtn.style.display = "none";
//     } else {
//         nextBtn.style.display = "flex";
//     }
// }


function updateSideNavButtons1() {

    const flipbook = $("#flipbook");
    if (!flipbook.length || !flipbook.data("turn")) return;

    const page       = flipbook.turn("page");
    const totalPages = flipbook.turn("pages");
    const display    = flipbook.turn("display");

    const prevBtn = document.getElementById("prevPage");
    const nextBtn = document.getElementById("nextPage");

    if (!prevBtn || !nextBtn) return;

    prevBtn.style.display = page <= 1 ? "none" : "flex";

    if (
        (display === "single" && page >= totalPages) ||
        (display === "double" && page >= totalPages - 1)
    ) {
        nextBtn.style.display = "none";
    } else {
        nextBtn.style.display = "flex";
    }
}
function updateSideNavButtons() {

    const fb = $("#flipbook");
    if (!fb.length || !fb.data("turn")) return;

    const page  = fb.turn("page");
    const total = fb.turn("pages");
    const display = fb.turn("display");

    const prev = document.getElementById("prevPage");
    const next = document.getElementById("nextPage");

    if (!prev || !next) return;

    // PREV
    prev.style.display = page <= 1 ? "none" : "flex";

    // NEXT
    if (display === "single") {
        next.style.display = page >= total ? "none" : "flex";
    } else {
        next.style.display = page >= total - 1 ? "none" : "flex";
    }
}


// Run on load


// Run on every page turn


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
let lastOrientation = window.innerWidth > window.innerHeight ? "landscape" : "portrait";

window.addEventListener("resize", () => {
    const currentOrientation =
        window.innerWidth > window.innerHeight ? "landscape" : "portrait";

    if (currentOrientation !== lastOrientation) {
        location.reload(); // 🔁 FULL REFRESH
    }
});

/* ===============================
   EBOOK LOADER CONTROL
================================ */
function showEbookLoader() {
    const loader = document.getElementById("ebookLoader");
    const viewer = document.getElementById("viewer-wrapper");

    if (loader) loader.style.display = "flex";
    if (viewer) viewer.classList.remove("show");
}

function hideEbookLoader() {
    const loader = document.getElementById("ebookLoader");
    const viewer = document.getElementById("viewer-wrapper");

    if (loader) loader.style.display = "none";
    if (viewer) {
        viewer.style.display = "flex";
        requestAnimationFrame(() => viewer.classList.add("show"));
    }
}

/* ===============================
   WAIT FOR IMAGES THEN INIT
================================ */
document.addEventListener("DOMContentLoaded", () => {

    const flipbook = document.getElementById("flipbook");
    if (!flipbook) return;

    showEbookLoader();

    const images = flipbook.querySelectorAll("img");
    let loaded = 0;

    if (images.length === 0) {
        initFlipbook();
        hideEbookLoader();
        return;
    }

    images.forEach(img => {
        if (img.complete) {
            loaded++;
        } else {
            img.onload = img.onerror = () => {
                loaded++;
                if (loaded === images.length) {
                    initFlipbook();
                    hideEbookLoader();
                }
            };
        }
    });

    if (loaded === images.length) {
        initFlipbook();
        hideEbookLoader();z
    }
});


$("#prevPage").on("click", function () {
    const page = $("#flipbook").turn("page");
    if (page <= 1) return;
    $("#flipbook").turn("page", page - 1);
});
$("#nextPage").on("click", function () {
    const fb = $("#flipbook");
    const page = fb.turn("page");
    const total = fb.turn("pages");

    if (page >= total) return;
    fb.turn("page", page + 1);
});

