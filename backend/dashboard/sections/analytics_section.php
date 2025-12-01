<?php
/**
 * Analytics Section Fragment
 * Lazy-loaded section for admin dashboard analytics
 */

// This fragment is loaded via AJAX, so it doesn't need full page structure
// Just return the content to be inserted into the deferred container
?>

<div style="padding: 1.5rem; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-chart-line"></i> Analitik ve Raporlar
    </h3>

    <!-- Quick Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <!-- Total Revenue -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Toplam Gelir</p>
                    <h4 style="margin: 0.5rem 0 0 0; font-size: 1.75rem;">₺0</h4>
                </div>
                <i class="fas fa-lira-sign" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
        </div>

        <!-- Total Bookings -->
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Toplam Rezervasyon</p>
                    <h4 style="margin: 0.5rem 0 0 0; font-size: 1.75rem;">0</h4>
                </div>
                <i class="fas fa-calendar-check" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
        </div>

        <!-- Active Customers -->
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Aktif Müşteri</p>
                    <h4 style="margin: 0.5rem 0 0 0; font-size: 1.75rem;">0</h4>
                </div>
                <i class="fas fa-users" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
        </div>

        <!-- Completion Rate -->
        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Tamamlanma Oranı</p>
                    <h4 style="margin: 0.5rem 0 0 0; font-size: 1.75rem;">0%</h4>
                </div>
                <i class="fas fa-chart-pie" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>

    <!-- Charts Placeholder -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        <!-- Revenue Chart -->
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 1px solid #e9ecef;">
            <h5 style="margin: 0 0 1rem 0; color: #333;">Gelir Trendi</h5>
            <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                <i class="fas fa-chart-area" style="font-size: 3rem; opacity: 0.3;"></i>
            </div>
            <p style="text-align: center; color: #64748b; font-size: 0.875rem; margin: 1rem 0 0 0;">
                Grafik verileri yükleniyor...
            </p>
        </div>

        <!-- Booking Distribution -->
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 1px solid #e9ecef;">
            <h5 style="margin: 0 0 1rem 0; color: #333;">Rezervasyon Dağılımı</h5>
            <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                <i class="fas fa-chart-pie" style="font-size: 3rem; opacity: 0.3;"></i>
            </div>
            <p style="text-align: center; color: #64748b; font-size: 0.875rem; margin: 1rem 0 0 0;">
                Grafik verileri yükleniyor...
            </p>
        </div>
    </div>

    <!-- Info Message -->
    <div style="margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
        <p style="margin: 0; color: #1565c0; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-info-circle"></i>
            <span>Analitik veriler ve detaylı raporlar için veri toplama işlemleri devam etmektedir.</span>
        </p>
    </div>
</div>

<script>
(function(){
    'use strict';
    // Analytics section specific JavaScript
    console.log('Analytics section loaded successfully');
    
    // TODO: Fetch real analytics data from API
    // Example:
    // fetch('/carwash_project/backend/api/analytics/dashboard.php', { credentials: 'same-origin' })
    //     .then(resp => resp.json())
    //     .then(data => {
    //         // Update stats with real data
    //     })
    //     .catch(err => console.error('Failed to load analytics:', err));
})();
</script>
