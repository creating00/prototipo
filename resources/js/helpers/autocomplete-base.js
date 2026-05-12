export default class AutocompleteBase {
    constructor(config) {
        this.input = document.querySelector(config.inputSelector);
        this.resultsList = document.querySelector(config.resultsListSelector);
        this.spinner = document.querySelector(config.spinnerSelector);
        this.template = document.querySelector(config.templateSelector);

        this.currentIndex = -1;
        this.debounceTimeout = null;
        this.abortController = null;
        this.cache = {};

        if (this.input) this.initEvents();
    }

    initEvents() {
        this.input.addEventListener("input", () => this.handleInput());
        this.input.addEventListener("keydown", (e) => this.handleNavigation(e));
        document.addEventListener("click", (e) => {
            if (
                !this.input.contains(e.target) &&
                !this.resultsList?.contains(e.target)
            ) {
                this.hideResults();
            }
        });
    }

    handleInput() {
        clearTimeout(this.debounceTimeout);
        const query = this.input.value.trim();

        if (query.length < 2) {
            this.cancelSearch();
            this.hideResults();
            return;
        }

        if (this.cache[query]) {
            this.renderResults(this.cache[query]);
            return;
        }

        this.debounceTimeout = setTimeout(() => this.search(query), 300);
    }

    cancelSearch() {
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
    }

    handleNavigation(e) {
        const items = this.resultsList.querySelectorAll(".dropdown-item");
        if (!items.length) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            this.currentIndex = Math.min(
                this.currentIndex + 1,
                items.length - 1
            );
            this.highlightItem(items);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            this.currentIndex = Math.max(this.currentIndex - 1, 0);
            this.highlightItem(items);
        } else if (e.key === "Enter" && this.currentIndex >= 0) {
            e.preventDefault();
            items[this.currentIndex].click();
        } else if (e.key === "Escape") {
            this.hideResults();
        }
    }

    highlightItem(items) {
        items.forEach((item, index) => {
            item.classList.toggle("active", index === this.currentIndex);
            if (index === this.currentIndex)
                item.scrollIntoView({ block: "nearest" });
        });
    }

    showResults() {
        this.resultsList.style.display = "block";
        this.resultsList.classList.add("show");
    }

    renderResults(products) {}

    hideResults() {
        if (this.resultsList) {
            this.resultsList.style.display = "none";
            this.resultsList.classList.remove("show");
        }
        this.currentIndex = -1;
    }

    clear() {
        if (this.input) {
            this.input.value = "";
        }
        this.hideResults();
        this.cancelSearch();
        this.currentIndex = -1;
        // Opcional: limpiar cache si los productos dependen del tipo de venta
        // this.cache = {};
    }
}
