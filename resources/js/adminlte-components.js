class AdminLteComponents {
    static init() {
        this.initAutoCloseAlerts();
    }

    static initAutoCloseAlerts() {
        const alertElements = document.querySelectorAll(
            ".alert[data-auto-close]"
        );

        alertElements.forEach((alertElement) => {
            const delay = alertElement.getAttribute("data-auto-close");

            setTimeout(() => {
                if (typeof bootstrap !== "undefined" && bootstrap.Alert) {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                } else {
                    this.closeAlert(alertElement);
                }
            }, parseInt(delay));
        });
    }

    static closeAlert(alertElement) {
        if (alertElement) {
            alertElement.style.transition = "opacity 0.15s linear";
            alertElement.style.opacity = "0";

            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.parentNode.removeChild(alertElement);
                }
            }, 150);
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    AdminLteComponents.init();
});

window.AdminLteComponents = AdminLteComponents;
