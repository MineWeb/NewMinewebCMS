window.addEventListener("load", function() {
    function getCookie(name) {
        var value = "; " + document.cookie
        var parts = value.split("; " + name + "=")
        if (parts.length === 2) return parts.pop().split(";").shift()
        return null
    }

    function fadeInElement(el, duration, callback) {
        if (!el) return
        el.style.opacity = 0
        el.style.display = "block"
        var last = performance.now()
        function tick(now) {
            var dt = now - last
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
        var last = performance.now()
        function tick(now) {
            var dt = now - last
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
            var xhr = new XMLHttpRequest()
            xhr.open("POST", url)

            if (csrfToken) {
                xhr.setRequestHeader("X-CSRF-Token", csrfToken)
            }

            xhr.upload.onprogress = function(e) {
                var percent
                if (e.lengthComputable) {
                    percent = e.loaded / e.total * 100
                } else {
                    percent = e.loaded / 1000000 * 100
                }
                if (onProgress) onProgress(percent)
            }

            xhr.onload = function() {
                resolve(new Response(xhr.responseText, {status: xhr.status}))
            }

            xhr.onerror = function() {
                reject(xhr)
            }

            var formData = new FormData()
            for (var k in data) {
                if (Object.prototype.hasOwnProperty.call(data, k)) {
                    formData.append(k, data[k])
                }
            }

            xhr.send(formData)
        })
    }

    var csrfToken = getCookie("csrfToken")
    var databaseDiv = document.querySelector("div.database")
    var installBtn = document.querySelector(".installSQL")
    var ajaxMsgBox = document.querySelector(".ajax-msg")
    var progressBox = document.querySelector(".SQLprogress")

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
        var typeSelect = databaseDiv.querySelector('select[name="type"]')
        var mysqlRequire = document.getElementById("mysql_require")
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

    function setupDatabaseSave(csrfToken) {
        if (!databaseDiv) return

        var dbUrl = databaseDiv.dataset.dbUrl
        var saveForm = document.querySelector("form#saveDB")
        if (!saveForm || !dbUrl) return

        saveForm.addEventListener("submit", function(e) {
            e.preventDefault()

            var button = saveForm.querySelector(".saveDB")
            if (!button) return

            var typeValue = saveForm.querySelector('select[name="type"]').value
            var hostValue = saveForm.querySelector('input[name="host"]').value
            var loginValue = saveForm.querySelector('input[name="login"]').value
            var databaseValue = saveForm.querySelector('input[name="database"]').value
            var passwordValue = saveForm.querySelector('input[name="password"]').value

            if (typeValue === "0") {
                if (
                    hostValue.trim() === "" ||
                    loginValue.trim() === "" ||
                    databaseValue.trim() === ""
                ) {
                    if (ajaxMsgBox) {
                        ajaxMsgBox.innerHTML =
                            '<div class="alert alert-danger animated fadeInTop"><b>' +
                            (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                            " : </b> " +
                            (typeof TEXT__FILL_ALL_FIELDS !== "undefined"
                                ? TEXT__FILL_ALL_FIELDS
                                : "Veuillez remplir tous les champs obligatoires") +
                            "</div>"
                    }
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
            var submitContent = button.innerHTML
            button.innerHTML = typeof TEXT__LOADING !== "undefined" ? TEXT__LOADING : "Loading"

            var inputs = {
                type: typeValue,
                host: hostValue,
                login: loginValue,
                database: databaseValue,
                password: passwordValue
            }

            var headers = {"Content-Type": "application/x-www-form-urlencoded"}
            if (csrfToken) {
                headers["X-CSRF-Token"] = csrfToken
            }

            fetch(dbUrl, {
                method: "POST",
                headers: headers,
                body: new URLSearchParams(inputs)
            })
                .then(function(r) {
                    return r.json()
                })
                .then(function(data) {
                    if (data.status) {
                        if (ajaxMsgBox) fadeOutElement(ajaxMsgBox, 250)
                        fadeOutElement(saveForm, 550, showInstallButton)
                    } else {
                        if (ajaxMsgBox) {
                            ajaxMsgBox.innerHTML =
                                '<div class="alert alert-danger animated fadeInTop"><b>' +
                                (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                                " : </b> " +
                                data.msg +
                                "</div>"
                        }
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
                .catch(function(err) {
                    var status = err && err.status ? err.status : "?"
                    if (ajaxMsgBox) {
                        ajaxMsgBox.innerHTML =
                            '<div class="alert alert-danger animated fadeInTop"><b>' +
                            (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                            " : </b> " +
                            (typeof TEXT__INTERNAL_ERROR !== "undefined" ? TEXT__INTERNAL_ERROR : "Erreur interne") +
                            " (" +
                            status +
                            ").</div>"
                    }
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

    function setupDatabaseInstall(csrfToken) {
        if (!databaseDiv || !installBtn) return

        var installUrl = databaseDiv.dataset.installUrl
        if (!installUrl) return

        installBtn.addEventListener("click", function(e) {
            e.preventDefault()

            var button = installBtn
            button.disabled = true
            button.classList.add("disabled")
            var submitContent = button.innerHTML
            button.innerHTML = typeof TEXT__LOADING !== "undefined" ? TEXT__LOADING : "Loading"

            if (progressBox) {
                fadeInElement(progressBox, 250)
                progressBox.classList.add("animated", "fadeInTop")
            }

            fetchWithProgress(installUrl, {}, csrfToken, function(p) {
                var bar = document.querySelector(".SQLprogress .progress-bar")
                if (bar) bar.style.width = p + "%"
            })
                .then(function(r) {
                    return r.json()
                })
                .then(function(data) {
                    if (data.status) {
                        window.location = "/install/user"
                    } else {
                        if (ajaxMsgBox) {
                            ajaxMsgBox.innerHTML =
                                '<div class="alert alert-danger animated fadeInTop"><b>' +
                                (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                                " : </b> " +
                                data.msg +
                                "</div>"
                        }
                        button.innerHTML = submitContent
                        button.classList.remove("disabled")
                        button.disabled = false
                    }
                })
                .catch(function(err) {
                    var status = err && err.status ? err.status : "?"
                    if (ajaxMsgBox) {
                        ajaxMsgBox.innerHTML =
                            '<div class="alert alert-danger animated fadeInTop"><b>' +
                            (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                            " : </b> " +
                            (typeof TEXT__INTERNAL_ERROR !== "undefined" ? TEXT__INTERNAL_ERROR : "Erreur interne") +
                            " (" +
                            status +
                            ").</div>"
                    }
                    button.innerHTML = submitContent
                    button.classList.remove("disabled")
                    button.disabled = false
                })
        })
    }

    function setupAdminUserForm(csrfToken) {
        var step3Form = document.querySelector("form#step3")
        if (!step3Form) return

        var userUrl = step3Form.dataset.userUrl
        var nextLink = step3Form.querySelector("#tabsleft-link")
        var ajaxMsgStep3 = step3Form.querySelector(".ajax-msg-step3")
        var progressBar = document.querySelector(".progress .progress-bar")

        if (!nextLink || !userUrl) return

        nextLink.addEventListener("click", function(e) {
            e.preventDefault()

            var button = nextLink
            button.classList.add("disabled")

            var formData = {
                pseudo: step3Form.querySelector('input[name="pseudo"]').value,
                password: step3Form.querySelector('input[name="password"]').value,
                password_confirmation: step3Form.querySelector('input[name="password_confirmation"]').value,
                email: step3Form.querySelector('input[name="email"]').value
            }

            var headers = {"Content-Type": "application/x-www-form-urlencoded"}
            if (csrfToken) {
                headers["X-CSRF-Token"] = csrfToken
            }

            fetch(userUrl, {
                method: "POST",
                headers: headers,
                body: new URLSearchParams(formData)
            })
                .then(function(r) {
                    return r.json()
                })
                .then(function(data) {
                    if (data.statut) {
                        if (ajaxMsgStep3) {
                            ajaxMsgStep3.innerHTML =
                                '<div class="alert alert-success animated fadeInTop"><b>' +
                                (typeof TEXT__LOADING !== "undefined" ? TEXT__LOADING : "Chargement") +
                                " : </b> " +
                                data.msg +
                                "</div>"
                        }
                        var tab2 = document.getElementById("tabsleft-tab2")
                        var tab3 = document.getElementById("tabsleft-tab3")
                        if (tab2) tab2.classList.remove("active")
                        if (tab3) tab3.classList.add("active")

                        var link2 = document.querySelector('a[href="#tabsleft-tab2"]')
                        var link3 = document.querySelector('a[href="#tabsleft-tab3"]')
                        if (link2 && link2.parentElement) link2.parentElement.classList.remove("active")
                        if (link3 && link3.parentElement) link3.parentElement.classList.add("active")

                        if (progressBar) {
                            progressBar.style.width = "100%"
                            progressBar.setAttribute("aria-valuenow", "100")
                        }
                    } else {
                        if (ajaxMsgStep3) {
                            ajaxMsgStep3.innerHTML =
                                '<div class="alert alert-danger animated fadeInTop"><b>' +
                                (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                                " : </b> " +
                                data.msg +
                                "</div>"
                        }
                        button.classList.remove("disabled")
                    }
                })
                .catch(function(err) {
                    var status = err && err.status ? err.status : "?"
                    if (ajaxMsgStep3) {
                        ajaxMsgStep3.innerHTML =
                            '<div class="alert alert-danger animated fadeInTop"><b>' +
                            (typeof TEXT__ERROR !== "undefined" ? TEXT__ERROR : "Erreur") +
                            " : </b> " +
                            (typeof TEXT__INTERNAL_ERROR !== "undefined" ? TEXT__INTERNAL_ERROR : "Erreur interne") +
                            " (" +
                            status +
                            ").</div>"
                    }
                    button.classList.remove("disabled")
                })
        })
    }

    setupDatabaseTypeToggle()
    setupDatabaseSave(csrfToken)
    setupDatabaseInstall(csrfToken)
    setupAdminUserForm(csrfToken)
})
