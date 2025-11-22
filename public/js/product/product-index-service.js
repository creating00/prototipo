class ProductService {
    constructor(baseUrl = '/api/products') {
        this.baseUrl = baseUrl;
    }

    async getAll() {
        const res = await axios.get(this.baseUrl);
        return Array.isArray(res.data) ? res.data : [];
    }

    async delete(id) {
        return axios.delete(`${this.baseUrl}/${id}`);
    }
}
