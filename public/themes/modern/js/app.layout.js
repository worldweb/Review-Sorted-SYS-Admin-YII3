/**
 * Reviews Sorted Enterprise - Application Shell Controllers (v8.0)
 * Architecture: Modular Vanilla JS Controllers
 */

const RSIteration = (function() {
    'use strict';

    // =========================================================================
    // 1. Sidebar Controller
    // =========================================================================
    const SidebarController = {
        elements: {},
        
        init() {
            this.elements.sidebar = document.querySelector('.rs-sidebar');
            this.elements.toggleBtn = document.querySelector('.rs-header__action-btn--toggle-sidebar');
            this.elements.body = document.body;
            this.elements.layoutBody = document.querySelector('.rs-layout-body');
            
            if (!this.elements.sidebar || !this.elements.toggleBtn) return;

            // Create mobile overlay
            if (!document.querySelector('.rs-sidebar-overlay')) {
                this.elements.overlay = document.createElement('div');
                this.elements.overlay.className = 'rs-sidebar-overlay';
                if(this.elements.layoutBody) {
                    this.elements.layoutBody.appendChild(this.elements.overlay);
                }
            } else {
                this.elements.overlay = document.querySelector('.rs-sidebar-overlay');
            }

            this.restoreState();
            this.bindEvents();
        },

        restoreState() {
            if (window.innerWidth >= 992) {
                const isCollapsed = localStorage.getItem('rs-sidebar-collapsed') === 'true';
                if (isCollapsed) {
                    this.elements.sidebar.classList.add('rs-sidebar--collapsed');
                    this.elements.sidebar.classList.remove('rs-sidebar--expanded');
                } else {
                    this.elements.sidebar.classList.remove('rs-sidebar--collapsed');
                    this.elements.sidebar.classList.add('rs-sidebar--expanded');
                }
            }
        },

        bindEvents() {
            this.toggleHandler = this.toggleSidebar.bind(this);
            this.overlayHandler = this.closeMobileDrawer.bind(this);
            this.resizeHandler = this.handleResize.bind(this);

            this.elements.toggleBtn.addEventListener('click', this.toggleHandler);
            if(this.elements.overlay) this.elements.overlay.addEventListener('click', this.overlayHandler);
            window.addEventListener('resize', this.resizeHandler);
        },

        unbindEvents() {
            if(this.elements.toggleBtn) this.elements.toggleBtn.removeEventListener('click', this.toggleHandler);
            if(this.elements.overlay) this.elements.overlay.removeEventListener('click', this.overlayHandler);
            window.removeEventListener('resize', this.resizeHandler);
        },

        toggleSidebar() {
            if (window.innerWidth >= 992) {
                this.elements.sidebar.classList.toggle('rs-sidebar--collapsed');
                this.elements.sidebar.classList.toggle('rs-sidebar--expanded');
                const isNowCollapsed = this.elements.sidebar.classList.contains('rs-sidebar--collapsed');
                localStorage.setItem('rs-sidebar-collapsed', isNowCollapsed);
            } else {
                this.elements.sidebar.classList.toggle('rs-sidebar--mobile-open');
                if(this.elements.overlay) this.elements.overlay.classList.toggle('rs-sidebar-overlay--active');
                
                if (this.elements.sidebar.classList.contains('rs-sidebar--mobile-open')) {
                    this.elements.body.style.overflow = 'hidden';
                } else {
                    this.elements.body.style.overflow = '';
                }
            }
        },

        closeMobileDrawer() {
            this.elements.sidebar.classList.remove('rs-sidebar--mobile-open');
            if(this.elements.overlay) this.elements.overlay.classList.remove('rs-sidebar-overlay--active');
            this.elements.body.style.overflow = '';
        },

        handleResize() {
            if (window.innerWidth >= 992) {
                this.closeMobileDrawer();
                this.restoreState();
            } else {
                this.elements.sidebar.classList.remove('rs-sidebar--collapsed');
                this.elements.sidebar.classList.remove('rs-sidebar--expanded');
            }
        },

        destroy() {
            this.unbindEvents();
        },
        
        reinitialize() {
            this.destroy();
            this.init();
        }
    };

    // =========================================================================
    // 2. Dropdown Controller
    // =========================================================================
    const DropdownController = {
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            this.clickHandler = this.handleClick.bind(this);
            this.keyHandler = this.handleKeydown.bind(this);
            document.addEventListener('click', this.clickHandler);
            document.addEventListener('keydown', this.keyHandler);
        },
        
        unbindEvents() {
            document.removeEventListener('click', this.clickHandler);
            document.removeEventListener('keydown', this.keyHandler);
        },
        
        handleClick(e) {
            const toggleBtn = e.target.closest('[data-toggle="dropdown"]');
            
            // If clicking a toggle button
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = toggleBtn.closest('.rs-dropdown');
                if (!dropdown) return;

                const isOpen = dropdown.classList.contains('rs-dropdown--open');
                
                // Close all other dropdowns
                this.closeAll();
                
                if (!isOpen) {
                    dropdown.classList.add('rs-dropdown--open');
                    toggleBtn.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            
            // If clicking outside, close all
            if (!e.target.closest('.rs-dropdown')) {
                this.closeAll();
            }
        },
        
        handleKeydown(e) {
            if (e.key === 'Escape') {
                this.closeAll();
            }
        },
        
        closeAll() {
            const openDropdowns = document.querySelectorAll('.rs-dropdown--open');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('rs-dropdown--open');
                const toggle = dropdown.querySelector('[data-toggle="dropdown"]');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
        },
        
        destroy() {
            this.unbindEvents();
            this.closeAll();
        },
        
        reinitialize() {
            this.destroy();
            this.init();
        }
    };

    // =========================================================================
    // 3. Modal Controller
    // =========================================================================
    const ModalController = {
        openModals: [],
        triggerElements: {},
        
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            this.clickHandler = this.handleClick.bind(this);
            this.keyHandler = this.handleKeydown.bind(this);
            document.addEventListener('click', this.clickHandler);
            document.addEventListener('keydown', this.keyHandler);
        },
        
        unbindEvents() {
            document.removeEventListener('click', this.clickHandler);
            document.removeEventListener('keydown', this.keyHandler);
        },
        
        handleClick(e) {
            const toggleBtn = e.target.closest('[data-toggle="modal"]');
            if (toggleBtn) {
                e.preventDefault();
                const targetId = toggleBtn.getAttribute('data-target');
                if (targetId) this.open(targetId, toggleBtn);
                return;
            }
            
            const closeBtn = e.target.closest('[data-dismiss="modal"]');
            if (closeBtn) {
                e.preventDefault();
                const modal = closeBtn.closest('.rs-modal-backdrop');
                if (modal) this.close(modal.id);
                return;
            }
            
            // Close on backdrop click
            if (e.target.classList.contains('rs-modal-backdrop')) {
                this.close(e.target.id);
            }
        },
        
        handleKeydown(e) {
            if (e.key === 'Escape' && this.openModals.length > 0) {
                const topModalId = this.openModals[this.openModals.length - 1];
                this.close(topModalId);
            }
        },
        
        open(modalId, triggerEl) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            if (triggerEl) {
                this.triggerElements[modalId] = triggerEl;
            } else if (document.activeElement) {
                this.triggerElements[modalId] = document.activeElement;
            }

            modal.classList.add('rs-modal-backdrop--open');
            document.body.style.overflow = 'hidden';
            
            // Accessibility focus management
            modal.setAttribute('aria-hidden', 'false');
            const focusable = modal.querySelector('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if(focusable) focusable.focus();
            
            this.openModals.push(modalId);
        },
        
        close(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            modal.classList.remove('rs-modal-backdrop--open');
            modal.setAttribute('aria-hidden', 'true');
            
            this.openModals = this.openModals.filter(id => id !== modalId);
            
            if (this.openModals.length === 0) {
                document.body.style.overflow = '';
            }

            if (this.triggerElements[modalId]) {
                try {
                    this.triggerElements[modalId].focus();
                } catch (err) {}
                delete this.triggerElements[modalId];
            }
        },
        
        destroy() {
            this.unbindEvents();
            this.openModals.forEach(id => this.close(id));
        },
        
        reinitialize() {
            this.destroy();
            this.init();
        }
    };

    // =========================================================================
    // 4. Command Palette Controller
    // =========================================================================
    const CommandPaletteController = {
        init() {
            this.modalId = 'rs-command-palette-modal';
            this.modal = document.getElementById(this.modalId);
            if (!this.modal) return;

            this.input = this.modal.querySelector('.rs-command-palette__input');
            this.body = this.modal.querySelector('.rs-command-palette__body');
            this.searchUrl = this.modal.getAttribute('data-search-url') || '/site/search';
            
            this.debounceTimer = null;
            this.abortController = null;
            this.currentQuery = '';
            this.activeIndex = -1;
            this.items = [];

            this.bindEvents();
        },
        
        bindEvents() {
            this.keyHandler = this.handleKeydown.bind(this);
            this.clickHandler = this.handleClick.bind(this);
            this.inputHandler = this.handleInput.bind(this);
            this.modalKeyHandler = this.handleModalKeydown.bind(this);
            this.itemClickHandler = this.handleItemClick.bind(this);
            this.itemHoverHandler = this.handleItemHover.bind(this);

            document.addEventListener('keydown', this.keyHandler);
            document.addEventListener('click', this.clickHandler);

            if (this.input) {
                this.input.addEventListener('input', this.inputHandler);
                this.input.addEventListener('keydown', this.modalKeyHandler);
            }

            if (this.body) {
                this.body.addEventListener('click', this.itemClickHandler);
                this.body.addEventListener('mouseover', this.itemHoverHandler);
            }
        },
        
        unbindEvents() {
            document.removeEventListener('keydown', this.keyHandler);
            document.removeEventListener('click', this.clickHandler);

            if (this.input) {
                this.input.removeEventListener('input', this.inputHandler);
                this.input.removeEventListener('keydown', this.modalKeyHandler);
            }

            if (this.body) {
                this.body.removeEventListener('click', this.itemClickHandler);
                this.body.removeEventListener('mouseover', this.itemHoverHandler);
            }

            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            if (this.abortController) {
                this.abortController.abort();
            }
        },
        
        handleClick(e) {
            const searchTrigger = e.target.closest('.rs-header__search');
            if (searchTrigger) {
                this.openPalette(searchTrigger);
            }
        },

        handleKeydown(e) {
            // Cmd+K or Ctrl+K
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.openPalette();
                return;
            }
            // Enter or Space when focused on search trigger
            if (e.target && e.target.closest('.rs-header__search') && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                this.openPalette(e.target.closest('.rs-header__search'));
            }
        },

        openPalette(triggerEl) {
            ModalController.open(this.modalId, triggerEl);
            if (this.input) {
                setTimeout(() => {
                    this.input.focus();
                    this.input.select();
                }, 50);
            }
        },

        handleInput(e) {
            const query = e.target.value.trim();
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            if (query === '') {
                if (this.abortController) {
                    this.abortController.abort();
                }
                this.currentQuery = '';
                this.renderInitialState();
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.performSearch(query);
            }, 250);
        },

        handleModalKeydown(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.moveActive(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.moveActive(-1);
            } else if (e.key === 'Enter') {
                if (this.activeIndex >= 0 && this.items[this.activeIndex]) {
                    e.preventDefault();
                    const url = this.items[this.activeIndex].getAttribute('data-url');
                    if (url) {
                        this.navigateTo(url);
                    }
                }
            }
        },

        handleItemClick(e) {
            const item = e.target.closest('.rs-command-palette__item');
            if (item) {
                const url = item.getAttribute('data-url');
                if (url) {
                    this.navigateTo(url);
                }
            }
        },

        handleItemHover(e) {
            const item = e.target.closest('.rs-command-palette__item');
            if (item && this.items.length > 0) {
                const index = this.items.indexOf(item);
                if (index !== -1 && index !== this.activeIndex) {
                    this.setActiveIndex(index, false);
                }
            }
        },

        moveActive(direction) {
            if (this.items.length === 0) return;
            let nextIndex = this.activeIndex + direction;
            if (nextIndex < 0) {
                nextIndex = this.items.length - 1;
            } else if (nextIndex >= this.items.length) {
                nextIndex = 0;
            }
            this.setActiveIndex(nextIndex, true);
        },

        setActiveIndex(index, shouldScroll = true) {
            this.items.forEach((item, i) => {
                if (i === index) {
                    item.classList.add('rs-command-palette__item--active');
                    item.setAttribute('aria-selected', 'true');
                    if (shouldScroll) {
                        item.scrollIntoView({ block: 'nearest' });
                    }
                } else {
                    item.classList.remove('rs-command-palette__item--active');
                    item.removeAttribute('aria-selected');
                }
            });
            this.activeIndex = index;
        },

        navigateTo(url) {
            ModalController.close(this.modalId);
            window.location.href = url;
        },

        async performSearch(query) {
            if (this.abortController) {
                this.abortController.abort();
            }
            this.abortController = new AbortController();
            this.currentQuery = query;

            this.renderLoading();

            try {
                const searchParams = new URLSearchParams({ q: query });
                const separator = this.searchUrl.includes('?') ? '&' : '?';
                const url = `${this.searchUrl}${separator}${searchParams.toString()}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: this.abortController.signal
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                if (this.currentQuery === query) {
                    this.renderResults(data, query);
                }
            } catch (err) {
                if (err.name === 'AbortError') return;
                this.renderError();
            }
        },

        renderInitialState() {
            if (!this.body) return;
            this.items = [];
            this.activeIndex = -1;

            this.body.className = 'rs-command-palette__body rs-command-palette__body--empty';
            this.body.innerHTML = '';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'rs-command-palette__empty-icon';
            iconWrap.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';

            const title = document.createElement('p');
            title.className = 'rs-command-palette__status-title';
            title.textContent = 'Quick Search & Navigation';

            const desc = document.createElement('p');
            desc.className = 'rs-command-palette__status-desc';
            desc.textContent = 'Type to search accounts, users, settings, or reviews across the system.';

            this.body.appendChild(iconWrap);
            this.body.appendChild(title);
            this.body.appendChild(desc);
        },

        renderLoading() {
            if (!this.body) return;
            this.items = [];
            this.activeIndex = -1;

            this.body.className = 'rs-command-palette__body';
            this.body.innerHTML = '';

            const wrap = document.createElement('div');
            wrap.className = 'rs-command-palette__loading';

            const spinner = document.createElement('div');
            spinner.className = 'rs-command-palette__spinner';

            const text = document.createElement('span');
            text.textContent = 'Searching...';

            wrap.appendChild(spinner);
            wrap.appendChild(text);
            this.body.appendChild(wrap);
        },

        renderResults(data, query) {
            if (!this.body) return;
            this.body.className = 'rs-command-palette__body';
            this.body.innerHTML = '';
            this.items = [];
            this.activeIndex = -1;

            if (!data || !data.success || !data.categories || data.categories.length === 0 || data.total === 0) {
                this.renderNoResults(query);
                return;
            }

            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'rs-command-palette__results';

            data.categories.forEach(category => {
                if (!category.items || category.items.length === 0) return;

                const catBlock = document.createElement('div');
                catBlock.className = 'rs-command-palette__category';

                const catHeader = document.createElement('div');
                catHeader.className = 'rs-command-palette__category-header';
                
                const catIcon = document.createElement('span');
                catIcon.className = 'rs-command-palette__category-icon';
                catIcon.innerHTML = this.getCategoryIcon(category.icon);

                const catTitle = document.createElement('span');
                catTitle.textContent = category.name;

                catHeader.appendChild(catIcon);
                catHeader.appendChild(catTitle);
                catBlock.appendChild(catHeader);

                const list = document.createElement('ul');
                list.className = 'rs-command-palette__list';
                list.setAttribute('role', 'listbox');

                category.items.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'rs-command-palette__item';
                    li.setAttribute('role', 'option');
                    li.setAttribute('data-url', item.url);
                    li.setAttribute('tabindex', '-1');

                    const contentWrap = document.createElement('div');
                    contentWrap.className = 'rs-command-palette__item-content';

                    const itemTitle = document.createElement('div');
                    itemTitle.className = 'rs-command-palette__item-title';
                    itemTitle.textContent = item.title;

                    const itemSubtitle = document.createElement('div');
                    itemSubtitle.className = 'rs-command-palette__item-subtitle';
                    itemSubtitle.textContent = item.subtitle;

                    contentWrap.appendChild(itemTitle);
                    contentWrap.appendChild(itemSubtitle);

                    const actionWrap = document.createElement('div');
                    actionWrap.className = 'rs-command-palette__item-action';
                    actionWrap.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';

                    li.appendChild(contentWrap);
                    li.appendChild(actionWrap);
                    list.appendChild(li);

                    this.items.push(li);
                });

                catBlock.appendChild(list);
                resultsContainer.appendChild(catBlock);
            });

            this.body.appendChild(resultsContainer);

            // Auto-highlight first result
            if (this.items.length > 0) {
                this.setActiveIndex(0, false);
            }
        },

        renderNoResults(query) {
            if (!this.body) return;
            this.body.className = 'rs-command-palette__body rs-command-palette__body--empty';
            this.body.innerHTML = '';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'rs-command-palette__empty-icon';
            iconWrap.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';

            const title = document.createElement('p');
            title.className = 'rs-command-palette__status-title';
            title.textContent = 'No results found';

            const desc = document.createElement('p');
            desc.className = 'rs-command-palette__status-desc';
            desc.textContent = `No matches found for "${query}". Try searching by name, email, or section.`;

            this.body.appendChild(iconWrap);
            this.body.appendChild(title);
            this.body.appendChild(desc);
        },

        renderError() {
            if (!this.body) return;
            this.body.className = 'rs-command-palette__body rs-command-palette__body--empty';
            this.body.innerHTML = '';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'rs-command-palette__empty-icon';
            iconWrap.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

            const title = document.createElement('p');
            title.className = 'rs-command-palette__status-title';
            title.textContent = 'Unable to complete search';

            const desc = document.createElement('p');
            desc.className = 'rs-command-palette__status-desc';
            desc.textContent = 'An error occurred while connecting to the search service. Please try again.';

            this.body.appendChild(iconWrap);
            this.body.appendChild(title);
            this.body.appendChild(desc);
        },

        getCategoryIcon(icon) {
            switch (icon) {
                case 'compass':
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>';
                case 'briefcase':
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>';
                case 'user':
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
                case 'star':
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
                default:
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
            }
        },

        destroy() {
            this.unbindEvents();
        },
        
        reinitialize() {
            this.destroy();
            this.init();
        }
    };

    
    // =========================================================================
    // 5. PJAX Controller
    // =========================================================================
    const PJAXController = {
        init() {
            this.pjaxEndHandler = this.handlePjaxEnd.bind(this);
            this.pjaxSendHandler = this.handlePjaxSend.bind(this);
            
            // Use jQuery to ensure reliable capture of custom PJAX events
            if (typeof jQuery !== 'undefined') {
                jQuery(document).on('pjax:send', this.pjaxSendHandler);
                jQuery(document).on('pjax:end', this.pjaxEndHandler);
            } else {
                document.addEventListener('pjax:send', this.pjaxSendHandler);
                document.addEventListener('pjax:end', this.pjaxEndHandler);
            }
        },
        
        unbindEvents() {
            if (typeof jQuery !== 'undefined') {
                jQuery(document).off('pjax:send', this.pjaxSendHandler);
                jQuery(document).off('pjax:end', this.pjaxEndHandler);
            } else {
                document.removeEventListener('pjax:send', this.pjaxSendHandler);
                document.removeEventListener('pjax:end', this.pjaxEndHandler);
            }
        },
        
        shouldShowGlobalLoader(event) {
            // e.relatedTarget exists during user-initiated events (click, change, form submit)
            // It is undefined during silent programmatic background reloads ($.pjax.reload)
            return event && event.relatedTarget !== undefined;
        },

        showGlobalLoader() {
            const loader = document.getElementById('rs-global-loader');
            if (loader) {
                loader.classList.remove('rs-d-none');
            }
        },

        hideGlobalLoader() {
            const loader = document.getElementById('rs-global-loader');
            if (loader) {
                loader.classList.add('rs-d-none');
            }
        },
        
        handlePjaxSend(e) {
            if (this.shouldShowGlobalLoader(e)) {
                this.showGlobalLoader();
            }
        },

        handlePjaxEnd(e) {
            this.hideGlobalLoader();

            SidebarController.reinitialize();
            DropdownController.reinitialize();
            ModalController.reinitialize();
            CommandPaletteController.reinitialize();
        },
        
        destroy() {
            this.unbindEvents();
        }
    };

    // =========================================================================
    // Application Layout Engine Bootstrap
    // =========================================================================
    const AppLayoutEngine = {
        init() {
            SidebarController.init();
            DropdownController.init();
            ModalController.init();
            CommandPaletteController.init();
            PJAXController.init();
        },
        
        destroy() {
            SidebarController.destroy();
            DropdownController.destroy();
            ModalController.destroy();
            CommandPaletteController.destroy();
            PJAXController.destroy();
        }
    };

    return AppLayoutEngine;
})();

// Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    RSIteration.init();
});
