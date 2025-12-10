window.addEventListener("load", function() {
    function getCookie(name) {
        let value = "; " + document.cookie
        let parts = value.split("; " + name + "=")
        if (parts.length === 2) return parts.pop().split(";").shift()
        return null
    }

    let csrfToken = getCookie("csrfToken")

    let databaseDiv = document.querySelector("div.database")
    let installBtn = document.querySelector(".installSQL")
    let loader = document.querySelector(".container svg.loader")

    if (loader) {
        fadeOut(loader, 550, affichFirst)
    } else {
        affichFirst()
    }

    function affichFirst() {
        let logo = document.querySelector(".logo")
        if (logo) {
            logo.style.transition = "margin-top 1.5s, margin-bottom 1.5s"
            logo.style.marginTop = "15%"
            logo.style.marginBottom = "0"
        }
        let first = document.querySelector("div.first")
        if (first) {
            fadeIn(first, 1500, affichCompatibilite)
        } else {
            affichCompatibilite()
        }
    }

    function affichCompatibilite() {
        let compat = document.querySelector("div.compatibilite")
        if (compat && compat.dataset.needToDisplay === "true") {
            fadeIn(compat, 550)
            let head = compat.querySelector("thead")
            if (head) head.classList.add("animated", "fadeInLeft")
            let rows = compat.querySelectorAll("tbody tr")
            let i = 0
            rows.forEach(function(tr) {
                i++
                let side = i % 2 === 0 ? "Left" : "Right"
                tr.classList.add("animated", "fadeIn" + side)
            })
        } else {
            affichDB()
        }
    }

    function affichDB() {
        if (!databaseDiv) {
            affichContinue()
            return
        }
        if (databaseDiv.dataset.needToDisplay === "true") {
            fadeIn(databaseDiv, 550)
            let groups = databaseDiv.querySelectorAll("div.form-group")
            groups.forEach(function(g) {
                g.classList.add("animated", "shake")
            })
            let saveBtn = document.querySelector(".saveDB")
            if (saveBtn) saveBtn.classList.add("animated", "tada")
        } else {
            affichContinue()
        }
    }

    function affichContinue() {
        if (!installBtn) return
        fadeIn(installBtn, 250)
        installBtn.classList.add("animated", "bounce")
    }

    if (databaseDiv) {
        let dbUrl = databaseDiv.dataset.dbUrl
        let installUrl = databaseDiv.dataset.installUrl
        let saveForm = document.querySelector("form#saveDB")

        if (saveForm && dbUrl) {
            saveForm.addEventListener("submit", function(e) {
                e.preventDefault()

                let button = saveForm.querySelector(".saveDB")
                button.disabled = true
                button.classList.add("disabled")
                let submitContent = button.innerHTML
                button.innerHTML = TEXT__LOADING

                let inputs = {
                    type: saveForm.querySelector('select[name="type"]').value,
                    host: saveForm.querySelector('input[name="host"]').value,
                    login: saveForm.querySelector('input[name="login"]').value,
                    database: saveForm.querySelector('input[name="database"]').value,
                    password: saveForm.querySelector('input[name="password"]').value
                }

                let headers = {"Content-Type": "application/x-www-form-urlencoded"}
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
                            let ajaxMsg = document.querySelector(".ajax-msg")
                            if (ajaxMsg) fadeOut(ajaxMsg, 250)
                            fadeOut(saveForm, 550, affichContinue)
                        } else {
                            document.querySelector(".ajax-msg").innerHTML =
                                '<div class="alert alert-danger animated fadeInTop"><b>' +
                                TEXT__ERROR +
                                " : </b> " +
                                data.msg +
                                "</div>"
                            button.innerHTML = submitContent
                            button.classList.remove("disabled")
                            button.disabled = false
                        }
                    })
                    .catch(function(err) {
                        let status = err && err.status ? err.status : "?"
                        document.querySelector(".ajax-msg").innerHTML =
                            '<div class="alert alert-danger animated fadeInTop"><b>' +
                            TEXT__ERROR +
                            " : </b> " +
                            TEXT__INTERNAL_ERROR +
                            " (" +
                            status +
                            ").</div>"
                        button.innerHTML = submitContent
                        button.classList.remove("disabled")
                        button.disabled = false
                    })
            })
        }

        if (installBtn && installUrl) {
            installBtn.addEventListener("click", function(e) {
                e.preventDefault()

                let button = installBtn
                button.disabled = true
                button.classList.add("disabled")
                let submitContent = button.innerHTML
                button.innerHTML = TEXT__LOADING

                let progressBox = document.querySelector(".SQLprogress")
                if (progressBox) {
                    fadeIn(progressBox, 250)
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
                        if (data.status) {
                            window.location = "/install/user"
                        } else {
                            document.querySelector(".ajax-msg").innerHTML =
                                '<div class="alert alert-danger animated fadeInTop"><b>' +
                                TEXT__ERROR +
                                " : </b> " +
                                data.msg +
                                "</div>"
                            button.innerHTML = submitContent
                            button.classList.remove("disabled")
                            button.disabled = false
                        }
                    })
                    .catch(function(err) {
                        let status = err && err.status ? err.status : "?"
                        document.querySelector(".ajax-msg").innerHTML =
                            '<div class="alert alert-danger animated fadeInTop"><b>' +
                            TEXT__ERROR +
                            " : </b> " +
                            TEXT__INTERNAL_ERROR +
                            " (" +
                            status +
                            ").</div>"
                        button.innerHTML = submitContent
                        button.classList.remove("disabled")
                        button.disabled = false
                    })
            })
        }
    }

    let step3Form = document.querySelector("form#step3")
    if (step3Form) {
        let userUrl = step3Form.dataset.userUrl
        let nextLink = step3Form.querySelector("#tabsleft-link")
        let ajaxMsgStep3 = step3Form.querySelector(".ajax-msg-step3")
        let progressBar = document.querySelector(".progress .progress-bar")

        if (nextLink && userUrl) {
            nextLink.addEventListener("click", function(e) {
                e.preventDefault()

                let button = nextLink
                button.classList.add("disabled")
                let formData = {
                    pseudo: step3Form.querySelector('input[name="pseudo"]').value,
                    password: step3Form.querySelector('input[name="password"]').value,
                    password_confirmation: step3Form.querySelector('input[name="password_confirmation"]').value,
                    email: step3Form.querySelector('input[name="email"]').value
                }

                let headers = {"Content-Type": "application/x-www-form-urlencoded"}
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
                                    TEXT__LOADING +
                                    " : </b> " +
                                    data.msg +
                                    "</div>"
                            }
                            let tab2 = document.getElementById("tabsleft-tab2")
                            let tab3 = document.getElementById("tabsleft-tab3")
                            if (tab2) tab2.classList.remove("active")
                            if (tab3) tab3.classList.add("active")

                            let link2 = document.querySelector('a[href="#tabsleft-tab2"]')
                            let link3 = document.querySelector('a[href="#tabsleft-tab3"]')
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
                                    TEXT__ERROR +
                                    " : </b> " +
                                    data.msg +
                                    "</div>"
                            }
                            button.classList.remove("disabled")
                        }
                    })
                    .catch(function(err) {
                        let status = err && err.status ? err.status : "?"
                        if (ajaxMsgStep3) {
                            ajaxMsgStep3.innerHTML =
                                '<div class="alert alert-danger animated fadeInTop"><b>' +
                                TEXT__ERROR +
                                " : </b> " +
                                TEXT__INTERNAL_ERROR +
                                " (" +
                                status +
                                ").</div>"
                        }
                        button.classList.remove("disabled")
                    })
            })
        }
    }

    function fadeIn(el, duration, callback) {
        if (!el) return
        el.style.opacity = 0
        el.style.display = "block"
        let last = performance.now()
        let tick = function(now) {
            let dt = now - last
            el.style.opacity = +el.style.opacity + dt / duration
            last = now
            if (+el.style.opacity < 1) {
                requestAnimationFrame(tick)
            } else if (callback) {
                callback()
            }
        }
        requestAnimationFrame(tick)
    }

    function fadeOut(el, duration, callback) {
        if (!el) return
        el.style.opacity = 1
        let last = performance.now()
        let tick = function(now) {
            let dt = now - last
            el.style.opacity = +el.style.opacity - dt / duration
            last = now
            if (+el.style.opacity > 0) {
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
                resolve(new Response(xhr.responseText, {status: xhr.status}))
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
})
