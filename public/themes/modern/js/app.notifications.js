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
        cachedItems: null,
        cachedUnreadCount: 0,
        hasRenderedMenu: false,
        urls: {
            latest: '',
            markRead: '',
            markAllRead: '',
            history: ''
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
            this.urls.history = this.dropdownEl.getAttribute('data-url-history') || '';

            this.bindEvents();
            if (this.urls.latest) {
                this.fetchLatest(false); // Initial background check on page load
            }
        },

        bindEvents() {
            const self = this;

            // When bell toggle is clicked
            this.toggleBtn.addEventListener('click', function() {
                const now = Date.now();
                // If not yet rendered, render cached items immediately or fetch
                if (!self.hasRenderedMenu) {
                    if (self.cachedItems !== null) {
                        self.renderNotifications(self.cachedItems, self.cachedUnreadCount);
                        // If cache is stale, refresh in background
                        if (now - self.lastFetchTime > self.cacheTtlMs) {
                            self.fetchLatest(false);
                        }
                    } else {
                        self.fetchLatest(true);
                    }
                } else if (now - self.lastFetchTime > self.cacheTtlMs) {
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
                        self.cachedItems = response.items || [];
                        self.cachedUnreadCount = response.unread_count || 0;
                        self.updateBadge(response.unread_count);
                        if (renderMenu) {
                            self.renderNotifications(self.cachedItems, self.cachedUnreadCount);
                        }
                    } else if (renderMenu) {
                        self.renderEmpty();
                    }
                },
                error: function() {
                    self.isLoading = false;
                    if (renderMenu) {
                        if (self.cachedItems !== null && self.cachedItems.length > 0) {
                            self.renderNotifications(self.cachedItems, self.cachedUnreadCount);
                        } else {
                            self.renderError();
                        }
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
            this.hasRenderedMenu = true;
            this.menuEl.innerHTML = '';

            const container = document.createElement('div');
            container.className = 'rs-p-4 rs-text-center rs-text-muted';
            
            const p = document.createElement('p');
            p.className = 'rs-m-0';
            p.style.fontSize = '0.875rem';
            p.textContent = 'No new notifications';
            container.appendChild(p);

            this.menuEl.appendChild(container);

            if (this.urls.history) {
                const footer = document.createElement('div');
                footer.className = 'rs-p-2 rs-text-center';
                footer.style.borderTop = '1px solid var(--rs-border)';
                footer.style.backgroundColor = 'var(--rs-bg-surface)';

                const link = document.createElement('a');
                link.href = this.urls.history;
                link.className = 'rs-btn rs-btn--ghost rs-btn--sm rs-w-100';
                link.style.fontSize = '0.8125rem';
                link.style.display = 'block';
                link.style.textAlign = 'center';
                link.textContent = 'View all notifications';

                footer.appendChild(link);
                this.menuEl.appendChild(footer);
            }
        },

        renderError() {
            const self = this;
            if (!this.menuEl) return;
            this.menuEl.innerHTML = '';

            const container = document.createElement('div');
            container.className = 'rs-p-3 rs-text-center rs-text-muted';
            
            const p = document.createElement('p');
            p.className = 'rs-m-0 rs-mb-2';
            p.style.fontSize = '0.8125rem';
            p.textContent = 'Unable to load notifications.';
            container.appendChild(p);

            const retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.className = 'rs-btn rs-btn--ghost rs-btn--sm';
            retryBtn.style.fontSize = '0.75rem';
            retryBtn.textContent = 'Retry';
            retryBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                self.fetchLatest(true);
            });
            container.appendChild(retryBtn);

            this.menuEl.appendChild(container);
        },

        getTypeMeta(type) {
            switch (type) {
                case 'review':
                case 'new_review':
                    return {
                        bg: 'rgba(245, 158, 11, 0.12)',
                        color: '#d97706',
                        svg: '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
                        label: 'Review'
                    };
                case 'social_post':
                    return {
                        bg: 'rgba(16, 185, 129, 0.12)',
                        color: '#059669',
                        svg: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>',
                        label: 'Post'
                    };
                case 'social_post_failed':
                    return {
                        bg: 'rgba(239, 68, 68, 0.12)',
                        color: '#dc2626',
                        svg: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
                        label: 'Failed'
                    };
                case 'connection':
                    return {
                        bg: 'rgba(59, 130, 246, 0.12)',
                        color: '#2563eb',
                        svg: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
                        label: 'Link'
                    };
                default:
                    return {
                        bg: 'rgba(35, 65, 55, 0.08)',
                        color: 'var(--rs-primary)',
                        svg: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
                        label: 'Notice'
                    };
            }
        },

        renderNotifications(items, unreadCount) {
            const self = this;
            if (!this.menuEl) return;
            this.hasRenderedMenu = true;
            this.menuEl.innerHTML = '';

            if (!items || items.length === 0) {
                self.renderEmpty();
                return;
            }

            // 1. Dropdown Header
            const header = document.createElement('div');
            header.className = 'rs-d-flex rs-align-items-center rs-justify-content-between';
            header.style.cssText = 'padding: 12px 16px; border-bottom: 1px solid var(--rs-border); background: var(--rs-bg-surface);';

            const headerLeft = document.createElement('div');
            headerLeft.className = 'rs-d-flex rs-align-items-center rs-gap-2';

            const title = document.createElement('span');
            title.className = 'rs-font-bold rs-text-main';
            title.style.cssText = 'font-size: 0.9375rem; color: #111827; letter-spacing: -0.01em;';
            title.textContent = 'Notifications';
            headerLeft.appendChild(title);

            if (unreadCount > 0) {
                const countBadge = document.createElement('span');
                countBadge.className = 'rs-badge';
                countBadge.id = 'rs-dropdown-unread-pill';
                countBadge.style.cssText = 'font-size: 0.7rem; padding: 2px 7px; border-radius: 999px; background: rgba(35, 65, 55, 0.1); color: var(--rs-primary); font-weight: 600;';
                countBadge.textContent = unreadCount + ' unread';
                headerLeft.appendChild(countBadge);
            }
            header.appendChild(headerLeft);

            if (unreadCount > 0) {
                const markAllBtn = document.createElement('button');
                markAllBtn.type = 'button';
                markAllBtn.id = 'rs-dropdown-mark-all-btn';
                markAllBtn.className = 'rs-btn rs-btn--ghost rs-btn--sm';
                markAllBtn.style.cssText = 'font-size: 0.75rem; padding: 3px 8px; color: var(--rs-primary); font-weight: 500; border-radius: 6px;';
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
            list.style.cssText = 'max-height: 380px; overflow-y: auto;';

            items.forEach(function(item) {
                const isUnread = !item.is_read;
                const meta = self.getTypeMeta(item.type);

                const row = document.createElement('a');
                row.href = item.action_url || '#';
                row.id = 'rs-dropdown-notif-' + item.id;
                row.className = 'rs-d-flex rs-align-items-start rs-text-decoration-none rs-dropdown-notif-row';
                row.style.cssText = 'padding: 12px 16px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); transition: background-color 0.15s ease; color: inherit; display: flex; align-items: flex-start; text-decoration: none; position: relative; gap: 12px; cursor: pointer;' +
                    (isUnread ? 'background: rgba(35, 65, 55, 0.035); border-left: 3px solid var(--rs-primary);' : 'background: #ffffff; border-left: 3px solid transparent;');

                row.addEventListener('mouseenter', function() {
                    row.style.backgroundColor = 'rgba(0, 0, 0, 0.025)';
                });
                row.addEventListener('mouseleave', function() {
                    row.style.backgroundColor = item.is_read ? '#ffffff' : 'rgba(35, 65, 55, 0.035)';
                });

                // Icon Avatar
                const iconBox = document.createElement('div');
                iconBox.style.cssText = 'flex-shrink: 0; width: 34px; height: 34px; border-radius: 8px; background: ' + meta.bg + '; color: ' + meta.color + '; display: flex; align-items: center; justify-content: center; margin-top: 2px;';
                iconBox.innerHTML = meta.svg;
                row.appendChild(iconBox);

                // Content Block
                const content = document.createElement('div');
                content.style.cssText = 'flex-grow: 1; min-width: 0;';

                // Top Header Row inside Item
                const topDiv = document.createElement('div');
                topDiv.className = 'rs-d-flex rs-align-items-center rs-justify-content-between rs-gap-2 rs-mb-1';

                const titleLeft = document.createElement('div');
                titleLeft.className = 'rs-d-flex rs-align-items-center rs-gap-1';
                titleLeft.style.cssText = 'min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';

                if (item.account_name && !item.title.startsWith('[' + item.account_name + ']')) {
                    const accBadge = document.createElement('span');
                    accBadge.className = 'rs-badge';
                    accBadge.style.cssText = 'font-size: 0.6875rem; padding: 1px 5px; border-radius: 4px; background: rgba(35, 65, 55, 0.08); color: var(--rs-primary); font-weight: 600; margin-right: 4px; flex-shrink: 0;';
                    accBadge.textContent = item.account_name;
                    titleLeft.appendChild(accBadge);
                }

                const itemTitle = document.createElement('span');
                itemTitle.className = isUnread ? 'rs-font-bold rs-text-main rs-dropdown-notif-title' : 'rs-font-medium rs-text-main rs-dropdown-notif-title';
                itemTitle.style.cssText = 'font-size: 0.8125rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
                itemTitle.textContent = item.title;
                titleLeft.appendChild(itemTitle);

                if (isUnread) {
                    const dot = document.createElement('span');
                    dot.className = 'rs-dropdown-unread-dot';
                    dot.style.cssText = 'width: 7px; height: 7px; border-radius: 50%; background: var(--rs-primary); flex-shrink: 0; display: inline-block; margin-left: 2px;';
                    titleLeft.appendChild(dot);
                }

                topDiv.appendChild(titleLeft);

                const itemTime = document.createElement('span');
                itemTime.className = 'rs-text-muted';
                itemTime.style.cssText = 'font-size: 0.6875rem; color: #9ca3af; white-space: nowrap; flex-shrink: 0;';
                itemTime.textContent = item.time_ago || '';
                topDiv.appendChild(itemTime);

                content.appendChild(topDiv);

                // Message preview
                if (item.message) {
                    const itemMsg = document.createElement('div');
                    itemMsg.className = 'rs-text-secondary';
                    itemMsg.style.cssText = 'font-size: 0.75rem; color: #6b7280; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 2px;';
                    itemMsg.textContent = item.message;
                    content.appendChild(itemMsg);
                }

                row.appendChild(content);

                // Click event: mark read then navigate
                row.addEventListener('click', function(e) {
                    if (item.action_url && item.action_url !== '#') {
                        e.preventDefault();
                        const dest = item.action_url;
                        if (!item.is_read) {
                            self.markAsRead(item.id, function() {
                                window.location.href = dest;
                            });
                        } else {
                            window.location.href = dest;
                        }
                    } else if (!item.is_read) {
                        e.preventDefault();
                        self.markAsRead(item.id);
                    }
                });

                list.appendChild(row);
            });

            this.menuEl.appendChild(list);

            // 3. Dropdown Footer
            if (self.urls.history) {
                const footer = document.createElement('div');
                footer.style.cssText = 'padding: 10px 16px; border-top: 1px solid var(--rs-border); background: #f9fafb; text-align: center;';

                const link = document.createElement('a');
                link.href = self.urls.history;
                link.className = 'rs-d-flex rs-align-items-center rs-justify-content-center rs-gap-1 rs-text-decoration-none';
                link.style.cssText = 'font-size: 0.8125rem; font-weight: 600; color: var(--rs-primary); display: flex; align-items: center; justify-content: center; gap: 4px; padding: 4px; border-radius: 6px; transition: color 0.15s ease;';
                link.innerHTML = '<span>View all notifications</span> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';

                footer.appendChild(link);
                this.menuEl.appendChild(footer);
            }
        },

        markAsRead(id, callback) {
            const self = this;
            if (!self.urls.markRead) {
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }

            const numId = parseInt(id, 10);

            // Immediately synchronize local state
            if (self.cachedItems && Array.isArray(self.cachedItems)) {
                const found = self.cachedItems.find(function(it) { return it.id === numId; });
                if (found && !found.is_read) {
                    found.is_read = true;
                    if (self.cachedUnreadCount > 0) {
                        self.cachedUnreadCount--;
                        self.updateBadge(self.cachedUnreadCount);
                    }
                }
            }

            // Immediately update dropdown row UI
            const row = document.getElementById('rs-dropdown-notif-' + id);
            if (row) {
                row.style.backgroundColor = '#ffffff';
                row.style.borderLeft = '3px solid transparent';
                const dot = row.querySelector('.rs-dropdown-unread-dot');
                if (dot) dot.remove();
                const title = row.querySelector('.rs-dropdown-notif-title');
                if (title) {
                    title.classList.remove('rs-font-bold');
                    title.classList.add('rs-font-medium');
                    title.style.fontWeight = '500';
                }
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
                    if (response && response.success && response.unread_count !== undefined) {
                        self.cachedUnreadCount = response.unread_count;
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

            if (btnEl) {
                btnEl.disabled = true;
                btnEl.textContent = 'Marking...';
            }

            // Immediately clear local state
            self.cachedUnreadCount = 0;
            self.updateBadge(0);
            if (self.cachedItems && Array.isArray(self.cachedItems)) {
                self.cachedItems.forEach(function(it) { it.is_read = true; });
            }

            const data = {};
            data[self.getCsrfParam()] = self.getCsrfToken();

            $.ajax({
                url: self.urls.markAllRead,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        self.cachedUnreadCount = 0;
                        self.updateBadge(0);
                        self.renderNotifications(self.cachedItems || [], 0);
                    } else if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.textContent = 'Mark all as read';
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

    // Expose NotificationManager globally for cross-page coordination
    window.NotificationManager = NotificationManager;

    // Initialize on DOM ready safely
    if (typeof $ === 'function') {
        $(document).ready(function() {
            NotificationManager.init();
        });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            NotificationManager.init();
        });
    } else {
        NotificationManager.init();
    }

})(typeof jQuery !== 'undefined' ? jQuery : (window.$ || {}));
