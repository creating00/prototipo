// resources/js/modules/sales/services/FieldSyncer.js

const FieldSyncer = {
    sync: function (map) {
        Object.entries(map).forEach(([modalId, hiddenId]) => {
            const modalEl = document.getElementById(modalId);
            const hiddenEl = document.getElementById(hiddenId);

            if (modalEl && hiddenEl) {
                const update = () => {
                    if (hiddenEl.value !== modalEl.value) {
                        hiddenEl.value = modalEl.value;
                    }
                };
                ["input", "change"].forEach((ev) =>
                    modalEl.addEventListener(ev, update),
                );
                update();
            }
        });
    },
};

export default FieldSyncer;
