/**
 * Customer Dashboard Vehicle Debug Helper
 *
 * Provides comprehensive debugging for the vehicle section including:
 * - Global error catching
 * - Ajax request/response monitoring
 * - Form submission logging
 * - Image loading verification
 * - Delete button validation
 */

(function () {
    'use strict';

    // Ensure CONFIG and CONFIG.API are safely initialized
    window.CONFIG = window.CONFIG || {};
    window.CONFIG.API = window.CONFIG.API || {
        VEHICLE_CREATE: '/carwash_project/backend/dashboard/vehicle_api.php',
        VEHICLE_CHECK_IMAGES: '/carwash_project/backend/dashboard/vehicle_api.php?action=check_images'
    };

    // Create debug panel
    const debugPanel = document.createElement('div');
    debugPanel.id = 'vehicle-debug-panel';
    debugPanel.style.cssText = `
        position: fixed;
        bottom: 10px;
        right: 10px;
        width: 400px;
        max-height: 300px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        padding: 10px;
        border-radius: 5px;
        z-index: 9999;
        overflow-y: auto;
        display: none;
    `;

    const debugHeader = document.createElement('div');
    debugHeader.style.cssText = `
        font-weight: bold;
        margin-bottom: 5px;
        border-bottom: 1px solid white;
        padding-bottom: 5px;
        cursor: pointer;
    `;
    debugHeader.textContent = 'Vehicle Debug Panel (Click to Collapse/Expand)';

    const debugContent = document.createElement('div');
    debugContent.id = 'debug-content';
    debugContent.style.cssText = `
        white-space: pre-wrap;
        word-wrap: break-word;
    `;

    debugPanel.appendChild(debugHeader);
    debugPanel.appendChild(debugContent);
    document.body.appendChild(debugPanel);

    // Show/hide toggle
    debugHeader.addEventListener('click', function () {
        debugContent.style.display = debugContent.style.display === 'none' ? 'block' : 'none';
    });

    // Logging function
    function log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const prefix = `[${timestamp}] ${type.toUpperCase()}: `;
        const content = debugPanel.querySelector('#debug-content');
        content.textContent += prefix + message + '\n\n';
        content.scrollTop = content.scrollHeight;
        debugPanel.style.display = 'block';
    }

    // Ensure CSRF token is loaded and validated
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    window.CONFIG = window.CONFIG || {};
    window.CONFIG.CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : null;

    if (!window.CONFIG.CSRF_TOKEN) {
        log('CSRF_TOKEN is missing. Ensure meta tag or backend injection is working.', 'error');
        console.warn('CSRF_TOKEN is missing. Ensure meta tag or backend injection is working.');
    } else {
        log(`CSRF_TOKEN loaded successfully: ${window.CONFIG.CSRF_TOKEN.substring(0, 8)}...`, 'success');
    }

    // Debug CSRF status
    function debugCSRFStatus() {
        const token = window.CONFIG.CSRF_TOKEN;
        if (token) {
            log(`CSRF_TOKEN validation passed: ${token.substring(0, 8)}...`, 'success');
        } else {
            log('CSRF_TOKEN validation failed. Token is missing or invalid.', 'error');
            console.warn('CSRF_TOKEN validation failed. Token is missing or invalid.');
        }
    }

    debugCSRFStatus();

    // Hook into forms and AJAX requests
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const formData = new FormData(form);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', window.CONFIG.CSRF_TOKEN);
            }
        });
    });

    const originalFetch = window.fetch;
    window.fetch = async function (url, options = {}) {
        if (options.method === 'POST') {
            const headers = options.headers || {};
            const body = options.body || new FormData();

            if (body instanceof FormData && !body.has('csrf_token')) {
                body.append('csrf_token', window.CONFIG.CSRF_TOKEN);
            }

            options.body = body;
            options.headers = headers;
        }

        return originalFetch(url, options);
    };

    // 1. Global error handler
    window.addEventListener('error', function (e) {
        log(`JavaScript Error: ${e.message}\nFile: ${e.filename}\nLine: ${e.lineno}\nStack: ${e.error ? e.error.stack : 'N/A'}`, 'error');
    });

    window.addEventListener('unhandledrejection', function (e) {
        log(`Unhandled Promise Rejection: ${e.reason}`, 'error');
    });

    // 2. Intercept Ajax requests
    const loggedRequests = new Set(); // Prevent duplicate logs
    window.fetch = function (...args) {
        const url = args[0];
        if (typeof url === 'string' && (url.includes('vehicle_api.php') || url.includes('Customer_Dashboard_process.php'))) {
            if (!loggedRequests.has(url)) {
                log(`Ajax Request: ${args[0]}\nMethod: ${args[1]?.method || 'GET'}`, 'ajax');
                loggedRequests.add(url);
            }

            return originalFetch.apply(this, args).then(response => {
                // Clone response for inspection
                const clonedResponse = response.clone();

                return clonedResponse.text().then(text => {
                    log(`Ajax Response: ${response.url}\nStatus: ${response.status}`, 'ajax');

                    try {
                        const json = JSON.parse(text);
                        if (json.success === false) {
                            log(`Ajax Error Response:\nMessage: ${json.message || 'N/A'}\nError Type: ${json.error_type || 'N/A'}\nFull Response: ${JSON.stringify(json, null, 2)}`, 'error');
                        } else {
                            log(`Ajax Success Response: ${JSON.stringify(json).substring(0, 200)}...`, 'ajax');
                        }
                    } catch (e) {
                        log(`Ajax Non-JSON Response:\n${text.substring(0, 500)}${text.length > 500 ? '...' : ''}`, 'warn');
                    }

                    // Return original response
                    return response;
                });
            }).catch(error => {
                log(`Ajax Network Error: ${error.message}`, 'error');
                throw error;
            });
        }

        return originalFetch.apply(this, args);
    };

    // 3. Form submission monitoring
    function setupFormMonitoring() {
        const form = document.getElementById('vehicleFormInline');
        if (!form) {
            log('Vehicle form not found', 'warn');
            return;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault(); // Prevent default form submission

            log('Vehicle form submitted', 'form');

            const formData = new FormData(form);

            // Append CSRF token only once
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', csrfToken);
            }

            try {
                const response = await fetch(window.CONFIG.API.VEHICLE_CREATE, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const result = await response.json();

                    if (result.success) {
                        log('Vehicle added successfully', 'success');
                        alert('Vehicle added successfully!'); // Display success message
                        location.reload(); // Reload the page to update the vehicle list
                    } else {
                        throw new Error(result.message || 'Failed to add vehicle');
                    }
                } else {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
            } catch (error) {
                log(`Error submitting vehicle form: ${error.message}`, 'error');
                alert(`Error: ${error.message}`); // Display error message
            }
        });
    }

    // 4. Image verification
    async function verifyVehicleImages() {
        const images = document.querySelectorAll('#vehiclesList img');
        log(`Found ${images.length} vehicle images to verify`, 'image');

        if (images.length === 0) {
            log('No vehicle images found. This is normal if vehicles haven\'t loaded yet or no vehicles exist for the current user.', 'info');
        }

        // First, check server-side file existence
        try {
            const response = await fetch(window.CONFIG.API.VEHICLE_CHECK_IMAGES, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.missing && result.missing.length > 0) {
                    result.missing.forEach(missing => {
                        log(`MISSING FILE: Vehicle ID ${missing.id}\nDB Path: ${missing.path}\nFilesystem: ${missing.filesystem_path}`, 'error');
                    });
                }
                if (result.existing && result.existing.length > 0) {
                    log(`Server check: ${result.existing.length} images exist on disk`, 'image');
                }
            } else {
                log('Failed to check image existence on server', 'warn');
            }
        } catch (error) {
            log(`Error checking image existence: ${error.message}`, 'error');
        }

        // Then check client-side loading
        images.forEach((img, index) => {
            const src = img.src;
            log(`Checking image ${index + 1}: ${src}`, 'image');

            // Create a test image to check if it loads
            const testImg = new Image();
            testImg.onload = function () {
                log(`Image ${index + 1} loaded successfully: ${src}`, 'image');
            };
            testImg.onerror = function () {
                log(`Image ${index + 1} failed to load: ${src}\nIntended path: ${img.getAttribute('data-original-src') || 'N/A'}`, 'error');
            };
            testImg.src = src;
        });
    }

    // 5. Delete button verification
    function verifyDeleteButtons() {
        const deleteButtons = document.querySelectorAll('#vehiclesList [data-action="delete"]');
        log(`Found ${deleteButtons.length} delete buttons`, 'button');

        deleteButtons.forEach((btn, index) => {
            // Add click listener for logging
            btn.addEventListener('click', function (e) {
                const vehicleCard = btn.closest('.bg-white');
                const vehicleId = vehicleCard ? vehicleCard.getAttribute('data-vehicle-id') : 'unknown';
                log(`Delete button ${index + 1} clicked\nVehicle ID: ${vehicleId}`, 'button');
            });
        });

        if (deleteButtons.length === 0) {
            log('No delete buttons found in vehicle list', 'warn');
        }
    }

    // Function to add sample car washes for display
    function addSampleCarWashes() {
        const vehiclesList = document.getElementById('vehiclesList');
        if (!vehiclesList) {
            log('Vehicle list container not found. Cannot add sample car washes.', 'warn');
            return;
        }

        const sampleCarWashes = [
            {
                id: 'sample1',
                brand: 'Toyota',
                model: 'Corolla',
                licensePlate: 'ABC-1234',
                imagePath: '/frontend/images/sample-car1.png'
            },
            {
                id: 'sample2',
                brand: 'Honda',
                model: 'Civic',
                licensePlate: 'XYZ-5678',
                imagePath: '/frontend/images/sample-car2.png'
            }
        ];

        sampleCarWashes.forEach(car => {
            const vehicleCard = document.createElement('div');
            vehicleCard.className = 'vehicle-card';
            vehicleCard.innerHTML = `
                <div class="bg-white" data-vehicle-id="${car.id}">
                    <img src="${car.imagePath}" alt="${car.brand} ${car.model}" class="vehicle-image" />
                    <div class="vehicle-details">
                        <h4>${car.brand} ${car.model}</h4>
                        <p>License Plate: ${car.licensePlate}</p>
                    </div>
                </div>
            `;
            vehiclesList.appendChild(vehicleCard);
        });

        log('Sample car washes added to the vehicle list.', 'info');
    }

    // Initialize on DOM ready
    async function init() {
        log('Vehicle Debug Helper initialized', 'init');

        setupFormMonitoring();
        await verifyVehicleImages();
        verifyDeleteButtons();

        const vehiclesList = document.getElementById('vehiclesList');
        if (vehiclesList && vehiclesList.children.length === 0) {
            addSampleCarWashes();
        }

        // Optimize MutationObserver logic
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' && mutation.target.id === 'vehiclesList') {
                    log('Vehicle list updated, re-verifying...', 'update');
                    observer.disconnect(); // Disconnect to avoid repeated triggers
                    setTimeout(async () => {
                        await verifyVehicleImages();
                        verifyDeleteButtons();
                        observer.observe(mutation.target, { childList: true, subtree: true }); // Reconnect observer
                    }, 100);
                }
            });
        });

        const vehiclesListElement = document.getElementById('vehiclesList');
        if (vehiclesListElement) {
            observer.observe(vehiclesListElement, { childList: true, subtree: true });
        }
    }

    // Run on DOMContentLoaded or immediately if already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init());
    } else {
        init();
    }

    // Expose log function globally for manual debugging
    window.vehicleDebugLog = log;
})();