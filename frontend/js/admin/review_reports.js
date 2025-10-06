class ReportsManager {
    constructor() {
        this.initializeFilters();
        this.loadReports();
    }

    initializeFilters() {
        document.getElementById('statusFilter').addEventListener('change', () => this.loadReports());
        document.getElementById('reasonFilter').addEventListener('change', () => this.loadReports());
    }

    async loadReports() {
        try {
            const status = document.getElementById('statusFilter').value;
            const reason = document.getElementById('reasonFilter').value;

            const response = await fetch('../api/admin/get_reports.php?' + new URLSearchParams({
                status,
                reason
            }));

            const data = await response.json();
            
            if (data.success) {
                this.renderReports(data.reports);
            }
        } catch (error) {
            console.error('Error loading reports:', error);
        }
    }

    renderReports(reports) {
        const tbody = document.getElementById('reportsTableBody');
        tbody.innerHTML = reports.map(report => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${new Date(report.created_at).toLocaleDateString('tr-TR')}
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm">
                        <div class="font-medium">${report.review_excerpt}</div>
                        <div class="text-gray-500">${report.carwash_name}</div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${this.getReasonClass(report.reason)}">
                        ${this.getReasonText(report.reason)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${this.getStatusClass(report.status)}">
                        ${this.getStatusText(report.status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button onclick="reportsManager.showReportDetail(${report.id})"
                            class="text-blue-600 hover:text-blue-900">
                        Detaylar
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async showReportDetail(reportId) {
        try {
            const response = await fetch(`../api/admin/get_report_detail.php?id=${reportId}`);
            const data = await response.json();
            
            if (data.success) {
                const modal = document.getElementById('reportModal');
                const detail = document.getElementById('reportDetail');
                
                detail.innerHTML = this.generateReportDetailHTML(data.report);
                modal.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error loading report detail:', error);
        }
    }

    generateReportDetailHTML(report) {
        return `
            <div class="space-y-4">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-bold">Rapor #${report.id}</h3>
                    <button onclick="reportsManager.closeModal()"
                            class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-2">Değerlendirme</h4>
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="mb-2">
                            <span class="text-yellow-400">
                                ${'★'.repeat(report.review.rating)}
                            </span>
                        </div>
                        <p>${report.review.comment}</p>
                        <div class="text-sm text-gray-500 mt-2">
                            ${report.review.user_name} tarafından
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-2">Bildirim Detayları</h4>
                    <div class="space-y-2">
                        <p><strong>Bildiren:</strong> ${report.reporter_name}</p>
                        <p><strong>Neden:</strong> ${this.getReasonText(report.reason)}</p>
                        <p><strong>Açıklama:</strong> ${report.description}</p>
                    </div>
                </div>

                <div class="border-t pt-4 flex justify-end space-x-2">
                    ${report.status === 'pending' ? `
                        <button onclick="reportsManager.updateReportStatus(${report.id}, 'dismissed')"
                                class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Reddet
                        </button>
                        <button onclick="reportsManager.updateReportStatus(${report.id}, 'resolved')"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Değerlendirmeyi Kaldır
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    async updateReportStatus(reportId, status) {
        try {
            const response = await fetch('../api/admin/update_report_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reportId, status })
            });

            const data = await response.json();
            
            if (data.success) {
                this.closeModal();
                this.loadReports();
            }
        } catch (error) {
            console.error('Error updating report:', error);
        }
    }

    closeModal() {
        document.getElementById('reportModal').classList.add('hidden');
    }

    getReasonClass(reason) {
        const classes = {
            spam: 'bg-yellow-100 text-yellow-800',
            offensive: 'bg-red-100 text-red-800',
            inappropriate: 'bg-orange-100 text-orange-800',
            other: 'bg-gray-100 text-gray-800'
        };
        return classes[reason] || classes.other;
    }

    getReasonText(reason) {
        const texts = {
            spam: 'Spam',
            offensive: 'Rahatsız Edici',
            inappropriate: 'Uygunsuz',
            other: 'Diğer'
        };
        return texts[reason] || reason;
    }

    getStatusClass(status) {
        const classes = {
            pending: 'bg-yellow-100 text-yellow-800',
            resolved: 'bg-green-100 text-green-800',
            dismissed: 'bg-gray-100 text-gray-800'
        };
        return classes[status] || classes.pending;
    }

    getStatusText(status) {
        const texts = {
            pending: 'Beklemede',
            resolved: 'Çözüldü',
            dismissed: 'Reddedildi'
        };
        return texts[status] || status;
    }
}

// Initialize
const reportsManager = new ReportsManager();