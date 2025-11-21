class ApiClient {
    static async get(url) {
        const response = await axios.get(url);
        return response.data;
    }

    static async post(url, data, config = {}) {
        if (data instanceof FormData) {
            const response = await axios.post(url, data, {
                ...config,
                headers: {
                    ...config.headers,
                },
            });
            return response.data;
        } else {
            const response = await axios.post(url, data, config);
            return response.data;
        }
    }

    static async put(url, data, config = {}) {
        if (data instanceof FormData) {
            data.append("_method", "PUT");
            const response = await axios.post(url, data, {
                ...config,
                headers: {
                    ...config.headers,
                },
            });
            return response.data;
        } else {
            data = { ...data, _method: "PUT" };
            const response = await axios.post(url, data, config);
            return response.data;
        }
    }

    static async delete(url) {
        const response = await axios.delete(url);
        return response.data;
    }
}
