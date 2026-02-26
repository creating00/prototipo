export class UIHelper {
    static disableButton(btn, customText = "Procesando...") {
        btn.disabled = true;

        const spinner = btn.querySelector(".spinner-border");
        const text = btn.querySelector(".btn-text");

        if (text && spinner) {
            // Caso A: El botón tiene estructura fija (span + spinner)
            spinner.classList.remove("d-none");
            text.textContent = customText;
        } else {
            // Caso B: El botón es simple, guardamos todo el HTML
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${customText}`;
        }
    }

    static enableButton(btn) {
        btn.disabled = false;

        const spinner = btn.querySelector(".spinner-border");
        const text = btn.querySelector(".btn-text");

        if (text && spinner) {
        
            spinner.classList.add("d-none");
        } else if (btn.dataset.originalHtml) {
            // Caso B: Restauramos el HTML completo que guardamos en disableButton
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
    }

    static success(msg) {
        Swal?.fire({
            icon: "success",
            title: msg,
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
        });
    }

    static error(msg) {
        Swal?.fire({
            icon: "error",
            title: msg,
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 5000,
        });
    }

    /**
     * Descarga archivos vía AJAX con manejo de UI
     */
    static async handleDownload(btn, event, defaultFilename = 'plantilla.xlsx') {
        event.preventDefault();
        
        const url = btn.href;
        const type = btn.dataset.type; 
        const filename = type ? `plantilla_${type}.xlsx` : defaultFilename;

        this.disableButton(btn, "Preparando...");

        try {
            const response = await axios({
                url: url,
                method: "GET",
                responseType: "blob",
            });

            const blob = new Blob([response.data]);
            const urlBlob = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            
            link.href = urlBlob;
            link.download = filename;
            document.body.appendChild(link); // Mejor compatibilidad
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(urlBlob); // Liberar memoria

            this.success("Descarga iniciada");
        } catch (error) {
            console.error("Error en descarga:", error);
            this.error("El archivo no está disponible en el servidor.");
        } finally {
            this.enableButton(btn);
        }
    }
}
