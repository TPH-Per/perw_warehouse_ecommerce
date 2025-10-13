// Category management module
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class CategoryModule {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCategories();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'add-category-btn') {
                this.showCategoryForm();
            } else if (e.target.classList.contains('edit-category-btn')) {
                const categoryId = e.target.dataset.categoryId;
                this.showCategoryForm(categoryId);
            } else if (e.target.classList.contains('delete-category-btn')) {
                const categoryId = e.target.dataset.categoryId;
                this.deleteCategory(categoryId);
            }
        });

        document.addEventListener('submit', (e) => {
            if (e.target.id === 'category-form') {
                e.preventDefault();
                this.handleCategorySubmit(e.target);
            }
        });
    }

    async loadCategories() {
        const container = document.getElementById('categories-list');
        if (!container) return;

        UIHelpers.showLoading(container, 'Loading categories...');

        try {
            const response = await api.get('/admin/categories');
            this.renderCategoriesList(response.categories);
        } catch (error) {
            container.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load categories</div>';
        }
    }

    renderCategoriesList(categories) {
        const container = document.getElementById('categories-list');
        
        container.innerHTML = `
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">Categories</h2>
                <button id="add-category-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Add Category
                </button>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${categories.map(category => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">${category.name}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">${category.description || 'No description'}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 text-xs font-semibold rounded-full ${
                                        category.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }">
                                        ${category.status}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="edit-category-btn text-blue-600 hover:text-blue-900 mr-3"
                                            data-category-id="${category.id}">Edit</button>
                                    <button class="delete-category-btn text-red-600 hover:text-red-900"
                                            data-category-id="${category.id}">Delete</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    async showCategoryForm(categoryId = null) {
        const isEdit = categoryId !== null;
        let category = null;

        if (isEdit) {
            try {
                const response = await api.get(`/admin/categories/${categoryId}`);
                category = response.category;
            } catch (error) {
                UIHelpers.showToast('Failed to load category details', 'error');
                return;
            }
        }

        const modalContent = `
            <form id="category-form" class="space-y-4">
                <input type="hidden" name="category_id" value="${categoryId || ''}">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                    <input type="text" name="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           value="${category?.name || ''}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md">${category?.description || ''}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="active" ${category?.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${category?.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            </form>
        `;

        UIHelpers.createModal(
            isEdit ? 'Edit Category' : 'Add Category',
            modalContent,
            [
                {
                    text: 'Cancel',
                    class: 'bg-gray-300 text-gray-700',
                    onclick: `closeModal('modal-${Date.now()}')`
                },
                {
                    text: isEdit ? 'Update' : 'Create',
                    class: 'bg-blue-600 text-white',
                    onclick: `document.getElementById('category-form').dispatchEvent(new Event('submit'))`
                }
            ]
        );
    }

    async handleCategorySubmit(form) {
        const formData = new FormData(form);
        const categoryId = formData.get('category_id');
        const isEdit = categoryId && categoryId !== '';

        try {
            const data = {
                name: formData.get('name'),
                description: formData.get('description'),
                status: formData.get('status')
            };

            let response;
            if (isEdit) {
                response = await api.put(`/admin/categories/${categoryId}`, data);
            } else {
                response = await api.post('/admin/categories', data);
            }

            UIHelpers.showToast(response.message, 'success');
            this.loadCategories();
            
            // Close modal
            const modal = document.querySelector('[id^="modal-"]');
            if (modal) {
                document.body.removeChild(modal);
            }

        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
            UIHelpers.showFormErrors(form, error.errors || {});
        }
    }

    async deleteCategory(categoryId) {
        if (!confirm('Are you sure you want to delete this category?')) {
            return;
        }

        try {
            const response = await api.delete(`/admin/categories/${categoryId}`);
            UIHelpers.showToast(response.message, 'success');
            this.loadCategories();
        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
        }
    }
}