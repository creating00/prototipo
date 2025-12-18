document.addEventListener("DOMContentLoaded", () => {
    const selects = document.querySelectorAll("select[data-choices-config]");

    selects.forEach((el) => {
        const config = JSON.parse(el.getAttribute("data-choices-config"));

        new Choices(el, {
            searchEnabled: config.searchEnabled === true,
            itemSelectText: "",
            placeholder: true,
            placeholderValue: config.placeholderValue,
            removeItemButton: config.removeItemButton === true,
            shouldSort: false,
        });
    });
});
