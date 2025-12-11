window.addEventListener("load", function() {
    let container = document.querySelector(".container")
    let loader = container ? container.querySelector("svg.loader") : null
    let logo = container ? container.querySelector(".logo") : null
    let intro = container ? container.querySelector("div.first") : null
    let compat = container ? container.querySelector("div.compatibilite") : null

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

    function animateLogo() {
        if (!logo) return
        logo.style.transition = "margin-top 1.5s, margin-bottom 1.5s"
        logo.style.marginTop = "15%"
        logo.style.marginBottom = "0"
    }

    function showIntro() {
        if (intro) {
            fadeInElement(intro, 1500, showCompatibility)
        } else {
            showCompatibility()
        }
    }

    function showCompatibility() {
        if (compat) {
            fadeInElement(compat, 550)
        }
    }

    if (loader) {
        fadeOutElement(loader, 550, function() {
            animateLogo()
            showIntro()
        })
    } else {
        animateLogo()
        showIntro()
    }
})
