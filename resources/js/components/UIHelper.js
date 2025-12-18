export class UIHelper {
    static disableButton(btn) {
        btn.disabled = true;
        btn.querySelector(".spinner-border")?.classList.remove("d-none");
        const text = btn.querySelector(".btn-text");
        if (text) text.textContent = "Guardando...";
    }

    static enableButton(btn) {
        btn.disabled = false;
        btn.querySelector(".spinner-border")?.classList.add("d-none");
        const text = btn.querySelector(".btn-text");
        if (text) text.textContent = "Guardar";
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
}
