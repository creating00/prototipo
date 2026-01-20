// resources/js/modules/sales/services/FieldSyncer.js
const FieldSyncer = {
    sync: function (map) {
        Object.entries(map).forEach(([modalId, hiddenId]) => {
            const modalEl = document.getElementById(modalId);
            const hiddenEl = document.getElementById(hiddenId);

            if (modalEl && hiddenEl) {
                const update = () => {
                    hiddenEl.value = modalEl.value;
                };

                // Escuchamos eventos de usuario y manuales
                ["input", "change"].forEach((ev) =>
                    modalEl.addEventListener(ev, update)
                );

                // Ejecución inmediata inicial
                update();
            }
        });
    },
};

export default FieldSyncer;
