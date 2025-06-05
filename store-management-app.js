// Assume Vue.js and Axios are loaded globally (e.g., via wp_enqueue_script)
// For a real build, these would be imported.

// WordPress API Settings (to be localized)
const wpApiSettings = {
    root: '/wp-json/', // This would be localized (e.g., from rest_url())
    nonce: null, // This would be localized (e.g., from wp_create_nonce('wp_rest'))
    // For this example, we'll assume cookie-based authentication for WP REST API is fine
    // or a token is handled by Axios interceptors if needed.
    esm_namespace: 'esm/v1'
};

const api = axios.create({
    baseURL: `${wpApiSettings.root}${wpApiSettings.esm_namespace}/`,
    // headers: {
    //     'X-WP-Nonce': wpApiSettings.nonce // Example if using nonce for auth
    // }
});

// --- Utility Components ---

const ModalComponent = {
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
        title: {
            type: String,
            default: 'Modal Title'
        }
    },
    template: `
        <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-gray-600 bg-opacity-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl transform transition-all">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-800">{{ title }}</h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <slot></slot> <!-- Content of the modal -->
                    </div>
                    <div class="mt-6 modal-footer">
                        <slot name="footer"></slot> <!-- Optional footer content -->
                    </div>
                </div>
            </div>
        </div>
    `,
    emits: ['close']
};

const PaginationComponent = {
    props: {
        currentPage: {
            type: Number,
            required: true
        },
        totalPages: {
            type: Number,
            required: true
        },
        maxVisibleButtons: {
            type: Number,
            default: 5
        }
    },
    computed: {
        startPage() {
            if (this.currentPage === 1) return 1;
            if (this.totalPages <= this.maxVisibleButtons) return 1;
            if (this.currentPage > this.totalPages - Math.floor(this.maxVisibleButtons / 2) ) {
                 return Math.max(1, this.totalPages - this.maxVisibleButtons + 1);
            }
            return Math.max(1, this.currentPage - Math.floor(this.maxVisibleButtons / 2));
        },
        endPage() {
            return Math.min(this.startPage + this.maxVisibleButtons - 1, this.totalPages);
        },
        pages() {
            const range = [];
            for (let i = this.startPage; i <= this.endPage; i++) {
                range.push({
                    name: i,
                    isDisabled: i === this.currentPage
                });
            }
            return range;
        },
        isInFirstPage() {
            return this.currentPage === 1;
        },
        isInLastPage() {
            return this.currentPage === this.totalPages;
        }
    },
    methods: {
        onClickFirstPage() {
            this.$emit('pagechanged', 1);
        },
        onClickPreviousPage() {
            this.$emit('pagechanged', this.currentPage - 1);
        },
        onClickPage(page) {
            this.$emit('pagechanged', page);
        },
        onClickNextPage() {
            this.$emit('pagechanged', this.currentPage + 1);
        },
        onClickLastPage() {
            this.$emit('pagechanged', this.totalPages);
        },
        isPageActive(page) {
            return this.currentPage === page;
        }
    },
    template: `
        <nav v-if="totalPages > 1" aria-label="Pagination" class="flex items-center justify-between py-3">
            <div class="flex-1 flex justify-between sm:hidden">
                <button @click="onClickPreviousPage" :disabled="isInFirstPage" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                    Previous
                </button>
                <button @click="onClickNextPage" :disabled="isInLastPage" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                <div>
                    <ul class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <li>
                            <button @click="onClickFirstPage" :disabled="isInFirstPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                <span class="sr-only">First</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 010 1.414zm-6 0a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 1.414L5.414 10l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </li>
                        <li>
                            <button @click="onClickPreviousPage" :disabled="isInFirstPage" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </button>
                        </li>
                        <li v-for="page in pages" :key="page.name">
                            <button @click="onClickPage(page.name)" :disabled="page.isDisabled"
                                :class="['relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                        isPageActive(page.name) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        {'disabled:opacity-50': page.isDisabled}]">
                                {{ page.name }}
                            </button>
                        </li>
                        <li>
                            <button @click="onClickNextPage" :disabled="isInLastPage" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </button>
                        </li>
                        <li>
                            <button @click="onClickLastPage" :disabled="isInLastPage" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                <span class="sr-only">Last</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414zm6 0a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L14.586 10l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    `,
    emits: ['pagechanged']
};

// --- ProductForm Component ---
const ProductFormComponent = {
    props: {
        productData: { // Pass null for new product, object for editing
            type: Object,
            default: null
        },
        isEditMode: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            form: {
                name: '',
                type: 'simple', // Default product type
                status: 'publish', // Default status
                description: '',
                short_description: '',
                sku: '',
                regular_price: '',
                sale_price: '',
                manage_stock: false,
                stock_quantity: null,
                stock_status: 'instock',
                categories: [], // Array of term IDs or objects {id, name}
                tags: [],       // Array of term IDs or objects {id, name}
                image_id: null, // Featured image ID
                // Add more fields as necessary based on your product structure
            },
            availableStatuses: [ // Example statuses
                { value: 'publish', text: 'Published' },
                { value: 'draft', text: 'Draft' },
                { value: 'pending', text: 'Pending Review' }
            ],
            availableStockStatuses: [
                { value: 'instock', text: 'In Stock' },
                { value: 'outofstock', text: 'Out of Stock' },
                { value: 'onbackorder', text: 'On Backorder' },
            ],
            // In a real app, categories and tags would be fetched or passed as props
            availableCategories: [ {id: 1, name: 'Sample Category 1'}, {id: 2, name: 'Sample Category 2'}],
            availableTags: [ {id: 3, name: 'Sample Tag 1'}, {id: 4, name: 'Sample Tag 2'}],
            isLoading: false,
            errorMessage: '',
        };
    },
    watch: {
        productData: {
            handler(newData) {
                this.loadProductData(newData);
            },
            immediate: true, // Load data when component is created
            deep: true
        },
        isEditMode(newVal) {
            if (!newVal) {
                this.resetForm();
            }
        }
    },
    methods: {
        resetForm() {
            this.form = {
                name: '', type: 'simple', status: 'publish', description: '', short_description: '',
                sku: '', regular_price: '', sale_price: '', manage_stock: false, stock_quantity: null,
                stock_status: 'instock', categories: [], tags: [], image_id: null, image_preview_url: '',
            };
            this.errorMessage = '';
            this.mediaFrame = null; // To hold the media frame instance
        },
        loadProductData(data) {
            if (data && this.isEditMode) {
                this.form.id = data.id; // Keep track of ID for updates
                this.form.name = data.name || '';
                this.form.type = data.type || 'simple';
                this.form.status = data.status || 'publish';
                this.form.description = data.description || '';
                this.form.short_description = data.short_description || '';
                this.form.sku = data.sku || '';
                this.form.regular_price = data.regular_price || '';
                this.form.sale_price = data.sale_price || '';
                this.form.manage_stock = data.manage_stock || false;
                this.form.stock_quantity = data.stock_quantity === null ? null : Number(data.stock_quantity);
                this.form.stock_status = data.stock_status || 'instock';
                this.form.categories = data.categories ? data.categories.map(cat => typeof cat === 'object' ? cat.id : cat) : [];
                this.form.tags = data.tags ? data.tags.map(tag => typeof tag === 'object' ? tag.id : tag) : [];

                // Featured Image - assume productData might have featured_image_url and image_id (or featured_media_id)
                // The `esm_get_product_data` function in PHP should be updated to include these.
                this.form.image_id = data.image_id || data.featured_media_id || null;
                this.form.image_preview_url = data.featured_image_url || '';
                 // If only image_id is present, and no URL, we might need to fetch it.
                 // For this example, we rely on `featured_image_url` being present in productData.
                 // If not, an additional fetch to /wp/v2/media/{id} would be needed here.

            } else {
                this.resetForm();
            }
        },
        async handleSubmit() {
            this.isLoading = true;
            this.errorMessage = '';
            let response;

            const payload = { ...this.form };
            // Remove image_preview_url as it's not needed for backend, backend uses image_id
            delete payload.image_preview_url;
            if (payload.sale_price === '') delete payload.sale_price;
            if (payload.stock_quantity === null) delete payload.stock_quantity;

            try {
                if (this.isEditMode && payload.id) {
                    response = await api.put(`/products/${payload.id}`, payload);
                } else {
                    response = await api.post('/products', payload);
                }
                this.$emit('save-success', response.data);
                this.$emit('close');
            } catch (error) {
                console.error("Error saving product:", error);
                this.errorMessage = error.response?.data?.message || error.message || 'An unknown error occurred.';
                if (error.response?.data?.data?.details) {
                    this.errorMessage += ": " + JSON.stringify(error.response.data.data.details);
                }
            } finally {
                this.isLoading = false;
            }
        },
        openMediaLibrary() {
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                alert('WordPress media library is not available. Ensure wp_enqueue_media() is called.');
                return;
            }

            // If the frame already exists, open it
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }

            // Create a new media frame
            this.mediaFrame = wp.media({
                title: 'Select or Upload Product Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false, // Only one image for featured image
                library: {
                    type: 'image'
                }
            });

            // When an image is selected, run a callback
            this.mediaFrame.on('select', () => {
                const attachment = this.mediaFrame.state().get('selection').first().toJSON();
                this.form.image_id = attachment.id;
                // Prefer 'medium' or 'thumbnail' size for preview if available
                this.form.image_preview_url = attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;
            });

            // Open the frame
            this.mediaFrame.open();
        },
        removeFeaturedImage() {
            this.form.image_id = null;
            this.form.image_preview_url = '';
        },
        updateMultiSelect(modelKey, event) {
            this.form[modelKey] = Array.from(event.target.selectedOptions, option => option.value);
        }
    },
    emits: ['close', 'save-success'],
    template: `
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <div v-if="errorMessage" class="p-3 bg-red-100 text-red-700 border border-red-300 rounded-md">
                <p class="font-semibold">Error:</p>
                <p>{{ errorMessage }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="productName" class="block text-sm font-medium text-gray-700">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" v-model.trim="form.name" id="productName" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="productSku" class="block text-sm font-medium text-gray-700">SKU</label>
                    <input type="text" v-model.trim="form.sku" id="productSku" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label for="productDescription" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea v-model="form.description" id="productDescription" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
            </div>
            <div>
                <label for="productShortDescription" class="block text-sm font-medium text-gray-700">Short Description</label>
                <textarea v-model="form.short_description" id="productShortDescription" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="productRegularPrice" class="block text-sm font-medium text-gray-700">Regular Price</label>
                    <input type="text" v-model="form.regular_price" id="productRegularPrice" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., 9.99">
                </div>
                <div>
                    <label for="productSalePrice" class="block text-sm font-medium text-gray-700">Sale Price</label>
                    <input type="text" v-model="form.sale_price" id="productSalePrice" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., 7.99 (optional)">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div>
                    <label for="productStatus" class="block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="form.status" id="productStatus" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option v-for="status in availableStatuses" :key="status.value" :value="status.value">{{ status.text }}</option>
                    </select>
                </div>
                <div>
                    <label for="productType" class="block text-sm font-medium text-gray-700">Product Type</label>
                    <select v-model="form.type" id="productType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" disabled>
                        <option value="simple">Simple</option>
                        <option value="variable" disabled>Variable (Not implemented)</option>
                    </select>
                </div>
            </div>

            <fieldset class="mt-6">
                <legend class="text-base font-medium text-gray-900">Inventory</legend>
                <div class="mt-4 space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="manageStock" v-model="form.manage_stock" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="manageStock" class="font-medium text-gray-700">Manage stock?</label>
                            <p class="text-gray-500">Enable stock management for this product.</p>
                        </div>
                    </div>
                    <div v-if="form.manage_stock" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="stockQuantity" class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                            <input type="number" v-model.number="form.stock_quantity" id="stockQuantity" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="stockStatus" class="block text-sm font-medium text-gray-700">Stock Status</label>
                             <select v-model="form.stock_status" id="stockStatus" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option v-for="sStatus in availableStockStatuses" :key="sStatus.value" :value="sStatus.value">{{ sStatus.text }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Categories and Tags (Simplified - using multi-selects, real app might use better UI) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="productCategories" class="block text-sm font-medium text-gray-700">Categories</label>
                    <select multiple v-model="form.categories" id="productCategories" class="mt-1 block w-full h-32 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple.</p>
                </div>
                <div>
                    <label for="productTags" class="block text-sm font-medium text-gray-700">Tags</label>
                    <select multiple v-model="form.tags" id="productTags" class="mt-1 block w-full h-32 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option v-for="tag in availableTags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
                    </select>
                     <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple.</p>
                </div>
            </div>

            <!-- Featured Image Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Featured Image</label>
                <div class="mt-2 flex items-center space-x-4">
                    <div class="w-24 h-24 rounded-md border border-gray-300 flex items-center justify-center overflow-hidden">
                        <img v-if="form.image_preview_url" :src="form.image_preview_url" alt="Featured image preview" class="w-full h-full object-cover">
                        <svg v-else class="h-12 w-12 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.39 6.37A रेडियो.998 2.998 0 0016.5 3c-1.42 0-2.68.83-3.24 2.03C12.7 5.01 12.12 5 11.5 5c-2.07 0-3.8.89-4.68 2.22C6.29 7.09 5.77 7 5.25 7c-1.79 0-3.25 1.46-3.25 3.25S3.46 13.5 5.25 13.5h13.5c1.79 0 3.25-1.46 3.25-3.25 0-1.67-1.24-3.04-2.86-3.22zM5.25 11.5c-.69 0-1.25-.56-1.25-1.25S4.56 9 5.25 9s1.25.56 1.25 1.25S5.94 11.5 5.25 11.5zm13.5 0c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col space-y-2">
                        <button @click.prevent="openMediaLibrary" type="button" class="bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ form.image_id ? 'Change Image' : 'Set Image' }}
                        </button>
                        <button v-if="form.image_id" @click.prevent="removeFeaturedImage" type="button" class="text-red-600 hover:text-red-800 text-sm">
                            Remove Image
                        </button>
                    </div>
                </div>
                <p v-if="form.image_id" class="text-xs text-gray-500 mt-1">Image ID: {{ form.image_id }}</p>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-8">
                <button type="button" @click="$emit('close')" :disabled="isLoading" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                    Cancel
                </button>
                <button type="submit" :disabled="isLoading" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                    <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isLoading ? 'Saving...' : (isEditMode ? 'Save Changes' : 'Create Product') }}
                </button>
            </div>
        </form>
    `
};

// --- ProductList Component ---
const ProductListComponent = {
    components: {
        'pagination': PaginationComponent,
        'modal': ModalComponent,
        'product-form': ProductFormComponent,
    },
    data() {
        return {
            products: [],
            isLoading: false,
            errorMessage: '',
            // Pagination
            currentPage: 1,
            totalPages: 1,
            itemsPerPage: 10, // Default items per page
            // Search and Sort
            searchQuery: '',
            sortBy: 'date', // Default sort column
            sortOrder: 'desc', // Default sort order
            // Modal control
            isProductModalOpen: false,
            currentProduct: null, // For editing
            isEditMode: false,
            // Bulk actions
            selectedProducts: [],
            selectAll: false,
            bulkAction: '', // e.g., 'delete', 'change_price_percent'
            bulkValue: '', // Value for the bulk action
            isBulkEditModalOpen: false, // For a dedicated bulk edit UI
            // Inline Editing (simplified)
            editingCell: null, // e.g., { productId: 1, field: 'price' }
            editingValue: '',
        };
    },
    computed: {
        sortableColumns() {
            return [
                { key: 'name', label: 'Name' },
                { key: 'sku', label: 'SKU' },
                { key: 'price', label: 'Price' },
                { key: 'stock_quantity', label: 'Stock' },
                { key: 'date', label: 'Date' },
            ];
        },
        availableBulkActions() {
            return [
                { value: '', text: 'Select Bulk Action' },
                { value: 'delete', text: 'Delete Selected' },
                { value: 'publish', text: 'Set Status to Published' },
                { value: 'draft', text: 'Set Status to Draft' },
                // { value: 'change_price_percent', text: 'Change Price by %' }, // More complex, example
                // { value: 'change_stock_abs', text: 'Set Stock Quantity' }, // More complex, example
            ];
        }
    },
    methods: {
        async fetchProducts() {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const params = {
                    page: this.currentPage,
                    per_page: this.itemsPerPage,
                    search: this.searchQuery,
                    orderby: this.sortBy,
                    order: this.sortOrder,
                };
                const response = await api.get('/products', { params });
                this.products = response.data;
                this.totalPages = parseInt(response.headers['x-wp-totalpages'], 10) || 1;
                this.selectedProducts = []; // Reset selection on new fetch
                this.selectAll = false;
            } catch (error) {
                console.error("Error fetching products:", error);
                this.errorMessage = error.response?.data?.message || error.message || 'Failed to fetch products.';
                this.products = []; // Clear products on error
                this.totalPages = 1;
            } finally {
                this.isLoading = false;
            }
        },
        handlePageChange(page) {
            this.currentPage = page;
            this.fetchProducts();
        },
        handleSearch() {
            this.currentPage = 1; // Reset to first page on new search
            this.fetchProducts();
        },
        handleSort(columnKey) {
            if (this.sortBy === columnKey) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = columnKey;
                this.sortOrder = 'asc';
            }
            this.fetchProducts();
        },
        getSortIcon(columnKey) {
            if (this.sortBy === columnKey) {
                return this.sortOrder === 'asc' ? '▲' : '▼';
            }
            return '';
        },
        openCreateModal() {
            this.currentProduct = null;
            this.isEditMode = false;
            this.isProductModalOpen = true;
        },
        openEditModal(product) {
            this.currentProduct = { ...product }; // Clone to avoid direct mutation
            this.isEditMode = true;
            this.isProductModalOpen = true;
        },
        handleProductSaveSuccess(updatedProduct) {
            this.isProductModalOpen = false;
            // Optionally, update the specific product in the list or just refetch
            this.fetchProducts(); // Easiest way to ensure data consistency
            // Could also find and update/add product in this.products for smoother UX
        },
        async deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            try {
                await api.delete(`/products/${productId}`, { data: { force: true } }); // force delete
                this.fetchProducts(); // Refresh list
                // Remove from selectedProducts if it was there
                this.selectedProducts = this.selectedProducts.filter(id => id !== productId);
            } catch (error) {
                console.error("Error deleting product:", error);
                alert(`Failed to delete product: ${error.response?.data?.message || error.message}`);
            }
        },
        // --- Bulk Actions ---
        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedProducts = this.products.map(p => p.id);
            } else {
                this.selectedProducts = [];
            }
        },
        updateSelectAllState() {
            if (this.products.length === 0) {
                this.selectAll = false;
                return;
            }
            this.selectAll = this.selectedProducts.length === this.products.length;
        },
        async applyBulkAction() {
            if (!this.bulkAction || this.selectedProducts.length === 0) {
                alert('Please select an action and at least one product.');
                return;
            }
            if (!confirm(`Are you sure you want to apply "${this.bulkAction}" to ${this.selectedProducts.length} products?`)) return;

            this.isLoading = true;
            const updates = this.selectedProducts.map(id => {
                const updateItem = { id };
                if (this.bulkAction === 'publish' || this.bulkAction === 'draft') {
                    updateItem.status = this.bulkAction;
                }
                // Add more complex actions here, e.g., price changes
                // if (this.bulkAction === 'change_price_percent' && this.bulkValue) {
                //    updateItem.price_change_percentage = parseFloat(this.bulkValue);
                // }
                return updateItem;
            });

            try {
                if (this.bulkAction === 'delete') {
                     // For delete, call individual delete or a specific bulk delete endpoint if available
                    const deletePromises = this.selectedProducts.map(id => api.delete(`/products/${id}`, { data: { force: true } }));
                    await Promise.all(deletePromises);
                } else {
                    // For other updates, use the bulk-update endpoint
                    const response = await api.post('/products/bulk-update', updates);
                    // Process response - check for individual errors if API returns them
                    const errors = response.data.filter(item => item.status === 'error');
                    if (errors.length > 0) {
                        alert(`Some products could not be updated: ${errors.map(e => `ID ${e.id}: ${e.message}`).join(', ')}`);
                    }
                }
                this.fetchProducts(); // Refresh list
                this.selectedProducts = [];
                this.bulkAction = '';
                this.bulkValue = '';
            } catch (error) {
                console.error("Error applying bulk action:", error);
                alert(`Failed to apply bulk action: ${error.response?.data?.message || error.message}`);
            } finally {
                this.isLoading = false;
            }
        },
        // --- Inline Editing (Conceptual) ---
        startInlineEdit(product, field) {
            this.editingCell = { productId: product.id, field };
            this.editingValue = product[field];
            // Focus the input field (needs ref in template)
            this.$nextTick(() => {
                const inputField = this.$refs[`inlineInput_${product.id}_${field}`];
                if (inputField && inputField[0]) {
                    inputField[0].focus();
                } else if (inputField) {
                     inputField.focus();
                }
            });
        },
        async saveInlineEdit(product, field) {
            if (product[field] === this.editingValue) {
                this.cancelInlineEdit();
                return;
            }
            const payload = { id: product.id, [field]: this.editingValue };
            try {
                // Optimistic update (or show loader)
                const originalValue = product[field];
                product[field] = this.editingValue; // Reflect change immediately in UI

                await api.put(`/products/${product.id}`, payload);
                // this.fetchProducts(); // Or update product in list directly
                const updatedProductIndex = this.products.findIndex(p => p.id === product.id);
                if (updatedProductIndex !== -1) {
                    this.products[updatedProductIndex] = { ...this.products[updatedProductIndex], ...payload};
                }

            } catch (error) {
                console.error(`Error updating ${field}:`, error);
                alert(`Failed to update ${field}.`);
                product[field] = originalValue; // Revert on error
            } finally {
                this.cancelInlineEdit();
            }
        },
        cancelInlineEdit() {
            this.editingCell = null;
            this.editingValue = '';
        },
        formatPrice(value) {
            const num = parseFloat(value);
            return isNaN(num) ? '-' : `$${num.toFixed(2)}`;
        },
        getProductCategories(product) {
            if (product.categories && product.categories.length > 0) {
                return product.categories.join(', ');
            }
            return 'Uncategorized';
        }
    },
    created() {
        this.fetchProducts();
    },
    watch: {
        selectedProducts() {
            this.updateSelectAllState();
        }
    },
    template: \`
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold text-gray-900">Products</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage your store's products.</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <button @click="openCreateModal" type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto">
                        Add New Product
                    </button>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="mt-6 mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-grow">
                    <input type="text" v-model.lazy="searchQuery" @keyup.enter="handleSearch" placeholder="Search products (name, SKU)..." class="block w-full md:max-w-sm px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="flex items-center space-x-2">
                    <select v-model="itemsPerPage" @change="fetchProducts" class="block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedProducts.length > 0" class="my-4 p-3 bg-gray-50 rounded-md border border-gray-200 flex items-center space-x-3">
                <span class="text-sm font-medium text-gray-700">{{ selectedProducts.length }} product(s) selected.</span>
                <select v-model="bulkAction" class="block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option v-for="action in availableBulkActions" :key="action.value" :value="action.value">{{ action.text }}</option>
                </select>
                <!-- Input for bulkValue if needed for specific actions -->
                <!-- <input v-if="bulkAction === 'change_price_percent'" type="number" v-model="bulkValue" placeholder="e.g., 10 for 10%" class="block w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"> -->
                <button @click="applyBulkAction" :disabled="!bulkAction || isLoading" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                    Apply Bulk Action
                </button>
            </div>

            <!-- Product Table -->
            <div class="mt-8 flex flex-col">
                <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="relative px-6 py-3">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </th>
                                        <th v-for="col in sortableColumns" :key="col.key" @click="handleSort(col.key)" scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100">
                                            {{ col.label }} {{ getSortIcon(col.key) }}
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Categories</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-if="isLoading && products.length === 0">
                                        <td colspan="8" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            <svg class="mx-auto h-8 w-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Loading products...
                                        </td>
                                    </tr>
                                    <tr v-else-if="!isLoading && products.length === 0">
                                        <td colspan="8" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            No products found. Try adjusting your search or filters.
                                        </td>
                                    </tr>
                                    <tr v-for="product in products" :key="product.id" :class="{'bg-blue-50': selectedProducts.includes(product.id)}">
                                        <td class="relative px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" :value="product.id" v-model="selectedProducts" @change="updateSelectAllState" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div class="group relative">
                                                {{ product.name }}
                                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max bg-gray-700 text-white text-xs rounded py-1 px-2">
                                                    ID: {{ product.id }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">{{ product.sku }}</td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div @dblclick="startInlineEdit(product, 'price')" v-if="editingCell && editingCell.productId === product.id && editingCell.field === 'price'">
                                                <input type="text" v-model="editingValue" :ref="\`inlineInput_\${product.id}_price\`" @blur="saveInlineEdit(product, 'price')" @keyup.enter="saveInlineEdit(product, 'price')" @keyup.esc="cancelInlineEdit" class="w-20 px-1 py-0.5 border border-blue-300 rounded-sm text-sm">
                                            </div>
                                            <span v-else @click="startInlineEdit(product, 'price')" class="cursor-pointer hover:bg-gray-100 p-1 rounded">{{ formatPrice(product.price) }}</span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                             <div @dblclick="startInlineEdit(product, 'stock_quantity')" v-if="editingCell && editingCell.productId === product.id && editingCell.field === 'stock_quantity'">
                                                <input type="number" v-model.number="editingValue" :ref="\`inlineInput_\${product.id}_stock_quantity\`" @blur="saveInlineEdit(product, 'stock_quantity')" @keyup.enter="saveInlineEdit(product, 'stock_quantity')" @keyup.esc="cancelInlineEdit" class="w-16 px-1 py-0.5 border border-blue-300 rounded-sm text-sm">
                                            </div>
                                            <span v-else @click="startInlineEdit(product, 'stock_quantity')" class="cursor-pointer hover:bg-gray-100 p-1 rounded">{{ product.stock_quantity !== null ? product.stock_quantity : (product.manage_stock ? '0' : 'N/A') }} {{ product.stock_status }}</span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">{{ getProductCategories(product) }}</td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', product.status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800']">
                                                {{ product.status }}
                                            </span>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button @click="openEditModal(product)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                            <button @click="deleteProduct(product.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <pagination v-if="products.length > 0" :current-page="currentPage" :total-pages="totalPages" @pagechanged="handlePageChange" class="mt-6"></pagination>

            <!-- Product Form Modal -->
            <modal :is-open="isProductModalOpen" :title="isEditMode ? 'Edit Product' : 'Add New Product'" @close="isProductModalOpen = false">
                <product-form v-if="isProductModalOpen" :product-data="currentProduct" :is-edit-mode="isEditMode" @save-success="handleProductSaveSuccess" @close="isProductModalOpen = false"></product-form>
            </modal>

            <!-- Error Message Display -->
             <div v-if="errorMessage && !isLoading" class="mt-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md">
                <p class="font-semibold">An error occurred:</p>
                <p>{{ errorMessage }}</p>
            </div>
        </div>
    \`
};

// --- OrderDetail Component ---
const OrderDetailComponent = {
    props: {
        orderId: {
            type: Number,
            required: true,
        }
    },
    components: {
        'modal': ModalComponent, // If needed for sub-modals, or this itself is a modal content
    },
    data() {
        return {
            order: null,
            isLoading: false,
            errorMessage: '',
            newNote: '',
            isCustomerNote: false,
            availableOrderStatuses: [ // These should ideally come from WooCommerce settings or API
                { value: 'pending', text: 'Pending payment' },
                { value: 'processing', text: 'Processing' },
                { value: 'on-hold', text: 'On hold' },
                { value: 'completed', text: 'Completed' },
                { value: 'cancelled', text: 'Cancelled' },
                { value: 'refunded', text: 'Refunded' },
                { value: 'failed', text: 'Failed' },
            ],
            selectedStatus: '',
        };
    },
    watch: {
        orderId: {
            immediate: true,
            handler(newId) {
                if (newId) {
                    this.fetchOrderDetail();
                } else {
                    this.order = null; // Clear if no orderId
                }
            }
        }
    },
    methods: {
        async fetchOrderDetail() {
            if (!this.orderId) return;
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const response = await api.get(`/orders/${this.orderId}`);
                this.order = response.data;
                this.selectedStatus = this.order.status; // Initialize selectedStatus
            } catch (error) {
                console.error(`Error fetching order details for ID ${this.orderId}:`, error);
                this.errorMessage = error.response?.data?.message || error.message || 'Failed to fetch order details.';
                this.order = null;
            } finally {
                this.isLoading = false;
            }
        },
        async updateStatus() {
            if (!this.order || !this.selectedStatus || this.order.status === this.selectedStatus) {
                return; // No change or no order loaded
            }
            if (!confirm(`Are you sure you want to change status to "${this.selectedStatus}"?`)) {
                this.selectedStatus = this.order.status; // Revert dropdown if cancelled
                return;
            }
            this.isLoading = true;
            try {
                const response = await api.put(`/orders/${this.order.id}/status`, { status: this.selectedStatus });
                this.order = response.data; // Update with fresh order data
                this.selectedStatus = this.order.status; // Re-sync
                alert('Order status updated successfully.');
            } catch (error) {
                console.error("Error updating order status:", error);
                this.errorMessage = error.response?.data?.message || 'Failed to update status.';
                this.selectedStatus = this.order.status; // Revert on error
            } finally {
                this.isLoading = false;
            }
        },
        async addNote() {
            if (!this.order || !this.newNote.trim()) {
                alert('Note content cannot be empty.');
                return;
            }
            this.isLoading = true;
            try {
                const response = await api.post(`/orders/${this.order.id}/notes`, {
                    note: this.newNote,
                    is_customer_note: this.isCustomerNote,
                });
                // Add note to the order object locally or refetch
                if (!this.order.order_notes) this.order.order_notes = [];
                this.order.order_notes.unshift(response.data); // Add to beginning
                this.newNote = '';
                this.isCustomerNote = false;
                alert('Order note added successfully.');
            } catch (error) {
                console.error("Error adding order note:", error);
                this.errorMessage = error.response?.data?.message || 'Failed to add note.';
            } finally {
                this.isLoading = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        },
        formatPrice(value) {
             const num = parseFloat(value);
             return isNaN(num) ? '-' : `$${num.toFixed(2)}`;
        },
    },
    emits: ['close-detail'], // To signal parent to close this view/modal
    template: \`
        <div v-if="isLoading && !order" class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2">Loading order details...</p>
        </div>
        <div v-else-if="errorMessage" class="p-4 bg-red-100 text-red-700 border border-red-300 rounded-md">
            <p class="font-semibold">Error:</p>
            <p>{{ errorMessage }}</p>
            <button @click="$emit('close-detail')" class="mt-2 text-sm text-blue-600 hover:underline">Back to Orders</button>
        </div>
        <div v-else-if="order" class="p-2 sm:p-4 space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-800">Order #{{ order.order_number }}</h2>
                <button @click="$emit('close-detail')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Order Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg shadow">
                <div>
                    <p class="text-sm text-gray-500">Order Date:</p>
                    <p class="font-medium text-gray-800">{{ formatDate(order.date_created) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Order Status:</p>
                    <div class="flex items-center space-x-2">
                        <select v-model="selectedStatus" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option v-for="statusOpt in availableOrderStatuses" :key="statusOpt.value" :value="statusOpt.value">
                                {{ statusOpt.text }}
                            </option>
                        </select>
                        <button @click="updateStatus" :disabled="isLoading || order.status === selectedStatus"
                                class="px-3 py-2 bg-blue-500 text-white text-sm rounded-md hover:bg-blue-600 disabled:opacity-50">
                            Update
                        </button>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Order Total:</p>
                    <p class="font-medium text-gray-800 text-xl">{{ formatPrice(order.total) }} {{ order.currency }}</p>
                </div>
                 <div>
                    <p class="text-sm text-gray-500">Payment Method:</p>
                    <p class="font-medium text-gray-800">{{ order.payment_method_title || 'N/A' }}</p>
                </div>
            </div>

            <!-- Customer & Addresses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Customer Details</h3>
                    <p><strong>Name:</strong> {{ order.customer_name || 'Guest' }}</p>
                    <p><strong>Email:</strong> {{ order.billing_email || 'N/A' }}</p>
                    <p v-if="order.customer_id"><strong>Customer ID:</strong> {{ order.customer_id }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Billing Address</h3>
                    <div v-html="order.billing_address || 'No billing address provided.'"></div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Shipping Address</h3>
                    <div v-html="order.shipping_address || 'No shipping address provided. May be a virtual product.'"></div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in order.line_items" :key="item.id">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800">
                                    {{ item.name }}
                                    <span v-if="item.variation_id" class="text-xs text-gray-500 block">(Variation ID: {{ item.variation_id }})</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ item.sku || 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ item.quantity }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatPrice(item.subtotal / item.quantity) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800 text-right">{{ formatPrice(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                 <div class="mt-4 text-right">
                    <p class="text-lg font-semibold text-gray-800">Total: {{ formatPrice(order.total) }}</p>
                </div>
            </div>

            <!-- Order Notes -->
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Order Notes</h3>
                <div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                    <div v-if="!order.order_notes || order.order_notes.length === 0" class="text-sm text-gray-500">No notes for this order yet.</div>
                    <div v-for="note in order.order_notes" :key="note.id"
                         :class="['p-3 rounded-md text-sm', note.is_customer_note ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50 border border-gray-200']">
                        <p class="whitespace-pre-wrap">{{ note.content }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ formatDate(note.date_created_gmt) }}
                            <span v-if="note.added_by !== 'system'">by {{ note.added_by }}</span>
                            <span v-if="note.is_customer_note"> (Note to customer)</span>
                        </p>
                    </div>
                </div>
                <div class="mt-4 border-t pt-4">
                    <h4 class="text-md font-semibold text-gray-700 mb-2">Add New Note</h4>
                    <textarea v-model="newNote" rows="3" placeholder="Enter note..." class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <div class="mt-2 flex items-center justify-between">
                        <label class="flex items-center text-sm text-gray-700">
                            <input type="checkbox" v-model="isCustomerNote" class="mr-2 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            Visible to customer?
                        </label>
                        <button @click="addNote" :disabled="isLoading || !newNote.trim()" class="px-4 py-2 bg-green-500 text-white text-sm rounded-md hover:bg-green-600 disabled:opacity-50">
                            Add Note
                        </button>
                    </div>
                </div>
            </div>
             <div v-if="order.customer_note" class="bg-yellow-50 p-4 rounded-lg shadow border border-yellow-200">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">Customer Note</h3>
                <p class="text-sm text-yellow-700 whitespace-pre-wrap">{{ order.customer_note }}</p>
            </div>
        </div>
        <div v-else class="p-8 text-center">
            <p>No order selected or order data not found.</p>
            <button @click="$emit('close-detail')" class="mt-2 text-sm text-blue-600 hover:underline">Back to Orders</button>
        </div>
    \`
};


// --- OrderList Component ---
const OrderListComponent = {
    components: {
        'pagination': PaginationComponent,
        'modal': ModalComponent,
        'order-detail': OrderDetailComponent,
    },
    data() {
        return {
            orders: [],
            isLoading: false,
            errorMessage: '',
            // Pagination
            currentPage: 1,
            totalPages: 1,
            itemsPerPage: 15,
            // Search and Filters
            searchQuery: '',
            filters: {
                status: '', // e.g., 'processing', 'completed'
                date_after: '',
                date_before: '',
            },
            availableOrderStatuses: [ // For filter dropdown
                { value: '', text: 'All Statuses' },
                { value: 'pending', text: 'Pending payment' },
                { value: 'processing', text: 'Processing' },
                { value: 'on-hold', text: 'On hold' },
                { value: 'completed', text: 'Completed' },
                { value: 'cancelled', text: 'Cancelled' },
                { value: 'refunded', text: 'Refunded' },
                { value: 'failed', text: 'Failed' },
            ],
            // Order Detail Modal
            isOrderDetailModalOpen: false,
            selectedOrderId: null,
        };
    },
    methods: {
        async fetchOrders() {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const params = {
                    page: this.currentPage,
                    per_page: this.itemsPerPage,
                    search: this.searchQuery.trim(),
                    status: this.filters.status ? [this.filters.status] : [], // API expects array for status
                    date_after: this.filters.date_after,
                    date_before: this.filters.date_before,
                    orderby: 'date', // Default sort for orders
                    order: 'desc',
                };
                // Remove empty params
                Object.keys(params).forEach(key => {
                    if (params[key] === '' || (Array.isArray(params[key]) && params[key].length === 0)) {
                        delete params[key];
                    }
                });

                const response = await api.get('/orders', { params });
                this.orders = response.data;
                this.totalPages = parseInt(response.headers['x-wp-totalpages'], 10) || 1;
            } catch (error) {
                console.error("Error fetching orders:", error);
                this.errorMessage = error.response?.data?.message || error.message || 'Failed to fetch orders.';
                this.orders = [];
                this.totalPages = 1;
            } finally {
                this.isLoading = false;
            }
        },
        handlePageChange(page) {
            this.currentPage = page;
            this.fetchOrders();
        },
        applyFilters() { // Used by search input and filter dropdowns
            this.currentPage = 1;
            this.fetchOrders();
        },
        viewOrderDetails(orderId) {
            this.selectedOrderId = orderId;
            this.isOrderDetailModalOpen = true;
        },
        closeOrderDetailModal() {
            this.isOrderDetailModalOpen = false;
            this.selectedOrderId = null;
            this.fetchOrders(); // Re-fetch orders in case status/notes changed
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        },
        formatPrice(value) {
             const num = parseFloat(value);
             return isNaN(num) ? '-' : `$${num.toFixed(2)}`;
        },
        getStatusClass(status) {
            const S = status.toLowerCase();
            if (S === 'completed') return 'bg-green-100 text-green-800';
            if (S === 'processing') return 'bg-blue-100 text-blue-800';
            if (S === 'pending' || S === 'on-hold') return 'bg-yellow-100 text-yellow-800';
            if (S === 'cancelled' || S === 'failed' || S === 'refunded') return 'bg-red-100 text-red-800';
            return 'bg-gray-100 text-gray-800';
        }
    },
    created() {
        this.fetchOrders();
    },
    template: \`
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold text-gray-900">Orders</h1>
                    <p class="mt-2 text-sm text-gray-700">View and manage customer orders.</p>
                </div>
                <!-- Add Order button could go here if manual creation is enabled -->
            </div>

            <!-- Search and Filters -->
            <div class="mt-6 mb-4 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="orderSearch" class="block text-sm font-medium text-gray-700">Search Orders</label>
                    <input type="text" v-model.lazy="searchQuery" @keyup.enter="applyFilters" id="orderSearch"
                           placeholder="Order ID, customer name, email..."
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="orderStatusFilter" class="block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="filters.status" @change="applyFilters" id="orderStatusFilter"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option v-for="statusOpt in availableOrderStatuses" :key="statusOpt.value" :value="statusOpt.value">{{ statusOpt.text }}</option>
                    </select>
                </div>
                 <div>
                    <button @click="applyFilters" class="w-full inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Filter
                    </button>
                </div>
                <!-- Date filters can be added here if needed -->
            </div>

            <!-- Orders Table -->
            <div class="mt-8 flex flex-col">
                <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Order</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Customer</th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Total</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-if="isLoading && orders.length === 0">
                                        <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            <svg class="mx-auto h-8 w-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Loading orders...
                                        </td>
                                    </tr>
                                    <tr v-else-if="!isLoading && orders.length === 0">
                                        <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            No orders found. Try adjusting your search or filters.
                                        </td>
                                    </tr>
                                    <tr v-for="order in orders" :key="order.id">
                                        <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-blue-600 hover:text-blue-800">
                                            <a href="#" @click.prevent="viewOrderDetails(order.id)">#{{ order.order_number }}</a>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(order.date_created) }}</td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusClass(order.status)]">
                                                {{ order.status.replace('wc-', '') }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ order.customer_name || 'Guest' }}
                                            <span v-if="order.billing_email" class="block text-xs text-gray-400">{{ order.billing_email }}</span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-800 text-right">{{ formatPrice(order.total) }} {{ order.currency }}</td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button @click="viewOrderDetails(order.id)" class="text-blue-600 hover:text-blue-900">View</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <pagination v-if="orders.length > 0" :current-page="currentPage" :total-pages="totalPages" @pagechanged="handlePageChange" class="mt-6"></pagination>

            <!-- Order Detail Modal -->
            <modal :is-open="isOrderDetailModalOpen" :title="'Order Details'" @close="closeOrderDetailModal" class="modal-xl"> <!-- Custom class for wider modal -->
                 <order-detail v-if="selectedOrderId" :order-id="selectedOrderId" @close-detail="closeOrderDetailModal"></order-detail>
            </modal>

             <div v-if="errorMessage && !isLoading" class="mt-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md">
                <p class="font-semibold">An error occurred:</p>
                <p>{{ errorMessage }}</p>
            </div>
        </div>
    \`
};


// --- ReportsDashboard Component ---
const ReportsDashboardComponent = {
    components: {
        'pagination': PaginationComponent, // For low stock pagination
    },
    data() {
        return {
            isLoadingSales: false,
            isLoadingBestsellers: false,
            isLoadingLowStock: false,
            salesData: null,
            bestsellers: [],
            lowStockItems: [],
            lowStockPagination: {
                currentPage: 1,
                totalPages: 1,
                perPage: 10, // Default for low stock items
            },
            errorSales: '',
            errorBestsellers: '',
            errorLowStock: '',
            selectedPeriod: '7days',
            periods: [
                { value: '7days', text: 'Last 7 Days' },
                { value: '30days', text: 'Last 30 Days' },
                { value: 'current_month', text: 'Current Month' },
                { value: 'last_month', text: 'Last Month' },
                // Custom period would require date pickers, which are out of scope for this simple version
            ],
            bestsellersLimit: 5, // Default limit for bestsellers
            lowStockThreshold: 5, // Could be fetched or configured
        };
    },
    computed: {
        maxDailySale() {
            if (this.salesData && this.salesData.daily_sales_data && this.salesData.daily_sales_data.length > 0) {
                return Math.max(...this.salesData.daily_sales_data.map(d => d.total), 0);
            }
            return 0;
        }
    },
    methods: {
        async fetchAllReports() {
            this.fetchSalesData();
            this.fetchBestsellers();
            this.fetchLowStockItems();
        },
        async fetchSalesData() {
            this.isLoadingSales = true;
            this.errorSales = '';
            try {
                const response = await api.get('/reports/sales', { params: { period: this.selectedPeriod } });
                this.salesData = response.data;
            } catch (error) {
                console.error("Error fetching sales data:", error);
                this.errorSales = error.response?.data?.message || 'Failed to load sales data.';
                this.salesData = null;
            } finally {
                this.isLoadingSales = false;
            }
        },
        async fetchBestsellers() {
            this.isLoadingBestsellers = true;
            this.errorBestsellers = '';
            try {
                const response = await api.get('/reports/bestsellers', { params: { period: this.selectedPeriod, limit: this.bestsellersLimit } });
                this.bestsellers = response.data;
            } catch (error) {
                console.error("Error fetching bestsellers:", error);
                this.errorBestsellers = error.response?.data?.message || 'Failed to load bestselling products.';
                this.bestsellers = [];
            } finally {
                this.isLoadingBestsellers = false;
            }
        },
        async fetchLowStockItems(page = 1) {
            this.isLoadingLowStock = true;
            this.errorLowStock = '';
            try {
                const response = await api.get('/reports/low-stock', {
                    params: {
                        threshold: this.lowStockThreshold,
                        page: page,
                        per_page: this.lowStockPagination.perPage
                    }
                });
                this.lowStockItems = response.data;
                this.lowStockPagination.currentPage = page;
                this.lowStockPagination.totalPages = parseInt(response.headers['x-wp-totalpages'], 10) || 1;
            } catch (error) {
                console.error("Error fetching low stock items:", error);
                this.errorLowStock = error.response?.data?.message || 'Failed to load low stock items.';
                this.lowStockItems = [];
            } finally {
                this.isLoadingLowStock = false;
            }
        },
        handlePeriodChange() {
            this.fetchSalesData();
            this.fetchBestsellers();
        },
        handleLowStockPageChange(page) {
            this.fetchLowStockItems(page);
        },
        formatPrice(value) {
            const num = parseFloat(value);
            return isNaN(num) ? '-' : `$${num.toFixed(2)}`;
        },
        formatDate(dateString) {
            if (!dateString) return '';
            // Get only month and day for chart
            const date = new Date(dateString + 'T00:00:00'); // Ensure parsing as local date
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }
    },
    mounted() {
        // Fetch initial low stock threshold from WooCommerce settings if possible, or use default
        // For simplicity, this.lowStockThreshold is pre-set. A call to a settings endpoint could fetch it.
        this.fetchAllReports();
    },
    template: \`
        <div class="p-4 sm:p-6 lg:p-8 space-y-8">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Reports Dashboard</h1>
            </div>

            <!-- Period Selector -->
            <div class="mb-6 max-w-xs">
                <label for="reportPeriod" class="block text-sm font-medium text-gray-700">Select Period:</label>
                <select v-model="selectedPeriod" @change="handlePeriodChange" id="reportPeriod" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option v-for="periodOpt in periods" :key="periodOpt.value" :value="periodOpt.value">{{ periodOpt.text }}</option>
                </select>
            </div>

            <!-- Sales Report Section -->
            <section class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Sales Summary</h2>
                <div v-if="isLoadingSales" class="text-center py-4"><svg class="mx-auto h-8 w-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>
                <div v-else-if="errorSales" class="text-red-500">{{ errorSales }}</div>
                <div v-else-if="salesData" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm text-blue-700">Total Sales</p>
                            <p class="text-3xl font-bold text-blue-900">{{ formatPrice(salesData.total_sales) }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg mt-4">
                            <p class="text-sm text-green-700">Number of Orders</p>
                            <p class="text-3xl font-bold text-green-900">{{ salesData.order_count }}</p>
                        </div>
                         <p class="text-xs text-gray-500 mt-2">Period: {{ salesData.period.start }} to {{ salesData.period.end }}</p>
                    </div>
                    <div v-if="salesData.daily_sales_data && salesData.daily_sales_data.length > 0">
                        <h3 class="text-md font-semibold text-gray-700 mb-2">Sales Trend ({{ salesData.daily_sales_data.length }} days)</h3>
                        <div class="flex items-end border-b border-gray-300 h-48 space-x-1 px-1" aria-label="Sales chart">
                            <div v-for="day in salesData.daily_sales_data" :key="day.date" class="relative flex-1 bg-blue-500 hover:bg-blue-600 group"
                                 :style="{ height: maxDailySale > 0 ? (day.total / maxDailySale * 100) + '%' : '0%' }">
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-1.5 py-0.5 text-xs bg-gray-700 text-white rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                    {{ formatDate(day.date) }}: {{ formatPrice(day.total) }}
                                </span>
                            </div>
                        </div>
                         <div class="flex justify-between text-xs text-gray-500 mt-1 px-1">
                            <span>{{ formatDate(salesData.daily_sales_data[0].date) }}</span>
                            <span>{{ formatDate(salesData.daily_sales_data[salesData.daily_sales_data.length - 1].date) }}</span>
                        </div>
                    </div>
                    <div v-else class="text-gray-500">No daily sales data to display for this period.</div>
                </div>
            </section>

            <!-- Bestselling Products Section -->
            <section class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Bestselling Products (Top {{bestsellersLimit}})</h2>
                <div v-if="isLoadingBestsellers" class="text-center py-4"><svg class="mx-auto h-8 w-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>
                <div v-else-if="errorBestsellers" class="text-red-500">{{ errorBestsellers }}</div>
                <ul v-else-if="bestsellers.length > 0" class="divide-y divide-gray-200">
                    <li v-for="item in bestsellers" :key="item.product_id + '-' + item.variation_id" class="py-3 flex justify-between items-center">
                        <span class="text-gray-700">{{ item.name }} <em class="text-xs text-gray-500">(ID: {{item.variation_id || item.product_id}})</em></span>
                        <span class="font-medium text-gray-900">{{ item.quantity_sold }} sold</span>
                    </li>
                </ul>
                <p v-else class="text-gray-500">No bestselling products found for this period.</p>
            </section>

            <!-- Low Stock Alerts Section -->
            <section class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Low Stock Alerts (Threshold: {{ lowStockThreshold }})</h2>
                <div v-if="isLoadingLowStock" class="text-center py-4"><svg class="mx-auto h-8 w-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>
                <div v-else-if="errorLowStock" class="text-red-500">{{ errorLowStock }}</div>
                <div v-else-if="lowStockItems.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining Qty</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in lowStockItems" :key="item.product_id">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800">{{ item.name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ item.sku || 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-red-600 font-semibold">{{ item.stock_quantity }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <a :href="item.permalink.replace('/product/', '/wp-admin/post.php?post=' + item.product_id + '&action=edit')" target="_blank" class="text-blue-600 hover:underline">Edit Product</a>
                                    <!-- Note: Above link is a guess for wp-admin. A direct link to Vue app edit page would be better if router exists. -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <pagination v-if="lowStockItems.length > 0 && lowStockPagination.totalPages > 1"
                                :current-page="lowStockPagination.currentPage"
                                :total-pages="lowStockPagination.totalPages"
                                @pagechanged="handleLowStockPageChange" class="mt-4"></pagination>
                </div>
                <p v-else class="text-gray-500">No products currently below the low stock threshold.</p>
            </section>
        </div>
    \`
};


// --- App Component (Main) ---
const AppComponent = {
    components: {
        'product-list': ProductListComponent,
        'order-list': OrderListComponent,
        'reports-dashboard': ReportsDashboardComponent,
    },
    data() {
        return {
            currentView: 'products', // Default view
        };
    },
    template: \`
        <div class="min-h-screen bg-gray-100">
            <div class="flex flex-col md:flex-row flex-1">
                <aside class="bg-gray-800 text-white w-full md:w-64 min-h-screen md:min-h-0 md:sticky md:top-0 md:self-start">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-white">Store Manager</h2>
                        <nav class="mt-6">
                            <a @click.prevent="currentView = 'products'" href="#"
                               :class="['block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white', currentView === 'products' ? 'bg-gray-900 text-white' : 'text-gray-300']">
                                Products
                            </a>
                            <a @click.prevent="currentView = 'orders'" href="#"
                               :class="['block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white', currentView === 'orders' ? 'bg-gray-900 text-white' : 'text-gray-300']">
                                Orders
                            </a>
                             <a @click.prevent="currentView = 'reports'" href="#"
                               :class="['block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white', currentView === 'reports' ? 'bg-gray-900 text-white' : 'text-gray-300']">
                                Reports
                            </a>
                        </nav>
                    </div>
                </aside>

                <main class="flex-1">
                    <div v-if="currentView === 'products'">
                        <product-list></product-list>
                    </div>
                    <div v-if="currentView === 'orders'">
                        <order-list></order-list>
                    </div>
                     <div v-if="currentView === 'reports'">
                        <reports-dashboard></reports-dashboard>
                    </div>
                </main>
            </div>
        </div>
    \`
};

// --- Vue App Initialization ---
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('store-management-app')) {
        const app = Vue.createApp(AppComponent);

        app.component('modal', ModalComponent);
        app.component('pagination', PaginationComponent);
        app.component('product-form', ProductFormComponent);
        // ReportsDashboardComponent is registered locally in AppComponent.
        // Order components are also locally registered.

        app.mount('#store-management-app');
        console.log('Store Management Vue app initialized and mounted.');
    } else {
        console.warn('Store Management App placeholder #store-management-app not found. Vue app not mounted.');
    }
});

console.log('store-management-app.js fully loaded. Vue app initialization logic is in place.');
