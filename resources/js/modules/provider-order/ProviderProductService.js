export class ProviderProductService {
    static async fetchProducts(providerId) {
        const response = await fetch(`/api/providers/${providerId}/products`);
        if (!response.ok) {
            throw new Error("Error fetching provider products");
        }
        return await response.json();
    }

    static formatForChoices(products) {
        return products.map((p) => ({
            value: p.id,
            label: p.name,
            customProperties: {
                price: p.price,
                currency: p.currency,
            },
        }));
    }
}
