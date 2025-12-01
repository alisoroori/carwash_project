class CategoryManager {
    constructor() {
        this.initSortable();
        this.loadCategories();
    }

    initSortable() {
        const el = document.getElementById('categoryList');
        Sortable.create(el, {
            animation: 150,
            onEnd: async (evt) => {
                const items = [...evt.to.children];
                const orders = items.map((item, index) => ({
                    id: item.dataset.id,
                    order: index
                }));
                
                await this.updateOrder(orders);
            }
        });
    }

    async updateOrder(orders) {
        try {
            const response = await fetch('../../api/carwash/update_category_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ orders })
            });
            
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error updating order:', error);
            if (window.showToast) showToast('Sıralama güncellenirken hata oluştu', 'error'); else alert('Sıralama güncellenirken hata oluştu');
        }
    }
}

// Initialize
new CategoryManager();