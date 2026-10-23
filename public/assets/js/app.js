/**
 * Launder LMS - Core Application Scripts
 * Slice 1: Mobile Drawer Navigation, Active State, Flash Dismissal
 */

(function () {
    "use strict";

    function ready(fn) {
        if (document.readyState !== "loading") {
            fn();
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    ready(() => {
        initMobileDrawer();
        initFlashDismissal();
        initTheme();
        updateToggleIcons();
    });

    /**
     * Mobile Navigation Drawer Handler with Keyboard Focus Trap
     */
    function initMobileDrawer() {
        const openBtn = document.getElementById("mobile-menu-open");
        const closeBtn = document.getElementById("mobile-menu-close");
        const container = document.getElementById("mobile-drawer-container");
        const backdrop = document.getElementById("mobile-drawer-backdrop");
        const drawer = document.getElementById("mobile-drawer");

        if (!openBtn || !container || !drawer) {
            return;
        }

        let isOpen = false;

        function getFocusableElements(element) {
            if (!element) return [];
            const selector = [
                'a[href]:not([tabindex="-1"])',
                'button:not([disabled]):not([tabindex="-1"])',
                'input:not([disabled]):not([type="hidden"]):not([tabindex="-1"])',
                'select:not([disabled]):not([tabindex="-1"])',
                'textarea:not([disabled]):not([tabindex="-1"])',
                '[tabindex]:not([tabindex="-1"])'
            ].join(', ');

            return Array.from(element.querySelectorAll(selector)).filter((el) => {
                return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
            });
        }

        function handleKeyDown(e) {
            if (!isOpen) return;

            if (e.key === "Escape") {
                e.preventDefault();
                closeDrawer();
                return;
            }

            if (e.key === "Tab") {
                const focusables = getFocusableElements(drawer);
                if (focusables.length === 0) {
                    e.preventDefault();
                    return;
                }

                const firstEl = focusables[0];
                const lastEl = focusables[focusables.length - 1];

                if (e.shiftKey) {
                    // Shift + Tab: from first element cycle to last element
                    if (document.activeElement === firstEl || !drawer.contains(document.activeElement)) {
                        e.preventDefault();
                        lastEl.focus();
                    }
                } else {
                    // Tab: from last element cycle to first element
                    if (document.activeElement === lastEl || !drawer.contains(document.activeElement)) {
                        e.preventDefault();
                        firstEl.focus();
                    }
                }
            }
        }

        function openDrawer() {
            if (isOpen) return;
            isOpen = true;

            // Make visible in DOM
            container.classList.remove("hidden");
            openBtn.setAttribute("aria-expanded", "true");

            // Prevent touch scrolling on backdrop
            if (backdrop) {
                backdrop.addEventListener("touchmove", preventScroll, { passive: false });
            }

            // Bind keyboard trap only while open
            document.addEventListener("keydown", handleKeyDown);

            // Trigger animation frame for CSS transitions
            requestAnimationFrame(() => {
                if (backdrop) {
                    backdrop.classList.remove("opacity-0");
                    backdrop.classList.add("opacity-100");
                }
                drawer.classList.remove("-translate-x-full");
                drawer.classList.add("translate-x-0");
            });

            // Focus management: move focus into drawer (close button / first focusable)
            const focusables = getFocusableElements(drawer);
            if (focusables.length > 0) {
                focusables[0].focus();
            } else if (closeBtn) {
                closeBtn.focus();
            }
        }

        function closeDrawer() {
            if (!isOpen) return;
            isOpen = false;

            openBtn.setAttribute("aria-expanded", "false");

            // Remove keyboard trap listener immediately
            document.removeEventListener("keydown", handleKeyDown);

            if (backdrop) {
                backdrop.removeEventListener("touchmove", preventScroll);
                backdrop.classList.remove("opacity-100");
                backdrop.classList.add("opacity-0");
            }
            drawer.classList.remove("translate-x-0");
            drawer.classList.add("-translate-x-full");

            // Wait for transition duration (300ms) before hiding
            setTimeout(() => {
                if (!isOpen) {
                    container.classList.add("hidden");
                }
            }, 300);

            // Return focus to trigger button
            if (openBtn) {
                openBtn.focus();
            }
        }

        function preventScroll(e) {
            e.preventDefault();
        }

        openBtn.addEventListener("click", openDrawer);

        if (closeBtn) {
            closeBtn.addEventListener("click", closeDrawer);
        }

        if (backdrop) {
            backdrop.addEventListener("click", closeDrawer);
        }

        // Close when clicking any nav link inside drawer
        const navLinks = drawer.querySelectorAll("nav a, a");
        navLinks.forEach((link) => {
            link.addEventListener("click", () => {
                if (isOpen) {
                    closeDrawer();
                }
            });
        });

        // Close drawer if viewport is resized to desktop width (>= 768px)
        window.addEventListener("resize", () => {
            if (window.innerWidth >= 768 && isOpen) {
                closeDrawer();
            }
        });
    }

    /**
     * Flash Notification Dismissal via Event Delegation
     */
    function initFlashDismissal() {
        document.addEventListener("click", (e) => {
            const btn = e.target.closest("[data-flash-dismiss]");
            if (btn) {
                const alertEl = btn.closest("[data-flash-alert]");
                if (alertEl) {
                    alertEl.style.transition = "opacity 0.2s ease, transform 0.2s ease";
                    alertEl.style.opacity = "0";
                    alertEl.style.transform = "translateY(-4px)";
                    setTimeout(() => {
                        alertEl.remove();
                    }, 200);
                }
            }
        });
    }

    /**
     * Theme Management (Dark / Light Mode)
     */
     function initTheme() {
         window.addEventListener("storage", (e) => {
             if (e.key === "lms_theme") {
                 applyTheme(e.newValue);
             }
         });

         document.addEventListener("click", (e) => {
             const toggle = e.target.closest("[data-theme-toggle]");
             if (toggle) {
                 if (toggle._lastToggle && (Date.now() - toggle._lastToggle < 300)) {
                     return;
                 }
                 toggle._lastToggle = Date.now();
                 window.toggleTheme(e);
             }
         });
     }

     function applyTheme(theme) {
         if (theme === "dark") {
             document.documentElement.classList.add("dark");
         } else {
             document.documentElement.classList.remove("dark");
         }
         updateToggleIcons();
     }

     function setTheme(theme) {
         try {
             localStorage.setItem("lms_theme", theme);
         } catch (e) {}
         applyTheme(theme);
     }

     function updateToggleIcons() {
         const isDark = document.documentElement.classList.contains("dark");
         const toggles = document.querySelectorAll("[data-theme-toggle]");
         toggles.forEach(btn => {
             const moonIcon = btn.querySelector("[data-theme-icon-moon]");
             const sunIcon = btn.querySelector("[data-theme-icon-sun]");
             if (moonIcon && sunIcon) {
                 if (isDark) {
                     moonIcon.classList.add("hidden");
                     sunIcon.classList.remove("hidden");
                     btn.setAttribute("aria-label", "Switch to light mode");
                     btn.setAttribute("title", "Switch to light mode");
                 } else {
                     moonIcon.classList.remove("hidden");
                     sunIcon.classList.add("hidden");
                     btn.setAttribute("aria-label", "Switch to dark mode");
                     btn.setAttribute("title", "Switch to dark mode");
                 }
             }
         });
     }

     window.toggleTheme = function(e) {
         if (e && e.stopPropagation) e.stopPropagation();
         const currentTheme = document.documentElement.classList.contains("dark") ? "dark" : "light";
         setTheme(currentTheme === "dark" ? "light" : "dark");
     };

    /**
     * Global Helper for Custom Date Toggles
     */
    window.toggleCustomDates = function (prefix) {
        const filterEl = document.getElementById(prefix + "_filter");
        const customDiv = document.getElementById(prefix + "_custom_inputs");
        if (!filterEl || !customDiv) return;

        if (filterEl.value === "custom") {
            customDiv.classList.remove("hidden");
            customDiv.classList.add("flex");
        } else {
            customDiv.classList.add("hidden");
            customDiv.classList.remove("flex");
        }
    };
})();