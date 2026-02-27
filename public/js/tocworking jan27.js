/* ==================================================
   EBOOK VIEWER - FINAL STABLE VERSION
================================================== */

(() => {
    if (window.__EBOOK_APP_LOADED__) {
        console.warn("Ebook script already loaded");
        return;
    }
    window.__EBOOK_APP_LOADED__ = true;

    /* ===============================
   GLOBAL
=============================== */

    let pageFlip = null;
    const PAGE_OFFSET = 1; // for fake first page

    let isReady = false;
    let isInitRunning = false;
    let fileUploadInitialized = false;

    let PAGE_RATIO = 700 / 440;

    /* Zoom */
    let zoomLevel = 1;
    const ZOOM_MIN = 1;
    const ZOOM_MAX = 2.5;
    const ZOOM_STEP = 0.2;

    /* Sound */
    let flipSound = null;
    let soundUnlocked = false;

    /* ===============================
   DOM READY
=============================== */

    document.addEventListener("DOMContentLoaded", () => {
        initFileUpload();

        flipSound = document.getElementById("flipSound");
        unlockSound();

        loadBook();

        setupZoomControls();
        setupFullscreen();
        setupTOC(); // ✅ ADD THIS
        updateTOCLayout();

    });

    /* ===============================
   AUDIO
=============================== */

    function unlockSound() {
        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");

        function unlock() {
            if (!flipSound || soundUnlocked) return;

            flipSound
                .play()
                .then(() => {
                    flipSound.pause();
                    flipSound.currentTime = 0;
                    soundUnlocked = true;
                })
                .catch(() => {});
        }

        // 🔥 Only unlock when nav buttons clicked
        prev?.addEventListener("click", unlock, { once: true });
        next?.addEventListener("click", unlock, { once: true });
    }

    function playSound() {
        if (!flipSound || !soundUnlocked) return;

        flipSound.currentTime = 0;
        flipSound.play().catch(() => {});
    }

    /* ===============================
   LOAD BOOK
=============================== */

    function loadBook() {
        if (isInitRunning || isReady) return;

        isInitRunning = true;

        const flipbook = document.getElementById("flipbook");
        if (!flipbook) return;

        waitForImages(() => {
            detectPageRatio();
            initFlipbook();

            isReady = true;
            isInitRunning = false;

            hideLoader();
        });
    }

    /* ===============================
   IMAGE WAIT
=============================== */

    function waitForImages(cb) {
        const imgs = document.querySelectorAll("#flipbook img");

        if (!imgs.length) return cb();

        let loaded = 0;

        imgs.forEach((img) => {
            if (img.complete) {
                loaded++;
            } else {
                img.onload = img.onerror = () => {
                    loaded++;

                    if (loaded === imgs.length) cb();
                };
            }
        });

        if (loaded === imgs.length) cb();
    }

    /* ===============================
   PAGE RATIO
=============================== */

    function detectPageRatio() {
        const img = document.querySelector(".page img");

        if (!img) return;

        PAGE_RATIO = img.naturalHeight / img.naturalWidth;
    }

    /* ===============================
   SIZE
=============================== */

    function calculateSize() {
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let w = vw * 0.9;
        let h = w * PAGE_RATIO;

        if (h > vh * 0.9) {
            h = vh * 0.9;
            w = h / PAGE_RATIO;
        }

        return {
            width: Math.floor(w),
            height: Math.floor(h),
        };
    }

    /* ===============================
   FLIPBOOK
=============================== */

    function initFlipbook() {
        if (pageFlip) return;

        const book = document.getElementById("flipbook");
        if (!book) return;

        /* Fake pages */

        let pages = book.querySelectorAll(".page");

        if (!pages[0]?.classList.contains("fake")) {
            const fake = document.createElement("div");
            fake.className = "page fake";

            book.insertBefore(fake, pages[0]);
        }

        pages = book.querySelectorAll(".page");

        if (pages.length % 2 !== 0) {
            const fake = document.createElement("div");
            fake.className = "page fake";

            book.appendChild(fake);
        }

        const { width, height } = calculateSize();

        const mobile = window.innerWidth <= 900;
        const landscape = window.innerWidth > window.innerHeight;

        pageFlip = new St.PageFlip(book, {
            width,
            height,

            size: mobile && landscape ? "stretch" : "fixed",

            showCover: false,

            // usePortrait: mobile && !landscape,
            usePortrait:
                window.innerWidth <= 768 &&
                window.innerHeight > window.innerWidth,

            autoSize: true,
            maxShadowOpacity: 0.3,

            mobileScrollSupport: true,

            flippingTime: 500,
        });

        pageFlip.loadFromHTML(book.querySelectorAll(".page"));

        setTimeout(() => {
            pageFlip.turnToPage(1);
            updateNav();
        }, 150);

        /* SOUND ONLY HERE */
        pageFlip.on("flip", () => {
            updateNav();
            playSound();
        });

        setupNav();

        window.addEventListener("resize", debounce(resizeBook, 300));
    }

    /* ===============================
   RESIZE
=============================== */

    function resizeBook() {
        if (!pageFlip) return;

        pageFlip.update(calculateSize());
    }

    function debounce(fn, d) {
        let t;

        return function () {
            clearTimeout(t);

            t = setTimeout(fn, d);
        };
    }

    /* ===============================
   NAV
=============================== */

    function setupNav() {
        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");

        if (prev) {
            prev.onclick = (e) => {
                e.preventDefault();
                pageFlip.flipPrev();
            };
        }

        if (next) {
            next.onclick = (e) => {
                e.preventDefault();
                pageFlip.flipNext();
            };
        }
    }

    function updateNav() {
        if (!pageFlip) return;

        const i = pageFlip.getCurrentPageIndex();
        const t = pageFlip.getPageCount() - 1;

        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");

        if (prev) prev.style.display = i <= 1 ? "none" : "flex";
        if (next) next.style.display = i >= t ? "none" : "flex";
    }

    /* ===============================
   ZOOM
=============================== */

    function applyZoom(v) {
        zoomLevel = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, v));

        const w = document.querySelector(".stf__wrapper");

        if (!w) return;

        w.style.transform = `scale(${zoomLevel})`;
    }

    function setupZoomControls() {
        const zi = document.getElementById("zoomIn");
        const zo = document.getElementById("zoomOut");
        const zr = document.getElementById("zoomReset");

        if (!zi || !zo || !zr) return;

        zi.onclick = () => applyZoom(zoomLevel + ZOOM_STEP);
        zo.onclick = () => applyZoom(zoomLevel - ZOOM_STEP);
        zr.onclick = () => applyZoom(1);
    }

    /* ===============================
   FULLSCREEN
=============================== */

    function setupFullscreen() {
        const btn = document.getElementById("fullscreenToggle");
        const wrap = document.getElementById("viewer-wrapper");

        if (!btn || !wrap) return;

        btn.onclick = () => {
            document.fullscreenElement
                ? document.exitFullscreen()
                : wrap.requestFullscreen();
        };
    }
    /* ===============================
   TABLE OF CONTENTS
=============================== */

    function setupTOC() {
        const tocBtn = document.getElementById("tocBtn");
        const tocPanel = document.getElementById("tocPanel");
        const closeBtn = document.getElementById("closeToc");

        if (!tocBtn || !tocPanel || !closeBtn) return;

        // Highlight current page
        if (pageFlip) {
            pageFlip.on("flip", () => {
                // const index = pageFlip.getCurrentPageIndex();
                const index = pageFlip.getCurrentPageIndex() - PAGE_OFFSET;

                document.querySelectorAll(".toc-item").forEach((item) => {
                    item.classList.remove("active");

                    if (parseInt(item.dataset.page) === index) {
                        item.classList.add("active");
                    }
                });
            });
        }

        /* Open TOC */
        tocBtn.addEventListener("click", () => {
            tocPanel.classList.add("active");
        });

        /* Close TOC */
        closeBtn.addEventListener("click", () => {
            tocPanel.classList.remove("active");
        });

        /* Jump Page */
        document.querySelectorAll(".toc-item").forEach((item) => {
            item.addEventListener("click", function () {
                const page = parseInt(this.dataset.page);

                if (pageFlip && !isNaN(page)) {
                    // pageFlip.turnToPage(page);
                    pageFlip.flip(page + PAGE_OFFSET);
                }

                // tocPanel.classList.remove("active");
            });
        });
    }

    /* ===============================
   FILE UPLOAD (SAFE)
=============================== */

    function initFileUpload() {
        if (fileUploadInitialized) return;
        fileUploadInitialized = true;

        const pdf = document.getElementById("pdfInput");
        const folder = document.getElementById("folderInput");

        const b1 = document.getElementById("selectFiles");
        const b2 = document.getElementById("selectFolder");

        const list = document.getElementById("fileList");
        const items = document.getElementById("fileItems");
        const count = document.getElementById("fileCount");

        if (!pdf && !folder) return;

        function update(files) {
            items.innerHTML = "";

            let c = 0;

            [...files].forEach((f) => {
                if (f.type === "application/pdf") {
                    c++;

                    const li = document.createElement("li");
                    li.textContent = f.name;

                    items.appendChild(li);
                }
            });

            if (c > 0) {
                list.style.display = "block";
                count.textContent = c;
            }
        }

        b1?.addEventListener("click", (e) => {
            e.preventDefault();

            pdf.value = "";
            pdf.click();
        });

        b2?.addEventListener("click", (e) => {
            e.preventDefault();

            folder.value = "";
            folder.click();
        });

        pdf?.addEventListener("change", () => update(pdf.files));
        folder?.addEventListener("change", () => update(folder.files));
    }

    /* ===============================
   LOADER
=============================== */

    function hideLoader() {
        const l = document.getElementById("ebookLoader");
        const v = document.getElementById("viewer-wrapper");

        if (l) l.remove();

        if (v) v.classList.add("show");
    }
    /* ===============================
   ORIENTATION AUTO REFRESH
=============================== */

    let lastOrientation =
        window.innerWidth > window.innerHeight ? "landscape" : "portrait";

    window.addEventListener("resize", () => {
        // Only for mobile
        if (window.innerWidth > 900) return;

        const current =
            window.innerWidth > window.innerHeight ? "landscape" : "portrait";

        if (current !== lastOrientation) {
            lastOrientation = current;

            // Small delay for stability
            // setTimeout(() => {
            //     location.reload();
            // }, 300);
            setTimeout(() => {
                if (pageFlip) {
                    pageFlip.update(calculateSize());
                }
            }, 300);
        }
    });
    function updateTOCLayout() {
        const items = document.querySelectorAll("#tocList .toc-item");
        if (!items.length) return;

        const isMobile = window.innerWidth <= 768;
        const isPortrait = window.innerHeight > window.innerWidth;

        const singleMode = isMobile && isPortrait;

        let html = "";
        let i = 0;

        while (i < items.length) {
            const num = parseInt(items[i].dataset.num);
            const page = parseInt(items[i].dataset.page);

            /* SINGLE MODE (Mobile Portrait) */
            if (singleMode || i === 0 || i === items.length - 1) {
                html += `
                <div class="toc-item" data-page="${page}">
                    Page ${String(num).padStart(3, "0")}
                </div>
            `;

                i++;
            } else {

            /* DOUBLE MODE */
                const num2 = num + 1;

                html += `
                <div class="toc-item" data-page="${page}">
                    Page ${String(num).padStart(3, "0")}
                    -
                    ${String(num2).padStart(3, "0")}
                </div>
            `;

                i += 2;
            }
        }

        document.getElementById("tocList").innerHTML = html;

        // Rebind click
        setupTOC();
    }
})();
/* =====================================
   SHARE LINK (GLOBAL)
===================================== */
function openShareModal(ebookId) {
    fetch(`/ebook/share/${ebookId}`)
        .then((res) => res.json())
        .then((data) => {
            document.getElementById("shareLinkInput").value = data.publicLink;
            new bootstrap.Modal(document.getElementById("shareModal")).show();
        })
        .catch(() => alert("Failed to generate share link"));
}

function copyShareLink() {
    const input = document.getElementById("shareLinkInput");
    input.select();
    document.execCommand("copy");
    alert("Link copied!");
}
