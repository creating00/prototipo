export class FormHelper {
    static submitDeleteForm(url, data = {}) {
        if (confirm(`¿Estás seguro de eliminar este registro?`)) {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = url;

            // CSRF Token
            const csrfToken = document.createElement("input");
            csrfToken.type = "hidden";
            csrfToken.name = "_token";
            csrfToken.value = this.getCsrfToken();

            // Method spoofing
            const methodField = document.createElement("input");
            methodField.type = "hidden";
            methodField.name = "_method";
            methodField.value = "DELETE";

            // Datos adicionales
            Object.keys(data).forEach((key) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = key;
                input.value = data[key];
                form.appendChild(input);
            });

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    }

    static getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }
}
