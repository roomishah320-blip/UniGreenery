/**
 * Global Application Interactivity Handler
 * Step 10: Connecting All Modules & Interactivity
 */

document.addEventListener('DOMContentLoaded', function() {
    // 0. Global Page Pre-loader (Removed)

    // 1. Initialize AOS Animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    }

    // 2. Sidebar Active State & Toggle
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const sidebar = document.querySelector('.sidebar');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    
    // Create Overlay for Mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (mobileBtn) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    document.querySelectorAll('.sidebar-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
            // If in a dropdown, open the parent
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                const toggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                if (toggle) {
                    toggle.classList.remove('collapsed');
                    parentCollapse.classList.add('show');
                }
            }
        }
    });

    // 3. Global AJAX Form Handler
    const ajaxForms = document.querySelectorAll('.ajax-form');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // UI State: Loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            const formData = new FormData(form);
            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('Server returned ' + res.status);
                return res.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid server response format.');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Action completed successfully!', 'success');
                    if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1000);
                    if (data.reload) setTimeout(() => window.location.reload(), 1000);
                    // Close modal if exists
                    const modalEl = form.closest('.modal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                } else {
                    showToast(data.error || 'Something went wrong.', 'danger');
                }
            })
            .catch(err => {
                showToast(err.message || 'Network error. Please try again.', 'danger');
                console.error(err);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    });

    // 4. Global Delete Confirmation
    window.confirmDelete = function(id, module, title = 'item') {
        if (confirm(`Are you sure you want to delete this ${title}? This action cannot be undone.`)) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch(`modules/${module}-actions.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Deleted successfully.', 'success');
                    window.location.reload();
                } else {
                    showToast(data.error || 'Deletion failed.', 'danger');
                }
            });
        }
    };

    // 5. Toast Notification System
    window.showToast = function(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toastId = 'toast-' + Date.now();
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
        
        const toastHtml = `
            <div id="${toastId}" class="glass-toast ${type}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex align-items-center p-3">
                    <i class="fas ${icon} me-3"></i>
                    <div class="toast-body flex-grow-1">${message}</div>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(toastId);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // 6. Real-time Search Filter (Local tables)
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const targetTable = document.querySelector(input.dataset.target);
            const value = this.value.toLowerCase();
            const rows = targetTable.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        });
    });

    // 7. Interactive Stats Counters
    const counters = document.querySelectorAll('.counter-value');
    if (counters.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.target);
                    animateCounter(entry.target, target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(c => observer.observe(c));
    }

    function animateCounter(el, target) {
        let count = 0;
        const speed = target / 50;
        const update = () => {
            count += speed;
            if (count < target) {
                el.innerText = Math.ceil(count).toLocaleString();
                requestAnimationFrame(update);
            } else {
                el.innerText = target.toLocaleString();
            }
        };
        update();
    }

    // 8. Notifications AJAX Handler
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('api/notifications.php?action=mark_all_read')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('All notifications marked as read.', 'success');
                    const badge = document.querySelector('#notificationBellBtn .badge');
                    if (badge) badge.remove();
                    const notifItems = document.querySelectorAll('.notification-item');
                    notifItems.forEach(item => {
                        item.classList.add('opacity-75');
                        item.classList.remove('bg-white-5', 'fw-bold');
                        item.style.borderLeftColor = 'transparent';
                    });
                    markAllReadBtn.remove();
                } else {
                    showToast(data.error || 'Failed to mark notifications as read.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Network error while marking notifications as read.', 'danger');
            });
        });
    }
});
