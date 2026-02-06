import flatpickr from "flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";
import { Spanish } from "flatpickr/dist/l10n/es.js";
import "flatpickr/dist/flatpickr.css";
import "flatpickr/dist/plugins/monthSelect/style.css";

/**
 * Inicializa un selector de mes y año usando Flatpickr.
 */
export function initMonthPicker(elementId, callback) {
    const monthInput = document.getElementById(elementId);

    if (monthInput) {
        return flatpickr(monthInput, {
            locale: Spanish,
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y",
                }),
            ],
            onChange: function (selectedDates, dateStr) {
                monthInput.value = dateStr;
                if (typeof callback === "function") callback(dateStr);
            },
        });
    }
}
