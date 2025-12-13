window.addEventListener("load", function() {
    function fadeInElement(el, duration, callback) {
        if (!el) return
        el.style.opacity = 0
        el.style.display = "block"
        let last = performance.now()
        function tick(now) {
            let dt = now - last
            el.style.opacity = parseFloat(el.style.opacity) + dt / duration
            last = now
            if (parseFloat(el.style.opacity) < 1) {
                requestAnimationFrame(tick)
            } else if (callback) {
                callback()
            }
        }
        requestAnimationFrame(tick)
    }

    function fadeOutElement(el, duration, callback) {
        if (!el) return
        el.style.opacity = 1
        let last = performance.now()
        function tick(now) {
            let dt = now - last
            el.style.opacity = parseFloat(el.style.opacity) - dt / duration
            last = now
            if (parseFloat(el.style.opacity) > 0) {
                requestAnimationFrame(tick)
            } else {
                el.style.display = "none"
                if (callback) callback()
            }
        }
        requestAnimationFrame(tick)
    }

    function fetchWithProgress(url, data, csrfToken, onProgress) {
        return new Promise(function(resolve, reject) {
            let xhr = new XMLHttpRequest()
            xhr.open("POST", url)

            if (csrfToken) {
                xhr.setRequestHeader("X-CSRF-Token", csrfToken)
            }

            xhr.upload.onprogress = function(e) {
                let percent
                if (e.lengthComputable) {
                    percent = e.loaded / e.total * 100
                } else {
                    percent = e.loaded / 1000000 * 100
                }
                if (onProgress) onProgress(percent)
            }

            xhr.onload = function() {
                resolve(new Response(xhr.responseText, { status: xhr.status }))
            }

            xhr.onerror = function() {
                reject(xhr)
            }

            let formData = new FormData()
            for (let k in data) {
                if (Object.prototype.hasOwnProperty.call(data, k)) {
                    formData.append(k, data[k])
                }
            }

            xhr.send(formData)
        })
    }

    function getCsrfToken() {
        let el = document.querySelector('input[name="_csrfToken"]')
        if (el && el.value) return el.value
        return null
    }

    function showErrorMsg(box, msg) {
        if (!box) return
        box.innerHTML =
            '<div class="alert alert-danger animated fadeInTop"><b>' +
            (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
            " : </b> " +
            msg +
            "</div>"
    }

    function showInfoMsg(box, msg) {
        if (!box) return
        box.innerHTML =
            '<div class="alert alert-info animated fadeInTop">' +
            msg +
            "</div>"
    }

    function showSuccessMsg(box, msg) {
        if (!box) return
        box.innerHTML =
            '<div class="alert alert-success animated fadeInTop">' +
            msg +
            "</div>"
    }

    function setNextLoading(link, msgBox) {
        if (!link) return
        link.dataset.originalHtml = link.innerHTML
        link.classList.add("disabled")
        link.setAttribute("aria-disabled", "true")
        link.innerHTML = typeof LOADING_MSG !== "undefined" ? LOADING_MSG : "Chargement..."
        showInfoMsg(msgBox, typeof LOADING_MSG !== "undefined" ? LOADING_MSG : "Chargement...")
    }

    function resetNextLoading(link, msgBox, errorMsg) {
        if (!link) return
        link.classList.remove("disabled")
        link.removeAttribute("aria-disabled")
        if (link.dataset.originalHtml != null) link.innerHTML = link.dataset.originalHtml
        if (errorMsg) showErrorMsg(msgBox, errorMsg)
    }

    let csrfToken = getCsrfToken()

    let databaseDiv = document.querySelector("div.database")
    let installBtn = document.querySelector(".installSQL")
    let ajaxMsgBox = document.querySelector(".ajax-msg")
    let progressBox = document.querySelector(".SQLprogress")

    if (installBtn) {
        installBtn.style.display = "none"
        installBtn.style.opacity = 0
        installBtn.classList.remove("animated")
        installBtn.classList.remove("bounce")
    }

    if (progressBox) {
        progressBox.style.display = "none"
        progressBox.style.opacity = 0
        progressBox.classList.remove("animated")
        progressBox.classList.remove("fadeInTop")
    }

    function setupDatabaseTypeToggle() {
        if (!databaseDiv) return
        let typeSelect = databaseDiv.querySelector('select[name="type"]')
        let mysqlRequire = document.getElementById("mysql_require")
        if (!typeSelect || !mysqlRequire) return

        function updateMysqlVisibility() {
            if (typeSelect.value === "0") {
                mysqlRequire.style.display = "block"
            } else {
                mysqlRequire.style.display = "none"
            }
        }

        typeSelect.addEventListener("change", updateMysqlVisibility)
        updateMysqlVisibility()
    }

    function showInstallButton() {
        if (!installBtn) return
        fadeInElement(installBtn, 250)
        installBtn.classList.add("animated", "bounce")
    }

    function setupDatabaseSave() {
        if (!databaseDiv) return

        let dbUrl = databaseDiv.dataset.dbUrl
        let saveForm = document.querySelector("form#saveDB")
        if (!saveForm || !dbUrl) return

        saveForm.addEventListener("submit", function(e) {
            e.preventDefault()

            let button = saveForm.querySelector('button[type="submit"]')
            if (!button) return

            csrfToken = getCsrfToken()

            let typeValueEl = saveForm.querySelector('select[name="type"]')
            let hostEl = saveForm.querySelector('input[name="host"]')
            let loginEl = saveForm.querySelector('input[name="login"]')
            let databaseEl = saveForm.querySelector('input[name="database"]')
            let passwordEl = saveForm.querySelector('input[name="password"]')

            let typeValue = typeValueEl ? typeValueEl.value : ""
            let hostValue = hostEl ? hostEl.value : ""
            let loginValue = loginEl ? loginEl.value : ""
            let databaseValue = databaseEl ? databaseEl.value : ""
            let passwordValue = passwordEl ? passwordEl.value : ""

            if (typeValue === "0") {
                if (hostValue.trim() === "" || loginValue.trim() === "" || databaseValue.trim() === "") {
                    showErrorMsg(
                        ajaxMsgBox,
                        (typeof TEXT__FILL_ALL_FIELDS !== "undefined"
                            ? TEXT__FILL_ALL_FIELDS
                            : "Veuillez remplir tous les champs obligatoires")
                    )
                    if (installBtn) {
                        installBtn.style.display = "none"
                        installBtn.style.opacity = 0
                        installBtn.classList.remove("animated")
                        installBtn.classList.remove("bounce")
                    }
                    return
                }
            }

            button.disabled = true
            button.classList.add("disabled")
            let submitContent = button.innerHTML
            button.innerHTML = typeof TEXT__LOADING !== "undefined" ? TEXT__LOADING : "Loading"

            let inputs = {
                type: typeValue,
                host: hostValue,
                login: loginValue,
                database: databaseValue,
                password: passwordValue
            }

            let headers = { "Content-Type": "application/x-www-form-urlencoded" }
            if (csrfToken) headers["X-CSRF-Token"] = csrfToken

            fetch(dbUrl, {
                method: "POST",
                headers: headers,
                body: new URLSearchParams(inputs)
            })
                .then(function(r) {
                    return r.json()
                })
                .then(function(data) {
                    if (data && data.status) {
                        if (ajaxMsgBox) fadeOutElement(ajaxMsgBox, 250)
                        fadeOutElement(saveForm, 550, showInstallButton)
                    } else {
                        showErrorMsg(ajaxMsgBox, (data && data.messages) ? data.messages : "Erreur")
                        button.innerHTML = submitContent
                        button.classList.remove("disabled")
                        button.disabled = false
                        if (installBtn) {
                            installBtn.style.display = "none"
                            installBtn.style.opacity = 0
                            installBtn.classList.remove("animated")
                            installBtn.classList.remove("bounce")
                        }
                    }
                })
                .catch(function() {
                    showErrorMsg(
                        ajaxMsgBox,
                        (typeof TEXT__INTERNAL_ERROR !== "undefined" ? TEXT__INTERNAL_ERROR : "Erreur interne")
                    )
                    button.innerHTML = submitContent
                    button.classList.remove("disabled")
                    button.disabled = false
                    if (installBtn) {
                        installBtn.style.display = "none"
                        installBtn.style.opacity = 0
                        installBtn.classList.remove("animated")
                        installBtn.classList.remove("bounce")
                    }
                })
        })
    }

    function setupDatabaseInstall() {
        if (!databaseDiv || !installBtn) return

        let installUrl = databaseDiv.dataset.installUrl
        if (!installUrl) return

        installBtn.addEventListener("click", function(e) {
            e.preventDefault()

            csrfToken = getCsrfToken()

            let button = installBtn
            button.disabled = true
            button.classList.add("disabled")
            let submitContent = button.innerHTML
            button.innerHTML = typeof TEXT__LOADING !== "undefined" ? TEXT__LOADING : "Loading"

            if (progressBox) {
                fadeInElement(progressBox, 250)
                progressBox.classList.add("animated", "fadeInTop")
            }

            fetchWithProgress(installUrl, {}, csrfToken, function(p) {
                let bar = document.querySelector(".SQLprogress .progress-bar")
                if (bar) bar.style.width = p + "%"
            })
                .then(function(r) {
                    return r.json()
                })
                .then(function(data) {
                    if (data && data.status) {
                        window.location = "/install/user"
                    } else {
                        showErrorMsg(ajaxMsgBox, (data && data.messages) ? data.messages : "Erreur")
                        button.innerHTML = submitContent
                        button.classList.remove("disabled")
                        button.disabled = false
                    }
                })
                .catch(function() {
                    showErrorMsg(
                        ajaxMsgBox,
                        (typeof TEXT__INTERNAL_ERROR !== "undefined" ? TEXT__INTERNAL_ERROR : "Erreur interne")
                    )
                    button.innerHTML = submitContent
                    button.classList.remove("disabled")
                    button.disabled = false
                })
        })
    }

    function setupAdminUserForm() {
        let step3Form = document.querySelector("form#step3")
        if (!step3Form) return

        let userUrl = step3Form.dataset.userUrl
        let nextLink = step3Form.querySelector("#tabsleft-link-step1")
        let ajaxMsgStep3 = step3Form.querySelector(".ajax-msg-step3")

        if (!nextLink || !userUrl) return

        nextLink.addEventListener("click", function(e) {
            e.preventDefault()

            if (nextLink.classList.contains("disabled")) return

            csrfToken = getCsrfToken()

            setNextLoading(nextLink, ajaxMsgStep3)

            let usernameEl = step3Form.querySelector('input[name="username"]')
            let passwordEl = step3Form.querySelector('input[name="password"]')
            let passwordConfirmEl = step3Form.querySelector('input[name="password_confirmation"]')
            let emailEl = step3Form.querySelector('input[name="email"]')

            let formData = {
                username: usernameEl ? usernameEl.value : "",
                password: passwordEl ? passwordEl.value : "",
                password_confirmation: passwordConfirmEl ? passwordConfirmEl.value : "",
                email: emailEl ? emailEl.value : ""
            }

            let headers = { "Content-Type": "application/x-www-form-urlencoded" }
            if (csrfToken) headers["X-CSRF-Token"] = csrfToken

            fetch(userUrl, {
                method: "POST",
                headers: headers,
                body: new URLSearchParams(formData)
            })
                .then(function(r) {
                    return r.json()
                })
                .then(function(data) {
                    let ok = !!(data && (data.status === true))
                    if (ok) {
                        showSuccessMsg(ajaxMsgStep3, (data && data.messages) ? data.messages : "OK")

                        let tab2 = document.getElementById("tabsleft-tab2")
                        let tab3 = document.getElementById("tabsleft-tab3")
                        if (tab2) tab2.classList.remove("active", "show")
                        if (tab3) tab3.classList.add("active", "show")

                        let link2 = document.querySelector('a[href="#tabsleft-tab2"]')
                        let link3 = document.querySelector('a[href="#tabsleft-tab3"]')
                        if (link2 && link2.parentElement) link2.parentElement.classList.remove("active")
                        if (link3 && link3.parentElement) link3.parentElement.classList.add("active")
                    } else {
                        resetNextLoading(
                            nextLink,
                            ajaxMsgStep3,
                            (data && data.messages) ? data.messages : "Erreur"
                        )
                    }
                })
                .catch(function() {
                    resetNextLoading(
                        nextLink,
                        ajaxMsgStep3,
                        (typeof TEXT__INTERNAL_ERROR !== "undefined" ? TEXT__INTERNAL_ERROR : "Erreur interne")
                    )
                })
        })
    }

    setupDatabaseTypeToggle()
    setupDatabaseSave()
    setupDatabaseInstall()
    setupAdminUserForm()
})
