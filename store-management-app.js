const { createApp } = Vue;

createApp({
    data() {
        return {
            // App state
            isLoading: true,
            hasError: false,
            errorMessage: '',
            activeView: 'products',
            
            // User data
            currentUser: '',
            userEmail: '',
            userInitials: '',
            
            // API configuration
            apiUrl: '',
            nonce: '',
            
            // Notifications
            notifications: [],
            globalNotification: null,
            
            // Products data
            products: [],
            productsLoading: false,
            productsError: null,
            productsPagination: {
                currentPage: 1,
                totalPages: 1,
                totalItems: 0,
                itemsPerPage: 10
            },
            productsFilters: {
                search: '',
                orderby: 'date',
                order: 'desc'
            },
            selectedProducts: [],
            showProductModal: false,
            editingProduct: null,
            productForm: {
                name: '',
                description: '',
                short_description: '',
                sku: '',
                regular_price: '',
                sale_price: '',
                status: 'publish',
                stock_quantity: '',
                manage_stock: false,
                stock_status: 'instock',
                categories: [],
                tags: [],
                image_id: null,
                image_preview_url: ''
            },
            
            // Orders data
            orders: [],
            ordersLoading: false,
            ordersError: null,
            ordersPagination: {
                currentPage: 1,
                totalPages: 1,
                totalItems: 0,
                itemsPerPage: 10
            },
            ordersFilters: {
                search: '',
                status: [],
                orderby: 'date',
                order: 'desc'
            },
            showOrderModal: false,
            selectedOrder: null,
            orderStatusOptions: [
                { value: 'pending', label: 'Pending Payment' },
                { value: 'processing', label: 'Processing' },
                { value: 'on-hold', label: 'On Hold' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
                { value: 'refunded', label: 'Refunded' },
                { value: 'failed', label: 'Failed' }
            ],
            newOrderNote: '',
            isCustomerNote: false,
            
            // Reports data
            reportsLoading: false,
            reportsError: null,
            selectedPeriod: '7days',
            periodOptions: [
                { value: '7days', label: 'Last 7 Days' },
                { value: '30days', label: 'Last 30 Days' },
                { value: 'current_month', label: 'Current Month' },
                { value: 'last_month', label: 'Last Month' }
            ],
            salesData: {
                total_sales: 0,
                order_count: 0,
                period: { start: '', end: '' },
                daily_sales_data: []
            },
            bestsellersData: [],
            lowStockData: [],
            lowStockPagination: {
                currentPage: 1,
                totalPages: 1,
                totalItems: 0,
                perPage: 10
            }
        };
    },
    
    computed: {
        userInitials() {
            if (!this.currentUser) return 'SM';
            const names = this.currentUser.split(' ');
            return names.map(name => name.charAt(0)).join('').substring(0, 2).toUpperCase();
        }
    },
    
    async mounted() {
        await this.initializeApp();
    },
    
    methods: {
        async initializeApp() {
            try {
                // Get data from localized script
                if (typeof esmData !== 'undefined') {
                    this.apiUrl = esmData.apiUrl;
                    this.nonce = esmData.nonce;
                    this.currentUser = esmData.currentUser;
                    this.userEmail = esmData.userEmail;
                } else {
                    throw new Error('ESM data not found');
                }
                
                // Load initial data based on active view
                await this.loadViewData();
                
                this.isLoading = false;
            } catch (error) {
                console.error('App initialization error:', error);
                this.hasError = true;
                this.errorMessage = 'Failed to initialize the dashboard. Please refresh the page.';
                this.isLoading = false;
            }
        },
        
        async retryInitialization() {
            this.hasError = false;
            this.isLoading = true;
            await this.initializeApp();
        },
        
        setActiveView(view) {
            this.activeView = view;
            this.loadViewData();
        },
        
        getViewDescription() {
            const descriptions = {
                products: 'Manage your store products, inventory, and pricing',
                orders: 'View and manage customer orders and fulfillment',
                reports: 'Analyze sales performance and inventory insights'
            };
            return descriptions[this.activeView] || '';
        },
        
        async loadViewData() {
            switch (this.activeView) {
                case 'products':
                    await this.loadProducts();
                    break;
                case 'orders':
                    await this.loadOrders();
                    break;
                case 'reports':
                    await this.loadReports();
                    break;
            }
        },
        
        async refreshCurrentView() {
            await this.loadViewData();
            this.showNotification('Data refreshed successfully', 'success');
        },
        
        // API Helper Methods
        async apiRequest(endpoint, options = {}) {
            const url = this.apiUrl + endpoint;
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                }
            };
            
            const response = await fetch(url, { ...defaultOptions, ...options });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        },
        
        // Product Management Methods
        async loadProducts() {
            this.productsLoading = true;
            this.productsError = null;
            
            try {
                const params = new URLSearchParams({
                    page: this.productsPagination.currentPage,
                    per_page: this.productsPagination.itemsPerPage,
                    orderby: this.productsFilters.orderby,
                    order: this.productsFilters.order
                });
                
                if (this.productsFilters.search) {
                    params.append('search', this.productsFilters.search);
                }
                
                const response = await fetch(`${this.apiUrl}products?${params}`, {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                
                if (!response.ok) throw new Error('Failed to load products');
                
                this.products = await response.json();
                this.productsPagination.totalItems = parseInt(response.headers.get('X-WP-Total'));
                this.productsPagination.totalPages = parseInt(response.headers.get('X-WP-TotalPages'));
                
            } catch (error) {
                console.error('Products loading error:', error);
                this.productsError = error.message;
            } finally {
                this.productsLoading = false;
            }
        },
        
        async searchProducts() {
            this.productsPagination.currentPage = 1;
            await this.loadProducts();
        },
        
        async sortProducts(column) {
            if (this.productsFilters.orderby === column) {
                this.productsFilters.order = this.productsFilters.order === 'asc' ? 'desc' : 'asc';
            } else {
                this.productsFilters.orderby = column;
                this.productsFilters.order = 'asc';
            }
            await this.loadProducts();
        },
        
        async changeProductsPage(page) {
            this.productsPagination.currentPage = page;
            await this.loadProducts();
        },
        
        openProductModal(product = null) {
            this.editingProduct = product;
            if (product) {
                // Edit mode
                this.productForm = { ...product };
            } else {
                // Add mode
                this.resetProductForm();
            }
            this.showProductModal = true;
        },
        
        closeProductModal() {
            this.showProductModal = false;
            this.editingProduct = null;
            this.resetProductForm();
        },
        
        resetProductForm() {
            this.productForm = {
                name: '',
                description: '',
                short_description: '',
                sku: '',
                regular_price: '',
                sale_price: '',
                status: 'publish',
                stock_quantity: '',
                manage_stock: false,
                stock_status: 'instock',
                categories: [],
                tags: [],
                image_id: null,
                image_preview_url: ''
            };
        },
        
        async saveProduct() {
            try {
                const isEdit = !!this.editingProduct;
                const endpoint = isEdit ? `products/${this.editingProduct.id}` : 'products';
                const method = isEdit ? 'PUT' : 'POST';
                
                const response = await this.apiRequest(endpoint, {
                    method,
                    body: JSON.stringify(this.productForm)
                });
                
                this.showNotification(
                    isEdit ? 'Product updated successfully' : 'Product created successfully',
                    'success'
                );
                
                this.closeProductModal();
                await this.loadProducts();
                
            } catch (error) {
                console.error('Product save error:', error);
                this.showNotification('Failed to save product: ' + error.message, 'error');
            }
        },
        
        async deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            
            try {
                await this.apiRequest(`products/${productId}`, { method: 'DELETE' });
                this.showNotification('Product deleted successfully', 'success');
                await this.loadProducts();
            } catch (error) {
                console.error('Product delete error:', error);
                this.showNotification('Failed to delete product: ' + error.message, 'error');
            }
        },
        
        openMediaLibrary() {
            if (typeof wp !== 'undefined' && wp.media) {
                const mediaUploader = wp.media({
                    title: 'Select Product Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                
                mediaUploader.on('select', () => {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    this.productForm.image_id = attachment.id;
                    this.productForm.image_preview_url = attachment.url;
                });
                
                mediaUploader.open();
            } else {
                alert('Media library not available');
            }
        },
        
        removeProductImage() {
            this.productForm.image_id = null;
            this.productForm.image_preview_url = '';
        },
        
        // Order Management Methods
        async loadOrders() {
            this.ordersLoading = true;
            this.ordersError = null;
            
            try {
                const params = new URLSearchParams({
                    page: this.ordersPagination.currentPage,
                    per_page: this.ordersPagination.itemsPerPage,
                    orderby: this.ordersFilters.orderby,
                    order: this.ordersFilters.order
                });
                
                if (this.ordersFilters.search) {
                    params.append('search', this.ordersFilters.search);
                }
                
                if (this.ordersFilters.status.length > 0) {
                    this.ordersFilters.status.forEach(status => {
                        params.append('status[]', status);
                    });
                }
                
                const response = await fetch(`${this.apiUrl}orders?${params}`, {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                
                if (!response.ok) throw new Error('Failed to load orders');
                
                this.orders = await response.json();
                this.ordersPagination.totalItems = parseInt(response.headers.get('X-WP-Total'));
                this.ordersPagination.totalPages = parseInt(response.headers.get('X-WP-TotalPages'));
                
            } catch (error) {
                console.error('Orders loading error:', error);
                this.ordersError = error.message;
            } finally {
                this.ordersLoading = false;
            }
        },
        
        async searchOrders() {
            this.ordersPagination.currentPage = 1;
            await this.loadOrders();
        },
        
        async changeOrdersPage(page) {
            this.ordersPagination.currentPage = page;
            await this.loadOrders();
        },
        
        async openOrderModal(orderId) {
            try {
                this.selectedOrder = await this.apiRequest(`orders/${orderId}`);
                this.showOrderModal = true;
            } catch (error) {
                console.error('Order loading error:', error);
                this.showNotification('Failed to load order details: ' + error.message, 'error');
            }
        },
        
        closeOrderModal() {
            this.showOrderModal = false;
            this.selectedOrder = null;
            this.newOrderNote = '';
            this.isCustomerNote = false;
        },
        
        async updateOrderStatus(newStatus) {
            if (!this.selectedOrder) return;
            
            try {
                const response = await this.apiRequest(`orders/${this.selectedOrder.id}/status`, {
                    method: 'PUT',
                    body: JSON.stringify({ status: newStatus })
                });
                
                this.selectedOrder = response;
                this.showNotification('Order status updated successfully', 'success');
                await this.loadOrders();
                
            } catch (error) {
                console.error('Order status update error:', error);
                this.showNotification('Failed to update order status: ' + error.message, 'error');
            }
        },
        
        async addOrderNote() {
            if (!this.newOrderNote.trim()) {
                alert('Please enter a note');
                return;
            }
            
            try {
                const response = await this.apiRequest(`orders/${this.selectedOrder.id}/notes`, {
                    method: 'POST',
                    body: JSON.stringify({
                        note: this.newOrderNote,
                        is_customer_note: this.isCustomerNote
                    })
                });
                
                this.selectedOrder.notes.unshift(response);
                this.newOrderNote = '';
                this.isCustomerNote = false;
                this.showNotification('Order note added successfully', 'success');
                
            } catch (error) {
                console.error('Add order note error:', error);
                this.showNotification('Failed to add order note: ' + error.message, 'error');
            }
        },
        
        getOrderStatusClass(status) {
            const statusClasses = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'processing': 'bg-blue-100 text-blue-800',
                'on-hold': 'bg-orange-100 text-orange-800',
                'completed': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800',
                'refunded': 'bg-purple-100 text-purple-800',
                'failed': 'bg-red-100 text-red-800'
            };
            return statusClasses[status] || 'bg-gray-100 text-gray-800';
        },
        
        // Reports Methods
        async loadReports() {
            this.reportsLoading = true;
            this.reportsError = null;
            
            try {
                await Promise.all([
                    this.loadSalesReport(),
                    this.loadBestsellersReport(),
                    this.loadLowStockReport()
                ]);
            } catch (error) {
                console.error('Reports loading error:', error);
                this.reportsError = error.message;
            } finally {
                this.reportsLoading = false;
            }
        },
        
        async loadSalesReport() {
            try {
                this.salesData = await this.apiRequest(`reports/sales?period=${this.selectedPeriod}`);
            } catch (error) {
                console.error('Sales report error:', error);
                throw error;
            }
        },
        
        async loadBestsellersReport() {
            try {
                this.bestsellersData = await this.apiRequest(`reports/bestsellers?period=${this.selectedPeriod}&limit=5`);
            } catch (error) {
                console.error('Bestsellers report error:', error);
                throw error;
            }
        },
        
        async loadLowStockReport() {
            try {
                const response = await fetch(`${this.apiUrl}reports/low-stock?page=${this.lowStockPagination.currentPage}&per_page=${this.lowStockPagination.perPage}`, {
                    headers: { 'X-WP-Nonce': this.nonce }
                });
                
                if (!response.ok) throw new Error('Failed to load low stock report');
                
                this.lowStockData = await response.json();
                this.lowStockPagination.totalItems = parseInt(response.headers.get('X-WP-Total'));
                this.lowStockPagination.totalPages = parseInt(response.headers.get('X-WP-TotalPages'));
                
            } catch (error) {
                console.error('Low stock report error:', error);
                throw error;
            }
        },
        
        async changePeriod() {
            await Promise.all([
                this.loadSalesReport(),
                this.loadBestsellersReport()
            ]);
        },
        
        async changeLowStockPage(page) {
            this.lowStockPagination.currentPage = page;
            await this.loadLowStockReport();
        },
        
        // Utility Methods
        showNotification(message, type = 'info') {
            this.globalNotification = { message, type };
            setTimeout(() => {
                this.globalNotification = null;
            }, 5000);
        },
        
        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        },
        
        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString();
        }
    }
}).mount('#store-management-app');