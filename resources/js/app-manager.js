import { SidebarScrollbar } from "./components/sidebar-scrollbar.js";
import { ChoicesManager } from "./components/choices-manager.js";

export class AppManager {
    constructor(overlayScrollbars, Choices) {
        this.overlayScrollbars = overlayScrollbars;
        this.Choices = Choices;
        this.sidebarScrollbar = null;
        this.choicesManager = null;
    }

    init() {
        this.initSidebarScrollbar();
        //this.initChoices();
    }

    initSidebarScrollbar() {
        this.sidebarScrollbar = SidebarScrollbar.init(this.overlayScrollbars);
    }

    initChoices() {
        this.choicesManager = ChoicesManager.init(this.Choices);
    }

    destroy() {
        if (this.sidebarScrollbar) {
            this.sidebarScrollbar.destroy();
        }

        if (this.choicesManager) {
            this.choicesManager.destroy();
        }
    }
}
