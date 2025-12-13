(function () {

    function all(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    class Notification {
        constructor(config) {
            this.options = Object.assign({}, Notification.defaultOptions, config || {});
            this.options.indicator = Object.assign({}, Notification.defaultOptions.indicator, (config && config.indicator) || {});
            this.options.indicator.style = Object.assign({}, Notification.defaultOptions.indicator.style, (config && config.indicator && config.indicator.style) || {});
            this.options.messages = Object.assign({}, Notification.defaultOptions.messages, (config && config.messages) || {});
            this.options.url = Object.assign({}, Notification.defaultOptions.url, (config && config.url) || {});
            this.options.list = Object.assign({}, Notification.defaultOptions.list, (config && config.list) || {});
            this.options.list.container = Object.assign({}, Notification.defaultOptions.list.container, (config && config.list && config.list.container) || {});
            this.options.list.notification = Object.assign({}, Notification.defaultOptions.list.notification, (config && config.list && config.list.notification) || {});
            this.options.list.notification.from = Object.assign({}, Notification.defaultOptions.list.notification.from, (config && config.list && config.list.notification && config.list.notification.from) || {});
            this.options.list.notification.seen = Object.assign({}, Notification.defaultOptions.list.notification.seen, (config && config.list && config.list.notification && config.list.notification.seen) || {});
            this.options.list.notification.seen.element = Object.assign({}, Notification.defaultOptions.list.notification.seen.element, (config && config.list && config.list.notification && config.list.notification.seen && config.list.notification.seen.element) || {});
            this.options.list.notification.seen.btn = Object.assign({}, Notification.defaultOptions.list.notification.seen.btn, (config && config.list && config.list.notification && config.list.notification.seen && config.list.notification.seen.btn) || {});
            this.options.list.notification.seen.btn.attr = Array.isArray((config && config.list && config.list.notification && config.list.notification.seen && config.list.notification.seen.btn && config.list.notification.seen.btn.attr))
                ? config.list.notification.seen.btn.attr
                : Notification.defaultOptions.list.notification.seen.btn.attr;

            this.notifications = {};
            this.getNotifications().then();
        }

        createIndicator(count) {
            const indicator = document.createElement('span');
            indicator.className = this.options.indicator.class;

            const style = this.options.indicator.style || {};
            for (const property in style) {
                indicator.style[property] = style[property];
            }

            indicator.textContent = String(count);
            return indicator;
        }

        async request(url) {
            const res = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return await res.json();
            }

            return await res.text();
        }

        async getNotifications() {
            this.notifications = {};
            const url = String(this.options.url.get || '') + '/' + encodeURIComponent(this.options.notification_type || 'user');

            const data = await this.request(url);
            if (!Array.isArray(data)) {
                this.updateDOM();
                return;
            }

            for (let i = 0; i < data.length; i++) {
                if (this.options.limit > 0 && this.options.limit === i) break;
                const id = Number(data[i] && data[i].id);
                if (!Number.isFinite(id)) continue;
                this.notifications[id] = data[i];
            }

            this.updateDOM();
        }

        unseenCount() {
            let count = 0;
            for (const k in this.notifications) {
                if (!this.notifications[k].seen) count++;
            }
            return count;
        }

        updateDOM() {
            const count = this.unseenCount();
            const indicatorTargets = all(this.options.indicator.element);

            if (count > 0) {
                const indicatorHtml = this.createIndicator(count).outerHTML;
                for (const el of indicatorTargets) {
                    el.innerHTML = String(this.options.indicator.defaultContent || '') + indicatorHtml;
                }
            } else {
                for (const el of indicatorTargets) {
                    el.innerHTML = String(this.options.indicator.defaultContent || '');
                }
            }

            this.generateNotificationsList();
        }

        bindActions(rootEl) {
            if (!rootEl) return;

            const markBtns = rootEl.querySelectorAll('[data-notif-action="mark"]');
            for (const btn of markBtns) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = Number(btn.getAttribute('data-notif-id'));
                    if (Number.isFinite(id)) this.markAsSeen(id);
                });
            }

            const clearBtns = rootEl.querySelectorAll('[data-notif-action="clear"]');
            for (const btn of clearBtns) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = Number(btn.getAttribute('data-notif-id'));
                    if (Number.isFinite(id)) this.clear(id);
                });
            }
        }

        generateNotificationsList() {
            const useContainerEl = String(this.options.list.container.type || '').length > 0;
            const containerEl = useContainerEl ? document.createElement(this.options.list.container.type) : null;

            if (containerEl) {
                containerEl.style.cssText = String(this.options.list.container.style || '');
                containerEl.className = String(this.options.list.container.class || '');
            }

            const ids = Object.keys(this.notifications)
                .map((x) => Number(x))
                .filter((x) => Number.isFinite(x))
                .sort((a, b) => b - a);

            let containerHtml = '';

            for (const id of ids) {
                const notif = this.notifications[id];
                const el = document.createElement(this.options.list.notification.type);
                el.style.cssText = String(this.options.list.notification.style || '');
                el.className = String(this.options.list.notification.class || '');

                if (notif && notif.seen) {
                    el.style.cssText += ' ' + String(this.options.list.notification.seen.element.style || '');
                    el.className += ' ' + String(this.options.list.notification.seen.element.class || '');
                }

                const safeContent = escapeHtml(notif && notif.content != null ? notif.content : '');
                const safeTime = escapeHtml(notif && notif.time != null ? notif.time : '');

                let content = String(this.options.list.notification.content || '');
                content = content.replaceAll('{ID}', String(id));
                content = content.replaceAll('{CONTENT}', safeContent);
                content = content.replaceAll('{TIME}', safeTime);
                content = content.replaceAll('{MARK_AS_SEEN}', escapeHtml(this.options.messages.markAsSeen || ''));

                el.innerHTML = content;

                const seenBtnSel = String(this.options.list.notification.seen.btn.element || '');
                if (notif && notif.seen && seenBtnSel) {
                    const btnSeen = el.querySelector(seenBtnSel);
                    if (btnSeen) {
                        btnSeen.style.cssText += ' ' + String(this.options.list.notification.seen.btn.style || '');
                        btnSeen.className += ' ' + String(this.options.list.notification.seen.btn.class || '');

                        const attrs = this.options.list.notification.seen.btn.attr || [];
                        for (const attrObj of attrs) {
                            for (const attr in attrObj) {
                                btnSeen.setAttribute(attr, String(attrObj[attr]));
                            }
                        }
                    }
                }

                const fromCfg = this.options.list.notification.from || {};
                const hasFrom = notif && notif.from != null && String(fromCfg.type || '').length > 0;
                if (hasFrom) {
                    const fromEl = document.createElement(fromCfg.type);
                    fromEl.style.cssText = String(fromCfg.style || '');
                    fromEl.className = String(fromCfg.class || '');

                    let fromContent = String(fromCfg.content || '');
                    fromContent = fromContent.replaceAll('{NOTIFIED_BY}', escapeHtml(this.options.messages.notifiedBy || ''));
                    fromContent = fromContent.replaceAll('{FROM}', escapeHtml(notif.from));

                    fromEl.innerHTML = fromContent;
                    el.innerHTML = el.innerHTML.replaceAll('{FROM}', fromEl.outerHTML);
                } else {
                    el.innerHTML = el.innerHTML.replaceAll('{FROM}', '');
                }

                this.bindActions(el);

                if (containerEl) {
                    containerEl.appendChild(el);
                } else {
                    containerHtml += el.outerHTML;
                }
            }

            const targets = all(this.options.list.element);
            for (const t of targets) {
                if (containerEl) {
                    t.innerHTML = '';
                    t.appendChild(containerEl.cloneNode(true));
                    this.bindActions(t);
                } else {
                    t.innerHTML = containerHtml;
                    this.bindActions(t);
                }
            }
        }

        async clear(id) {
            let url = String(this.options.url.clear || '');
            url = url.replace('NOTIF_ID', String(id));

            await this.request(url);
            delete this.notifications[id];
            this.updateDOM();
        }

        async clearAll() {
            await this.request(String(this.options.url.clearAll || ''));
            this.notifications = {};
            this.updateDOM();
        }

        async markAsSeen(id) {
            let url = String(this.options.url.markAsSeen || '');
            url = url.replace('NOTIF_ID', String(id));

            await this.request(url);
            if (this.notifications[id]) this.notifications[id].seen = true;
            this.updateDOM();
        }

        markAllAsSeen(time) {
            const delay = (Number(time) || 0) * 1000;

            window.setTimeout(async () => {
                await this.request(String(this.options.url.markAllAsSeen || ''));

                for (const k in this.notifications) {
                    this.notifications[k].seen = true;
                }

                this.updateDOM();
            }, delay);
        }
    }

    Notification.defaultOptions = {
        notification_type: 'user',
        limit: 0,
        indicator: {
            element: '.notification-indicator',
            style: {
                position: 'absolute',
                top: '-5px',
                borderRadius: '30px'
            },
            class: 'label label-danger',
            defaultContent: ''
        },
        messages: {
            markAsSeen: '#',
            notifiedBy: '#'
        },
        url: {
            get: '#',
            clear: '#',
            clearAll: '#',
            markAsSeen: '#',
            markAllAsSeen: '#'
        },
        list: {
            element: '.notifications-list',
            container: {
                type: 'ul',
                class: 'list-group',
                style: 'margin-bottom:0;'
            },
            notification: {
                type: 'li',
                class: 'list-group-item',
                style: 'border-top-left-radius:0;border-top-right-radius:0;',
                content:
                    '<p>{CONTENT}<small class="pull-right"><em>{TIME}</em></small></p>{FROM}' +
                    '<div class="btn-group pull-right" style="margin-top:-5px;">' +
                    '<button type="button" class="btn btn-default btn-sm mark-as-seen" data-notif-action="mark" data-notif-id="{ID}">' +
                    '<abbr title="{MARK_AS_SEEN}"><i class="fa fa-check"></i></abbr>' +
                    '</button>' +
                    '<button type="button" class="btn btn-danger btn-sm" data-notif-action="clear" data-notif-id="{ID}">' +
                    '<i class="fa fa-times"></i>' +
                    '</button>' +
                    '</div>' +
                    '<div class="clearfix"></div>',
                from: {
                    type: 'small',
                    class: 'text-muted',
                    style: '',
                    content: '<u><em>{NOTIFIED_BY} {FROM}</em></u>'
                },
                seen: {
                    element: {
                        style: 'opacity:0.6;',
                        class: ''
                    },
                    btn: {
                        element: '.mark-as-seen',
                        style: '',
                        class: 'disabled active',
                        attr: [{ disabled: true }, { onclick: '' }]
                    }
                }
            }
        }
    };

    window.NotificationWidget = Notification;
})();
