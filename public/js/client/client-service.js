class ClientService {
    async getAll() {
        const res = await axios.get("/api/clients");
        return res.data;
    }

    async delete(id) {
        return axios.delete(`/api/clients/${id}`);
    }
}
