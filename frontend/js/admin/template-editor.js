class TemplateEditor {
    constructor() {
        this.endpoints = {
            templates: '/carwash_project/backend/api/admin/templates/list.php',
            save: '/carwash_project/backend/api/admin/templates/save.php',
            preview: '/carwash_project/backend/api/admin/templates/preview.php'
        };
        this.currentTemplate = null;
        this.editor = null;
        this.init();
    }

    async init() {
        this.initializeEditor();
        this.setupEventListeners();
        await this.loadTemplates();
        this.initializeVariableInsert();
    }

    initializeEditor() {
        // Initialize TinyMCE editor
        tinymce.init({
            selector: '#templateContent',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
                'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: `
                undo redo | formatselect | bold italic backcolor | 
                alignleft aligncenter alignright alignjustify | 
                bullist numlist outdent indent | removeformat | variables | help
            `,
            setup: (editor) => {
                this.editor = editor;
                this.addVariablesButton(editor);
            }
        });
    }

    setupEventListeners() {
        // Template selection
        document.getElementById('templateSelector')?.addEventListener('change', (e) => {
            this.loadTemplate(e.target.value);
        });

        // Save button
        document.getElementById('saveTemplate')?.addEventListener('click', () => {
            this.saveTemplate();
        });

        // Preview button
        document.getElementById('previewTemplate')?.addEventListener('click', () => {
            this.previewTemplate();
        });
    }

    async loadTemplates() {
        try {
            const response = await fetch(this.endpoints.templates);
            const templates = await response.json();
            
            const selector = document.getElementById('templateSelector');
            if (selector) {
                selector.innerHTML = templates.map(template => `
                    <option value="${template.id}">${template.name}</option>
                `).join('');
                
                if (templates.length) {
                    this.loadTemplate(templates[0].id);
                }
            }
        } catch (error) {
            this.showError('Failed to load templates');
        }
    }

    async loadTemplate(templateId) {
        try {
            const response = await fetch(`${this.endpoints.templates}?id=${templateId}`);
            const template = await response.json();
            
            this.currentTemplate = template;
            this.editor?.setContent(template.content);
            
            // Update metadata fields
            document.getElementById('templateName').value = template.name;
            document.getElementById('templateType').value = template.type;
        } catch (error) {
            this.showError('Failed to load template');
        }
    }

    async saveTemplate() {
        if (!this.currentTemplate) return;

        const templateData = {
            id: this.currentTemplate.id,
            name: document.getElementById('templateName').value,
            type: document.getElementById('templateType').value,
            content: this.editor.getContent()
        };

        try {
            const response = await fetch(this.endpoints.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(templateData)
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Template saved successfully');
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to save template');
        }
    }

    async previewTemplate() {
        if (!this.currentTemplate) return;

        try {
            const response = await fetch(this.endpoints.preview, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    content: this.editor.getContent(),
                    type: document.getElementById('templateType').value
                })
            });

            const previewHtml = await response.text();
            this.showPreviewModal(previewHtml);
        } catch (error) {
            this.showError('Failed to generate preview');
        }
    }

    addVariablesButton(editor) {
        editor.ui.registry.addMenuButton('variables', {
            text: 'Variables',
            fetch: (callback) => {
                const items = [
                    { type: 'menuitem', text: 'Customer Name', onAction: () => editor.insertContent('{{customer_name}}') },
                    { type: 'menuitem', text: 'Booking Time', onAction: () => editor.insertContent('{{booking_time}}') },
                    { type: 'menuitem', text: 'Service Type', onAction: () => editor.insertContent('{{service_type}}') },
                    { type: 'menuitem', text: 'Location', onAction: () => editor.insertContent('{{location}}') },
                    { type: 'menuitem', text: 'Price', onAction: () => editor.insertContent('{{price}}') }
                ];
                callback(items);
            }
        });
    }

    showPreviewModal(html) {
        const modal = document.createElement('div');
        modal.className = 'preview-modal';
        modal.innerHTML = `
            <div class="preview-content">
                <div class="preview-header">
                    <h3>Template Preview</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="preview-body">
                    ${html}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.querySelector('.close-btn').onclick = () => modal.remove();
    }

    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'success-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize template editor
document.addEventListener('DOMContentLoaded', () => new TemplateEditor());