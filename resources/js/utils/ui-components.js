import flatpickr from "flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";
import { Spanish } from "flatpickr/dist/l10n/es.js";
import "flatpickr/dist/flatpickr.css";
import "flatpickr/dist/plugins/monthSelect/style.css";

/**
 * Inicializa un selector de fecha o mes usando Flatpickr.
 */
export function initMonthPicker(elementId, callback, mode = "month") {
    const input = document.getElementById(elementId);

    if (!input) return;

    if (input._flatpickr) {
        input._flatpickr.destroy();
    }

    if (mode === "month") {
        input.placeholder = "Seleccionar mes";
        return flatpickr(input, {
            locale: Spanish,
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y",
                }),
            ],
            onChange: function (selectedDates, dateStr) {
                input.value = dateStr;
                if (typeof callback === "function") callback(dateStr);
            },
        });
    } else {
        input.placeholder = "Seleccionar fecha";
        return flatpickr(input, {
            locale: Spanish,
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            onChange: function (selectedDates, dateStr) {
                input.value = dateStr;
                if (typeof callback === "function") callback(dateStr);
            },
        });
    }
}
