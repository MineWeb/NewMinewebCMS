(function () {
    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function on(el, evt, handler, opts) {
        if (!el) return;
        el.addEventListener(evt, handler, opts || false);
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? String(meta.getAttribute("content") || "") : "";
    }

    function postUrlEncoded(url, data) {
        const body = new URLSearchParams();
        Object.keys(data || {}).forEach(function (k) {
            const v = data[k];
            body.append(k, v === null || v === undefined ? "" : String(v));
        });

        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-Token": getCsrfToken()
            },
            body: body.toString()
        });
    }

    function initCaptchaReload() {
        const btn = qs("#reload");
        const img = qs("#captcha_image");
        if (!btn || !img) return;

        on(btn, "click", function (e) {
            e.preventDefault();
            const src = String(img.getAttribute("src") || "");
            const sep = src.indexOf("?") === -1 ? "?" : "&";
            img.setAttribute("src", src + sep + Math.random());
        });
    }

    function initBootstrapCarouselTimer() {
        const carousel = qs("#myCarousel");
        if (!carousel) return;

        const bar = qs(".transition-timer-carousel-progress-bar", carousel);
        if (bar) bar.style.width = "100%";

        on(carousel, "slide.bs.carousel", function () {
            const b = qs(".transition-timer-carousel-progress-bar", carousel);
            if (!b) return;
            b.classList.remove("animate");
            b.style.width = "0%";
        });

        on(carousel, "slid.bs.carousel", function () {
            const b = qs(".transition-timer-carousel-progress-bar", carousel);
            if (!b) return;
            b.classList.add("animate");
            b.style.width = "100%";
        });
    }

    function initHomePaginationFallback() {
        const ul = qs("ul#items");
        if (!ul) return;

        const step = 3;
        const items = qsa(":scope > li", ul);
        if (items.length <= step) return;

        const pager = qs("ol#pagination");
        if (!pager) return;

        let page = 0;
        const pages = Math.ceil(items.length / step);

        function render() {
            const start = page * step;
            const end = start + step;

            items.forEach(function (li, idx) {
                li.style.display = idx >= start && idx < end ? "" : "none";
            });

            pager.innerHTML = "";
            for (let i = 0; i < pages; i++) {
                const li = document.createElement("li");
                li.style.display = "inline-block";
                li.style.marginRight = "6px";

                const a = document.createElement("a");
                a.href = "#";
                a.textContent = String(i + 1);
                if (i === page) a.style.fontWeight = "700";

                on(a, "click", function (e) {
                    e.preventDefault();
                    page = i;
                    render();
                });

                li.appendChild(a);
                pager.appendChild(li);
            }
        }

        render();
    }

    function parseLikeCount(btn) {
        const txt = (btn.textContent || "").trim();
        const n = parseInt(txt, 10);
        return Number.isFinite(n) ? n : 0;
    }

    function setLikeHtml(btn, count) {
        btn.innerHTML = String(count) + ' <i class="fa fa-thumbs-up"></i>';
    }

    function getLikeUrls() {
        const likeUrl = typeof window.LIKE_URL !== "undefined" ? String(window.LIKE_URL || "") : "";
        const dislikeUrl = typeof window.DISLIKE_URL !== "undefined" ? String(window.DISLIKE_URL || "") : "";

        if (!likeUrl && typeof window.LIKE_URL === "undefined" && typeof LIKE_URL !== "undefined") {
            return { likeUrl: String(LIKE_URL || ""), dislikeUrl: String(DISLIKE_URL || "") };
        }

        return { likeUrl: likeUrl, dislikeUrl: dislikeUrl };
    }

    function initLikes() {
        const urls = getLikeUrls();
        const likeUrl = urls.likeUrl;
        const dislikeUrl = urls.dislikeUrl;

        if (!likeUrl || !dislikeUrl) return;

        document.addEventListener("click", function (e) {
            const btn = e.target && e.target.closest ? e.target.closest(".like") : null;
            if (!btn) return;

            if (btn.disabled === true) return;
            if (btn.hasAttribute("disabled")) return;
            if (btn.classList.contains("disabled")) return;

            const id = btn.getAttribute("id") || "";
            if (!id) return;

            const active = btn.classList.contains("active");
            const current = parseLikeCount(btn);

            if (active) {
                btn.classList.remove("active");
                setLikeHtml(btn, Math.max(0, current - 1));
                postUrlEncoded(dislikeUrl, { id: id }).catch(function () {});
            } else {
                btn.classList.add("active");
                setLikeHtml(btn, current + 1);
                postUrlEncoded(likeUrl, { id: id }).catch(function () {});
            }
        });
    }

    function isNumberValue(v) {
        if (v === "" || v === null || v === undefined) return false;
        return Number.isFinite(Number(v));
    }

    function initTypedNumbers() {
        qsa('input[data-type="numbers"]').forEach(function (input) {
            on(input, "keyup", function () {
                const v = input.value;
                if (v === "") return;
                if (!isNumberValue(v)) {
                    input.value = v.slice(0, -1);
                }
            });
        });
    }

    function initAutotab() {
        qsa("input[maxlength][tabindex]").forEach(function (input) {
            on(input, "keyup", function () {
                const max = parseInt(input.getAttribute("maxlength") || "0", 10);
                if (!max) return;

                if (input.value.length >= max) {
                    const nextIndex = parseInt(input.getAttribute("tabindex") || "0", 10) + 1;
                    const next = qs('input[maxlength][tabindex="' + CSS.escape(String(nextIndex)) + '"]');
                    if (next) next.focus();
                }
            });
        });
    }

    function initNavbarCollapseMaxHeight() {
        const navCollapse = qs(".navbar-collapse");
        const navHeader = qs(".navbar-header");
        if (!navCollapse || !navHeader) return;

        function apply() {
            const h = Math.max(0, (window.innerHeight - 130) - navHeader.offsetHeight);
            navCollapse.style.maxHeight = String(h) + "px";
        }

        apply();
        on(window, "resize", apply);
    }

    document.addEventListener("DOMContentLoaded", function () {
        initCaptchaReload();
        initBootstrapCarouselTimer();
        initHomePaginationFallback();
        initLikes();
        initTypedNumbers();
        initAutotab();
        initNavbarCollapseMaxHeight();
    });
})();
