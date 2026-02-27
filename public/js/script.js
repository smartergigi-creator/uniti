/* ============================
   AUTO LOGIN CHECK
============================ */

/* ============================
   PAGE ACCESS CONTROL
============================ */

/* ============================
   SAFE AUTH CHECK
============================ */

/* ============================
   AUTH GUARD (STABLE)
============================ */

// Prevent BFCache from showing protected pages after logout
window.addEventListener("pageshow", (event) => {
    const nav =
        performance.getEntriesByType("navigation")[0]?.type || "navigate";

    if (event.persisted || nav === "back_forward") {
        window.location.reload();
    }
});

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
    let firstRealPageIndex = 1;
    let lastRealPageIndex = 1;
    let lastNavigablePageIndex = 1;

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
        if (!flipbook) {
            isInitRunning = false;
            return;
        }

        const initWhenReady = () => {
            waitForImages(() => {
                detectPageRatio();
                initFlipbook();

                isReady = true;
                isInitRunning = false;

                hideLoader();
            });
        };

        if (window.__PDF_PAGES_READY_PROMISE__ instanceof Promise) {
            window.__PDF_PAGES_READY_PROMISE__
                .then(initWhenReady)
                .catch(() => {
                    isInitRunning = false;
                    hideLoader();
                });
            return;
        }

        initWhenReady();
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
        const mobile = vw <= 900;
        const landscape = vw > vh;

        const viewer = document.getElementById("viewer-wrapper");
        const rect = viewer?.getBoundingClientRect();

        const availableW = Math.max(220, Math.floor((rect?.width || vw) - (mobile ? 12 : 48)));
        const availableH = Math.max(
            220,
            Math.floor((rect?.height || vh) - (mobile ? (landscape ? 10 : 20) : 36)),
        );

        let w = availableW * (mobile && landscape ? 0.98 : 0.92);
        let h = w * PAGE_RATIO;
        const maxH = availableH * (mobile && landscape ? 0.96 : 0.9);

        if (h > maxH) {
            h = maxH;
            w = h / PAGE_RATIO;
        }

        return {
            width: Math.floor(w),
            height: Math.floor(h),
        };
    }

    /* ===============================openShareModal
   FLIPBOOK
=============================== */
    function refreshRealPageBounds(book) {
        const allPages = Array.from(book.querySelectorAll(".page"));
        const firstReal = allPages.findIndex((p) => !p.classList.contains("fake"));
        const lastReal = allPages.length - 1 - [...allPages].reverse()
            .findIndex((p) => !p.classList.contains("fake"));

        firstRealPageIndex = firstReal >= 0 ? firstReal : 0;
        lastRealPageIndex = lastReal >= firstRealPageIndex ? lastReal : firstRealPageIndex;
        lastNavigablePageIndex = lastRealPageIndex;
    }

    function refreshLoadedPageBounds(book) {
        const allPages = Array.from(book.querySelectorAll(".page"));
        const loadedRealIndexes = allPages
            .map((page, index) => ({ page, index }))
            .filter(({ page }) => !page.classList.contains("fake") && page.dataset.loaded === "1")
            .map(({ index }) => index);

        if (!loadedRealIndexes.length) {
            lastNavigablePageIndex = firstRealPageIndex;
            return;
        }

        const lastLoaded = loadedRealIndexes[loadedRealIndexes.length - 1];
        lastNavigablePageIndex = Math.min(lastRealPageIndex, lastLoaded);
    }

    function initFlipbook() {
        if (pageFlip) return;

        const book = document.getElementById("flipbook");
        if (!book) return;

        const mobile = window.innerWidth <= 900;
        const landscape = window.innerWidth > window.innerHeight;
        const useSingleMode = mobile && !landscape;

        /* Fake pages */

        let pages = book.querySelectorAll(".page");

        if (!useSingleMode && !pages[0]?.classList.contains("fake")) {
            const fake = document.createElement("div");
            fake.className = "page fake";

            book.insertBefore(fake, pages[0]);
        }

        pages = book.querySelectorAll(".page");

        if (!useSingleMode && pages.length % 2 !== 0) {
            const fake = document.createElement("div");
            fake.className = "page fake";

            book.appendChild(fake);
        }

        refreshRealPageBounds(book);
        refreshLoadedPageBounds(book);

        const { width, height } = calculateSize();

        pageFlip = new St.PageFlip(book, {
            width,
            height,

            size: "fixed",

            showCover: false,

            usePortrait: useSingleMode,

            autoSize: false,
            maxShadowOpacity: 0.3,

            mobileScrollSupport: true,

            flippingTime: 500,
        });

        pageFlip.loadFromHTML(book.querySelectorAll(".page"));

        setTimeout(() => {
            pageFlip.turnToPage(firstRealPageIndex);
            updateNav();
        }, 150);

        /* SOUND ONLY HERE */
        pageFlip.on("flip", () => {
            const current = pageFlip.getCurrentPageIndex();
            const clamped = Math.min(
                Math.max(current, firstRealPageIndex),
                lastNavigablePageIndex,
            );

            if (current !== clamped) {
                pageFlip.turnToPage(clamped);
            }

            updateNav();
            playSound();
        });

        setupNav();
        window.__PDF_PAGE_RENDERED_HOOK__ = () => {
            const liveBook = document.getElementById("flipbook");
            if (!liveBook || !pageFlip) return;

            refreshLoadedPageBounds(liveBook);
            updateNav();
        };

        window.addEventListener("resize", debounce(resizeBook, 300));
    }

    /* ===============================
   RESIZE
=============================== */

    function resizeBook() {
        if (!pageFlip) return;

        const currentPage = pageFlip.getCurrentPageIndex();
        const clampedPage = Math.min(
            Math.max(currentPage, firstRealPageIndex),
            lastNavigablePageIndex,
        );
        pageFlip.update(calculateSize());
        pageFlip.turnToPage(clampedPage);
        applyZoom(zoomLevel);
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
                if (!pageFlip) return;

                const current = pageFlip.getCurrentPageIndex();
                if (current <= firstRealPageIndex) return;

                const target = current - 1;
                if (target <= firstRealPageIndex) {
                    pageFlip.turnToPage(firstRealPageIndex);
                    updateNav();
                    return;
                }

                pageFlip.flipPrev();
            };
        }

        if (next) {
            next.onclick = (e) => {
                e.preventDefault();
                if (!pageFlip) return;

                const current = pageFlip.getCurrentPageIndex();
                if (current >= lastNavigablePageIndex) return;

                const target = current + 1;
                if (target >= lastNavigablePageIndex) {
                    pageFlip.turnToPage(lastNavigablePageIndex);
                    updateNav();
                    return;
                }

                pageFlip.flipNext();
            };
        }
    }

    function updateNav() {
        if (!pageFlip) return;

        const i = pageFlip.getCurrentPageIndex();
        const t = lastNavigablePageIndex;

        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");

        if (prev) prev.style.display = i <= firstRealPageIndex ? "none" : "flex";
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

        const syncAfterFullscreen = () => {
            const isViewerFullscreen = document.fullscreenElement === wrap;
            document.body.classList.toggle("fullscreen-active", isViewerFullscreen);

            // Browser applies fullscreen layout async, so wait one frame.
            setTimeout(() => {
                resizeBook();
            }, 80);
        };

        document.addEventListener("fullscreenchange", syncAfterFullscreen);
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
            if (!list || !items || !count) return;
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
    //     function initFileUpload() {

    //     if (fileUploadInitialized) return;
    //     fileUploadInitialized = true;

    //     const dropZone = document.getElementById("dropzone");

    //     const pdf = document.getElementById("pdfInput");
    //     const folder = document.getElementById("folderInput");

    //     const b1 = document.getElementById("selectFiles");
    //     const b2 = document.getElementById("selectFolder");

    //     const list = document.getElementById("fileList");
    //     const items = document.getElementById("fileItems");
    //     const count = document.getElementById("fileCount");

    //     if (!pdf || !dropZone) return;

    //     let selectedFiles = [];

    //     /* =====================
    //        BUTTON CLICK
    //     ===================== */

    //     b1?.addEventListener("click", (e) => {
    //         e.preventDefault();
    //         pdf.value = "";
    //         pdf.click();
    //     });

    //     b2?.addEventListener("click", (e) => {
    //         e.preventDefault();
    //         folder.value = "";
    //         folder.click();
    //     });

    //     /* =====================
    //        INPUT CHANGE
    //     ===================== */

    //     pdf?.addEventListener("change", (e) => {
    //         handleFiles(e.target.files);
    //     });

    //     folder?.addEventListener("change", (e) => {
    //         handleFiles(e.target.files);
    //     });

    //     /* =====================
    //        DRAG & DROP
    //     ===================== */

    //     dropZone.addEventListener("dragover", (e) => {
    //         e.preventDefault();
    //         dropZone.classList.add("dragover");
    //     });

    //     dropZone.addEventListener("dragleave", () => {
    //         dropZone.classList.remove("dragover");
    //     });

    //     dropZone.addEventListener("drop", (e) => {

    //         e.preventDefault();
    //         dropZone.classList.remove("dragover");

    //         handleFiles(e.dataTransfer.files);
    //     });

    //     /* =====================
    //        MAIN HANDLER
    //     ===================== */

    //     function handleFiles(files) {

    //         for (let file of files) {

    //             if (file.type !== "application/pdf") {
    //                 alert("Only PDF files allowed!");
    //                 continue;
    //             }

    //             if (selectedFiles.some(f => f.name === file.name)) {
    //                 continue;
    //             }

    //             selectedFiles.push(file);
    //         }

    //         updateList();
    //         syncInput();
    //     }

    //     /* =====================
    //        UPDATE UI
    //     ===================== */

    //     function updateList() {

    //         items.innerHTML = "";

    //         selectedFiles.forEach((file, index) => {

    //             const li = document.createElement("li");

    //             li.innerHTML = `
    //                 ${file.name}
    //                 <span style="color:red;cursor:pointer"
    //                       data-index="${index}"> ❌ </span>
    //             `;

    //             items.appendChild(li);
    //         });

    //         count.textContent = selectedFiles.length;

    //         list.style.display =
    //             selectedFiles.length ? "block" : "none";
    //     }

    //     /* =====================
    //        REMOVE FILE
    //     ===================== */

    //     items.addEventListener("click", (e) => {

    //         if (e.target.dataset.index !== undefined) {

    //             selectedFiles.splice(e.target.dataset.index, 1);

    //             updateList();
    //             syncInput();
    //         }
    //     });

    //     /* =====================
    //        SYNC TO INPUT
    //     ===================== */

    //     function syncInput() {

    //         const dt = new DataTransfer();

    //         selectedFiles.forEach(f => dt.items.add(f));

    //         pdf.files = dt.files;
    //         folder.files = dt.files;
    //     }
    // }

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

function copyShareLink() {
    const input = document.getElementById("shareLinkInput");
    input.select();
    document.execCommand("copy");
    alert("Link copied!");
}
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
        setTimeout(() => {
            location.reload();
        }, 300);
    }
});

/* ============================
   UPLOAD / STORE EBOOK (SESSION AUTH)
============================ */

// document.addEventListener("DOMContentLoaded", function () {
//     const uploadForm = document.getElementById("uploadForm");

//     if (!uploadForm) return;

//     uploadForm.addEventListener("submit", function (e) {
//         e.preventDefault();

//         const formData = new FormData(this);

//         const token = document
//             .querySelector('meta[name="csrf-token"]')
//             .getAttribute("content");

//         fetch("/ebooks/upload", {
//             method: "POST",
//             credentials: "same-origin", // 🔥 VERY IMPORTANT
//             headers: {
//                 "X-CSRF-TOKEN": token,
//                 Accept: "application/json",
//             },
//             body: formData,
//         })
//             .then((res) => res.json())
//             .then((data) => {
//                 console.log("UPLOAD:", data);

//                 if (data.status) {
//                     showMessage("success", data.message || "Upload successful");

//                     setTimeout(() => location.reload(), 1200);
//                 } else {
//                     showMessage("error", data.message || "Upload failed");
//                 }
//             })
//             .catch((err) => {
//                 console.error("UPLOAD ERROR:", err);
//                 showMessage("error", "Server error");
//             });
//     });
// });
document.addEventListener("DOMContentLoaded", function () {
    const uploadForm = document.getElementById("uploadForm");
    const uploadStatus = document.getElementById("uploadStatus");
    const pdfInput = document.getElementById("pdfInput");
    const folderInput = document.getElementById("folderInput");

    // Home page has its own dedicated upload handler in blade script.
    if (document.body.classList.contains("ebook-home")) return;

    if (!uploadForm) return;

    uploadForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(uploadForm);
        const selectedFiles = [];

        if (pdfInput && pdfInput.files?.length) {
            selectedFiles.push(...Array.from(pdfInput.files));
        }
        if (folderInput && folderInput.files?.length) {
            selectedFiles.push(...Array.from(folderInput.files));
        }

        const pdfFiles = selectedFiles.filter((file) => file.type === "application/pdf");
        if (!pdfFiles.length) {
            showMessage("error", "Please select at least one PDF file.");
            return;
        }

        formData.delete("pdfs[]");
        pdfFiles.forEach((file) => {
            formData.append("pdfs[]", file);
        });

        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        // ✅ SHOW LOADING
        if (uploadStatus) {
            uploadStatus.style.display = "flex";
        }

        // ❌ Disable submit button
        const btn = uploadForm.querySelector("button[type='submit']");
        if (btn) btn.disabled = true;

        fetch("/ebooks/upload", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "X-CSRF-TOKEN": token || "",
                Accept: "application/json",
            },
            body: formData,
        })
            .then((res) => res.json())
            .then((data) => {
                console.log("UPLOAD:", data);

                // ❌ HIDE LOADING
                if (uploadStatus) {
                    uploadStatus.style.display = "none";
                }

                if (btn) btn.disabled = false;

                if (data.status) {
                    showMessage("success", data.message || "Upload successful");

                    setTimeout(() => location.reload(), 1200);
                } else {
                    showMessage("error", data.message || "Upload failed");
                }
            })
            .catch((err) => {
                console.error("UPLOAD ERROR:", err);

                // ❌ HIDE LOADING
                if (uploadStatus) {
                    uploadStatus.style.display = "none";
                }

                if (btn) btn.disabled = false;

                showMessage("error", "Server error");
            });
    });
});

function showMessage(type, msg) {
    let box = document.getElementById("messageBox");

    if (!box) {
        alert(msg);
        return;
    }

    box.className =
        "alert alert-" + (type === "success" ? "success" : "danger");

    box.innerText = msg;
    box.style.display = "block";

    box.scrollIntoView({
        behavior: "smooth",
        block: "center",
    });

    setTimeout(() => {
        box.style.display = "none";
    }, 4000);
}

/* ============================
   DELETE EBOOK (SESSION AUTH)
============================ */

function deleteEbook(id) {
    if (!confirm("Are you sure you want to delete this ebook?")) return;

    fetch("/ebook/delete/" + id, {
        method: "DELETE",
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((res) => res.json())
        .then((data) => {
            console.log("DELETE:", data);

            if (data.status) {
                showMessage("success", data.message || "Deleted");

                setTimeout(() => location.reload(), 1200);
            } else {
                showMessage("error", data.message || "Delete failed");
            }
        })
        .catch(() => {
            showMessage("error", "Server error");
        });
}

/* ============================
   SHARE LINK (SESSION AUTH)
============================ */

function openShareModal(id) {
    fetch("/ebooks/share/" + id, {
        method: "POST", // ✅ Must be POST
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            Accept: "application/json",
        },
    })
        .then((res) => res.json())
        .then((data) => {
            console.log("SHARE:", data);

            if (!data.status) {
                alert(data.message || "Access Denied");
                return;
            }

            // Set link
            document.getElementById("shareLinkInput").value = data.publicLink;

            // Open modal
            let modal = new bootstrap.Modal(
                document.getElementById("shareModal"),
            );

            modal.show();
        })
        .catch(() => {
            alert("Server error");
        });
}

/* ============================
   SHARE LINK
============================ */

/* ============================
   COPY SHARE LINK
============================ */
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtns = document.querySelectorAll(".sidebar-toggle-desktop");

    toggleBtns.forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            document.body.classList.toggle("sidebar-collapsed");
        });
    });

    // Keep desktop collapse off on mobile
    if (window.innerWidth <= 768) {
        document.body.classList.remove("sidebar-collapsed");
    } else {
    }
});

// toggle sidebar mobileview
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtns = document.querySelectorAll(".sidebar-toggle");
    const sidebar = document.querySelector(".sidebar");

    if (!toggleBtns.length || !sidebar) return;

    // Always start hidden on mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.remove("show");
    }

    // Toggle
    toggleBtns.forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            sidebar.classList.toggle("show");
        });
    });

    // Click outside close
    document.addEventListener("click", function (e) {
        if (
            window.innerWidth <= 768 &&
            sidebar.classList.contains("show") &&
            !sidebar.contains(e.target)
        ) {
            sidebar.classList.remove("show");
        }
    });

    // On resize to mobile, ensure desktop-collapsed state doesn't fight layout
    window.addEventListener("resize", function () {
        if (window.innerWidth <= 768) {
            document.body.classList.remove("sidebar-collapsed");
        } else {
            sidebar.classList.remove("show");
        }
    });
});
