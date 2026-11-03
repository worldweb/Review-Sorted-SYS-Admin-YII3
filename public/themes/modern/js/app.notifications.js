/**
 * Modern Theme In-App Notification Manager
 * Handles unread badge polling/fetching, dropdown populating, and mark-as-read actions.
 */
(function($) {
    'use strict';

    const NotificationManager = {
        dropdownEl: null,
        toggleBtn: null,
        badgeEl: null,
        menuEl: null,
        isLoading: false,
        lastFetchTime: 0,
        cacheTtlMs: 5000,
        urls: {
            latest: '',
            markRead: '',
            markAllRead: ''
        },

        init() {
            this.dropdownEl = document.getElementById('rs-notifications-dropdown');
            this.toggleBtn = document.getElementById('rs-notification-bell-btn');
            this.badgeEl = document.getElementById('rs-notification-badge');
            this.menuEl = document.getElementById('rs-notification-menu');

            if (!this.dropdownEl || !this.toggleBtn || !this.menuEl) {
                return;
            }

            this.urls.latest = this.dropdownEl.getAttribute('data-url-latest') || '';
            this.urls.markRead = this.dropdownEl.getAttribute('data-url-mark-read') || '';
            this.urls.markAllRead = this.dropdownEl.getAttribute('data-url-mark-all-read') || '';

            if (!this.urls.latest) {
                console.warn('NotificationManager: data-url-latest is not configured.');
            }
            if (!this.urls.markRead) {
                console.warn('NotificationManager: data-url-mark-read is not configured.');
            }
            if (!this.urls.markAllRead) {
                console.warn('NotificationManager: data-url-mark-all-read is not configured.');
            }

            this.bindEvents();
            if (this.urls.latest) {
                this.fetchLatest(false); // Initial background count check on page load
            }
        },

        bindEvents() {
            const self = this;

            // When bell toggle is clicked
            this.toggleBtn.addEventListener('click', function() {
                // If opening dropdown and cache is expired, refresh list
                const now = Date.now();
                if (now - self.lastFetchTime > self.cacheTtlMs) {
                    self.fetchLatest(true);
                }
            });
        },

        getCsrfToken() {
            if (window.yii && typeof window.yii.getCsrfToken === 'function') {
                return window.yii.getCsrfToken();
            }
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        },

        getCsrfParam() {
            if (window.yii && typeof window.yii.getCsrfParam === 'function') {
                return window.yii.getCsrfParam();
            }
            const meta = document.querySelector('meta[name="csrf-param"]');
            return meta ? meta.getAttribute('content') : '_csrf';
        },

        updateBadge(count) {
            if (!this.badgeEl) return;
            const numericCount = parseInt(count, 10) || 0;
            if (numericCount > 0) {
                this.badgeEl.textContent = numericCount > 99 ? '99+' : numericCount;
                this.badgeEl.style.display = 'inline-block';
                this.badgeEl.classList.remove('rs-d-none');
            } else {
                this.badgeEl.textContent = '0';
                this.badgeEl.style.display = 'none';
                this.badgeEl.classList.add('rs-d-none');
            }
        },

        fetchLatest(renderMenu) {
            const self = this;
            if (!self.urls.latest) {
                if (renderMenu) {
                    self.renderEmpty();
                }
                return;
            }
            if (self.isLoading) return;
            self.isLoading = true;

            if (renderMenu) {
                self.renderLoading();
            }

            $.ajax({
                url: self.urls.latest,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    self.isLoading = false;
                    self.lastFetchTime = Date.now();
                    if (response && response.success) {
                        self.updateBadge(response.unread_count);
                        if (renderMenu) {
                            self.renderNotifications(response.items || [], response.unread_count || 0);
                        }
                    } else if (renderMenu) {
                        self.renderEmpty();
                    }
                },
                error: function() {
                    self.isLoading = false;
                    if (renderMenu) {
                        self.renderError();
                    }
                }
            });
        },

        renderLoading() {
            if (!this.menuEl) return;
            this.menuEl.innerHTML = '';
            
            const container = document.createElement('div');
            container.className = 'rs-p-4 rs-text-center rs-text-muted';
            container.style.fontSize = '0.875rem';

            const text = document.createElement('span');
            text.textContent = 'Loading notifications...';
            container.appendChild(text);

            this.menuEl.appendChild(container);
        },

        renderEmpty() {
            if (!this.menuEl) return;
            this.menuEl.innerHTML = '';

            const container = document.createElement('div');
            container.className = 'rs-p-4 rs-text-center rs-text-muted';
            
            const p = document.createElement('p');
            p.className = 'rs-m-0';
            p.style.fontSize = '0.875rem';
            p.textContent = 'No new notifications';
            container.appendChild(p);

            this.menuEl.appendChild(container);
        },

        renderError() {
            if (!this.menuEl) return;
            this.menuEl.innerHTML = '';

            const container = document.createElement('div');
            container.className = 'rs-p-3 rs-text-center rs-text-muted';
            
            const p = document.createElement('p');
            p.className = 'rs-m-0';
            p.style.fontSize = '0.8125rem';
            p.textContent = 'Unable to load notifications.';
            container.appendChild(p);

            this.menuEl.appendChild(container);
        },

        renderNotifications(items, unreadCount) {
            const self = this;
            if (!this.menuEl) return;
            this.menuEl.innerHTML = '';

            if (!items || items.length === 0) {
                self.renderEmpty();
                return;
            }

            // 1. Dropdown Header
            const header = document.createElement('div');
            header.className = 'rs-d-flex rs-align-items-center rs-justify-content-between rs-p-3';
            header.style.borderBottom = '1px solid var(--rs-border)';
            header.style.backgroundColor = 'var(--rs-bg-surface)';

            const title = document.createElement('span');
            title.className = 'rs-font-bold rs-text-main';
            title.style.fontSize = '0.875rem';
            title.textContent = 'Notifications';
            header.appendChild(title);

            if (unreadCount > 0) {
                const markAllBtn = document.createElement('button');
                markAllBtn.type = 'button';
                markAllBtn.className = 'rs-btn rs-btn--ghost rs-btn--sm';
                markAllBtn.style.fontSize = '0.75rem';
                markAllBtn.style.padding = '2px 6px';
                markAllBtn.style.color = 'var(--rs-primary)';
                markAllBtn.textContent = 'Mark all as read';
                markAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.markAllAsRead(markAllBtn);
                });
                header.appendChild(markAllBtn);
            }

            this.menuEl.appendChild(header);

            // 2. Notification List Container
            const list = document.createElement('div');
            list.className = 'rs-notification-list';
            list.style.maxHeight = '360px';
            list.style.overflowY = 'auto';

            items.forEach(function(item) {
                const row = document.createElement('a');
                row.href = item.action_url || '#';
                row.className = 'rs-d-block rs-p-3 rs-text-decoration-none';
                row.style.borderBottom = '1px solid rgba(0, 0, 0, 0.04)';
                row.style.transition = 'background-color 0.15s ease';
                row.style.color = 'inherit';
                row.style.display = 'block';

                if (!item.is_read) {
                    row.style.backgroundColor = 'rgba(35, 65, 55, 0.04)';
                    row.style.borderLeft = '3px solid var(--rs-primary)';
                } else {
                    row.style.borderLeft = '3px solid transparent';
                }

                row.addEventListener('mouseenter', function() {
                    row.style.backgroundColor = 'var(--rs-bg-app)';
                });
                row.addEventListener('mouseleave', function() {
                    row.style.backgroundColor = item.is_read ? 'transparent' : 'rgba(35, 65, 55, 0.04)';
                });

                // Top row: Title + Time
                const topDiv = document.createElement('div');
                topDiv.className = 'rs-d-flex rs-align-items-center rs-justify-content-between rs-gap-2 rs-mb-1';

                const itemTitle = document.createElement('span');
                itemTitle.className = item.is_read ? 'rs-font-medium rs-text-main' : 'rs-font-bold rs-text-main';
                itemTitle.style.fontSize = '0.8125rem';
                itemTitle.textContent = item.title;
                topDiv.appendChild(itemTitle);

                const itemTime = document.createElement('span');
                itemTime.className = 'rs-text-muted';
                itemTime.style.fontSize = '0.6875rem';
                itemTime.style.whiteSpace = 'nowrap';
                itemTime.textContent = item.time_ago || '';
                topDiv.appendChild(itemTime);

                row.appendChild(topDiv);

                // Message body
                if (item.message) {
                    const itemMsg = document.createElement('div');
                    itemMsg.className = 'rs-text-secondary';
                    itemMsg.style.fontSize = '0.75rem';
                    itemMsg.style.lineHeight = '1.4';
                    itemMsg.style.wordBreak = 'break-word';
                    itemMsg.textContent = item.message;
                    row.appendChild(itemMsg);
                }

                // Click event: mark read then navigate
                row.addEventListener('click', function(e) {
                    if (item.action_url && item.action_url !== '#') {
                        e.preventDefault();
                        self.markAsRead(item.id, function() {
                            window.location.href = item.action_url;
                        });
                    }
                });

                list.appendChild(row);
            });

            this.menuEl.appendChild(list);
        },

        markAsRead(id, callback) {
            const self = this;
            if (!self.urls.markRead) {
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }
            const data = {};
            data[self.getCsrfParam()] = self.getCsrfToken();

            const separator = self.urls.markRead.indexOf('?') !== -1 ? '&' : '?';
            const markReadUrl = self.urls.markRead + separator + 'id=' + encodeURIComponent(id);

            $.ajax({
                url: markReadUrl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        self.updateBadge(response.unread_count);
                    }
                    if (typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        },

        markAllAsRead(btnEl) {
            const self = this;
            if (!self.urls.markAllRead) {
                return;
            }
            const data = {};
            data[self.getCsrfParam()] = self.getCsrfToken();

            if (btnEl) {
                btnEl.disabled = true;
                btnEl.textContent = 'Marking...';
            }

            $.ajax({
                url: self.urls.markAllRead,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        self.updateBadge(0);
                        self.fetchLatest(true);
                    }
                },
                error: function() {
                    if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.textContent = 'Mark all as read';
                    }
                }
            });
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        NotificationManager.init();
    });

})(jQuery);
