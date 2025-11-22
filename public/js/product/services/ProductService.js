class ProductService {
    static async create(productData) {
        return await ApiClient.post("/api/products", productData);
    }

    static async update(id, productData) {
        return await ApiClient.put(`/api/products/${id}`, productData);
    }

    static async getById(id) {
        return await ApiClient.get(`/api/products/${id}`);
    }

    static async delete(id) {
        return await ApiClient.delete(`/api/products/${id}`);
    }
}
