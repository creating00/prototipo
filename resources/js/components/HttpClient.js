export class HttpClient {
    static async post(url, formData, csrf) {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            const message = data.errors
                ? Object.values(data.errors).flat().join(", ")
                : data.message || "Error desconocido";
            throw new Error(message);
        }

        return data;
    }
}
