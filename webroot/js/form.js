const AjaxForms = (() => {
    const STATE_ATTR = "data-ajax-bound";

    function init(root = document) {
        const forms = root.querySelectorAll('form[data-ajax="true"]');
        forms.forEach((form) => bind(form));
    }

    function bind(form) {
        if (form.getAttribute(STATE_ATTR) === "1") return;
        form.setAttribute(STATE_ATTR, "1");

        form.addEventListener("submit", (e) => {
            e.preventDefault();
            submit(form);
        });
    }

    async function submit(form) {
        const ui = getUI(form);
        ui.showLoading();

        const recaptchaAvailable =
            typeof grecaptcha !== "undefined" && typeof grecaptcha.getResponse === "function";
        const payload = buildPayload(form, recaptchaAvailable);

        const checkName = form.getAttribute("data-checkData");
        if (checkName && typeof window[checkName] === "function") {
            const check = window[checkName](payload);
            if (check && typeof check === "object" && check.status === false) {
                resetRecaptcha(recaptchaAvailable);
                ui.showError(check.messages || INTERNAL_ERROR_MSG);
                ui.restore();
                return;
            }
        }

        const action = form.getAttribute("action") || "";
        const isFormData = payload instanceof FormData;

        const headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };

        if (!isFormData) {
            headers["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
        }

        try {
            const res = await fetch(action, {
                method: "POST",
                credentials: "same-origin",
                headers,
                body: isFormData ? payload : toUrlEncoded(payload)
            });

            const json = await readJsonSafe(res);

            if (!json || typeof json !== "object") {
                resetRecaptcha(recaptchaAvailable);
                ui.showError(INTERNAL_ERROR_MSG);
                ui.restore();
                return;
            }

            if (json.status === true) {
                const successPref = form.getAttribute("data-success-msg");
                if (successPref === null || successPref === "true") {
                    ui.showSuccess(json.messages || SUCCESS_MSG);
                }

                const cbName = form.getAttribute("data-callback-function");
                if (cbName && typeof window[cbName] === "function") {
                    window[cbName](payload, json);
                }

                const redirectUrl = form.getAttribute("data-redirect-url");
                if (redirectUrl) {
                    redirectWithCacheBust(redirectUrl);
                    return;
                }

                ui.restore();
                return;
            }

            resetRecaptcha(recaptchaAvailable);

            if (json.status === false) {
                ui.showError(json.messages || INTERNAL_ERROR_MSG);
                ui.restore();
                return;
            }

            ui.showError(INTERNAL_ERROR_MSG);
            ui.restore();
        } catch (e) {
            resetRecaptcha(recaptchaAvailable);
            ui.showError(INTERNAL_ERROR_MSG);
            ui.restore();
        }
    }

    function getUI(form) {
        const submitEl = form.querySelector("input[type='submit'], button[type='submit']");
        const submitSnapshot = submitEl
            ? submitEl.tagName === "INPUT"
                ? submitEl.value
                : submitEl.innerHTML
            : "";

        const msgEl = resolveMessageElement(form);

        function setSubmitLoading() {
            if (!submitEl) return;
            if (submitEl.tagName === "INPUT") submitEl.value = LOADING_MSG + "...";
            else submitEl.innerHTML = LOADING_MSG + "...";
            submitEl.setAttribute("disabled", "disabled");
        }

        function restoreSubmit() {
            if (!submitEl) return;
            if (submitEl.tagName === "INPUT") submitEl.value = submitSnapshot;
            else submitEl.innerHTML = submitSnapshot;
            submitEl.removeAttribute("disabled");
        }

        function setMsg(html) {
            if (!msgEl) return;
            msgEl.innerHTML = html;
            msgEl.style.display = "";
        }

        function buildAlert(type, title, bodyHtml) {
            const cls = type === "success" ? "alert-success" : type === "info" ? "alert-info" : "alert-danger";
            const icon = type === "success" ? "fas fa-check" : type === "info" ? "fas fa-circle-notch" : "fas fa-times";
            return (
                '<div class="alert ' +
                cls +
                ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                "</button>" +
                '<h5 class="mb-1"><i class="' +
                icon +
                ' mr-2"></i>' +
                escapeHtml(String(title)) +
                "</h5>" +
                bodyHtml +
                "</div>"
            );
        }

        return {
            showLoading() {
                setMsg(
                    buildAlert(
                        "info",
                        LOADING_MSG,
                        '<div class="mb-0">' + escapeHtml(String(LOADING_MSG)) + " ...</div>"
                    )
                );
                setSubmitLoading();
            },
            showSuccess(messages) {
                setMsg(buildAlert("success", SUCCESS_MSG, formatMessages(messages)));
            },
            showError(messages) {
                setMsg(buildAlert("danger", ERROR_MSG, formatMessages(messages)));
            },
            restore() {
                restoreSubmit();
            }
        };
    }

    function resolveMessageElement(form) {
        const customDiv = form.getAttribute("data-custom-div-msg");
        if (customDiv && customDiv.length > 0) {
            return document.querySelector(customDiv);
        }

        let el = form.querySelector(".ajax-msg");
        if (!el) {
            el = document.createElement("div");
            el.className = "ajax-msg";
            form.insertBefore(el, form.firstChild);
        }
        return el;
    }

    function formatMessages(messages) {
        if (Array.isArray(messages)) {
            if (messages.length === 1) {
                return '<div class="mb-0">' + escapeHtml(String(messages[0])) + "</div>";
            }
            return (
                '<ul class="mb-0 pl-3">' +
                messages.map((m) => "<li>" + escapeHtml(String(m)) + "</li>").join("") +
                "</ul>"
            );
        }

        if (typeof messages === "string" || typeof messages === "number" || typeof messages === "boolean") {
            return '<div class="mb-0">' + escapeHtml(String(messages)) + "</div>";
        }

        return '<div class="mb-0">' + escapeHtml(String(INTERNAL_ERROR_MSG)) + "</div>";
    }

    function buildPayload(form, recaptchaAvailable) {
        const customFnName = form.getAttribute("data-custom-function");
        const upload = form.getAttribute("data-upload-image") === "true";

        if (customFnName && typeof window[customFnName] === "function") {
            const data = window[customFnName](form) || {};
            ensureCsrf(data, form);
            if (recaptchaAvailable && data.recaptcha === undefined) data.recaptcha = grecaptcha.getResponse();
            return data;
        }

        if (upload) {
            const fd = new FormData(form);
            ensureCsrf(fd, form);
            if (recaptchaAvailable) fd.set("recaptcha", grecaptcha.getResponse());
            return fd;
        }

        const obj = formToObject(form);
        ensureCsrf(obj, form);
        if (recaptchaAvailable) obj.recaptcha = grecaptcha.getResponse();
        return obj;
    }

    function formToObject(form) {
        const fd = new FormData(form);

        form.querySelectorAll('input[type="checkbox"][name]').forEach((cb) => {
            if (!cb.checked && !fd.has(cb.name)) fd.append(cb.name, "off");
        });

        const obj = {};
        const names = new Set();

        for (const [name] of fd.entries()) names.add(name);

        names.forEach((name) => {
            const input = form.querySelector(`input[name="${CSS.escape(name)}"]`);
            const textarea = form.querySelector(`textarea[name="${CSS.escape(name)}"]`);
            const select = form.querySelector(`select[name="${CSS.escape(name)}"]`);

            if (textarea) {
                if (typeof tinymce !== "undefined" && textarea.id && tinymce.get(textarea.id)) {
                    obj[name] = tinymce.get(textarea.id).getContent();
                } else {
                    obj[name] = textarea.value;
                }
                return;
            }

            if (select) {
                obj[name] = select.value;
                return;
            }

            if (!input) return;

            const type = (input.getAttribute("type") || "text").toLowerCase();

            if (type === "radio") {
                const checked = form.querySelector(`input[name="${CSS.escape(name)}"][type="radio"]:checked`);
                obj[name] = checked ? checked.value : "";
                return;
            }

            if (type === "checkbox") {
                const checked = form.querySelector(`input[name="${CSS.escape(name)}"][type="checkbox"]:checked`);
                obj[name] = checked ? 1 : 0;
                return;
            }

            obj[name] = input.value;
        });

        return obj;
    }

    function ensureCsrf(target, form) {
        if (!form) return;

        const csrfInput =
            form.querySelector('input[name="_csrfToken"]') ||
            form.querySelector('input[name="data[_Token][key]"]');

        if (!csrfInput) return;

        const token = csrfInput.value;

        if (target instanceof FormData) {
            if (!target.has("_csrfToken") && !target.has("data[_Token][key]")) {
                target.set(csrfInput.name, token);
            }
            return;
        }

        if (target && target[csrfInput.name] === undefined) {
            target[csrfInput.name] = token;
        }
    }

    function toUrlEncoded(obj) {
        const params = new URLSearchParams();
        Object.keys(obj || {}).forEach((k) => {
            const v = obj[k];
            if (Array.isArray(v)) v.forEach((vv) => params.append(k, String(vv)));
            else params.append(k, v === null || v === undefined ? "" : String(v));
        });
        return params.toString();
    }

    async function readJsonSafe(res) {
        const text = await res.text();

        if (!text) {
            if (res.status === 403) return { status: false, messages: FORBIDDEN_ERROR_MSG };
            if (res.status === 400) return { status: false, messages: INVALID_REQUEST_MSG || INTERNAL_ERROR_MSG };
            return null;
        }

        try {
            const json = JSON.parse(text);

            if (res.status === 403 && json && json.status === undefined) {
                return { status: false, messages: FORBIDDEN_ERROR_MSG };
            }

            if (res.status === 400 && json && json.status === undefined) {
                return { status: false, messages: INVALID_REQUEST_MSG || INTERNAL_ERROR_MSG };
            }

            return json;
        } catch (e) {
            if (res.status === 403) return { status: false, messages: FORBIDDEN_ERROR_MSG };
            if (res.status === 400) return { status: false, messages: INVALID_REQUEST_MSG || INTERNAL_ERROR_MSG };
            return null;
        }
    }

    function resetRecaptcha(enabled) {
        if (!enabled) return;
        if (typeof grecaptcha !== "undefined" && typeof grecaptcha.reset === "function") {
            grecaptcha.reset();
        }
    }

    function redirectWithCacheBust(url) {
        const sep = url.includes("?") ? "&" : "?";
        window.location.href = url + sep + "no-cache=" + Date.now();
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    return { init };
})();

AjaxForms.init();
