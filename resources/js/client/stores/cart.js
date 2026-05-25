import { defineStore } from 'pinia';
import api from '@/services/api';

const normalizeCartPayload = (payload = {}) => ({
    items: Array.isArray(payload.items) ? payload.items : [],
    subtotal: Number(payload.subtotal || 0),
    totalItems: Number(payload.total_items || 0),
});

const calculateCartTotals = (items) => {
    return items.reduce(
        (totals, item) => ({
            subtotal: totals.subtotal + Number(item.line_total || 0),
            totalItems: totals.totalItems + Number(item.quantity || 0),
        }),
        { subtotal: 0, totalItems: 0 },
    );
};

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        subtotal: 0,
        totalItems: 0,
        isLoading: false,
        errorMessage: '',
    }),

    getters: {
        isEmpty: (state) => state.items.length === 0,
    },

    actions: {
        async fetchCart() {
            this.isLoading = true;
            this.errorMessage = '';

            try {
                const response = await api.get('/cart');
                const cart = normalizeCartPayload(response.data);

                this.items = cart.items;
                this.subtotal = cart.subtotal;
                this.totalItems = cart.totalItems;

                return cart;
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Khong the tai gio hang.';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async addItem(productVariantId, quantity = 1) {
            const response = await api.post('/cart/items', {
                product_variant_id: productVariantId,
                quantity,
            });

            await this.fetchCart();
            return response.data;
        },

        async updateItem(productVariantId, quantity) {
            const previousItems = this.items.map((item) => ({ ...item }));
            const previousSubtotal = this.subtotal;
            const previousTotalItems = this.totalItems;
            const normalizedQuantity = Number(quantity || 1);

            this.items = this.items.map((item) => {
                if (item.product_variant_id !== productVariantId) {
                    return item;
                }

                return {
                    ...item,
                    quantity: normalizedQuantity,
                    line_total: Number(item.price || 0) * normalizedQuantity,
                };
            });

            const totals = calculateCartTotals(this.items);
            this.subtotal = totals.subtotal;
            this.totalItems = totals.totalItems;

            try {
                const response = await api.put(`/cart/items/${productVariantId}`, {
                    quantity: normalizedQuantity,
                });

                await this.fetchCart();
                return response.data;
            } catch (error) {
                this.items = previousItems;
                this.subtotal = previousSubtotal;
                this.totalItems = previousTotalItems;
                throw error;
            }
        },

        async removeItem(productVariantId) {
            const response = await api.delete(`/cart/items/${productVariantId}`);

            await this.fetchCart();
            return response.data;
        },
    },
});
