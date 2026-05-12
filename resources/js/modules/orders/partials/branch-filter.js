export default {
    init() {
        // Esperar un momento para que el DOM esté completamente cargado
        setTimeout(() => {
            this.setupBranchFilter();
        }, 100);
    },

    setupBranchFilter() {
        const hiddenBranchInput = document.querySelector(
            'input[name="branch_id"]'
        );
        const branchRecipientSelect = document.querySelector(
            'select[name="branch_recipient_id"]'
        );
        const branchSelect = document.querySelector('select[name="branch_id"]');

        // Create / Traspaso
        if (hiddenBranchInput && branchRecipientSelect) {
            branchRecipientSelect.addEventListener("change", () => {
                document.dispatchEvent(
                    new CustomEvent("branch:changed", {
                        detail: {
                            branchId: branchRecipientSelect.value,
                            source: "recipient",
                        },
                    })
                );
            });
        }

        // Cliente / Edición
        else if (branchSelect) {
            branchSelect.addEventListener("change", () => {
                document.dispatchEvent(
                    new CustomEvent("branch:changed", {
                        detail: {
                            branchId: branchSelect.value,
                            source: "origin",
                        },
                    })
                );
            });

            // Emisión inicial (equivalente a tu reload inicial)
            if (branchSelect.value) {
                setTimeout(() => {
                    document.dispatchEvent(
                        new CustomEvent("branch:changed", {
                            detail: {
                                branchId: branchSelect.value,
                                source: "initial",
                            },
                        })
                    );
                }, 500);
            }
        } else {
            console.warn("No branch controls found on this page");
        }
    },
};
