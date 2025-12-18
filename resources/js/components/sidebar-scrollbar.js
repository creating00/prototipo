export class SidebarScrollbar {
    static SELECTOR_SIDEBAR_WRAPPER = ".sidebar-wrapper";
    static DEFAULT_CONFIG = {
        scrollbarTheme: "os-theme-light",
        scrollbarAutoHide: "leave",
        scrollbarClickScroll: true,
    };

    constructor(overlayScrollbars) {
        this.overlayScrollbars = overlayScrollbars;
        this.sidebarWrapper = null;
        this.instance = null;
    }

    init() {
        this.sidebarWrapper = document.querySelector(
            SidebarScrollbar.SELECTOR_SIDEBAR_WRAPPER
        );

        if (!this.sidebarWrapper) {
            console.warn("Sidebar wrapper not found");
            return;
        }

        this.instance = this.overlayScrollbars(this.sidebarWrapper, {
            scrollbars: {
                theme: SidebarScrollbar.DEFAULT_CONFIG.scrollbarTheme,
                autoHide: SidebarScrollbar.DEFAULT_CONFIG.scrollbarAutoHide,
                clickScroll:
                    SidebarScrollbar.DEFAULT_CONFIG.scrollbarClickScroll,
            },
        });

        return this.instance;
    }

    destroy() {
        if (this.instance) {
            this.instance.destroy();
            this.instance = null;
        }
    }

    static init(overlayScrollbars) {
        const sidebarScrollbar = new SidebarScrollbar(overlayScrollbars);
        return sidebarScrollbar.init();
    }
}
