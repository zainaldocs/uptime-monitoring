/**
 * UUMS Global Javascript Utility Helpers
 */

/**
 * Escapes HTML characters to prevent XSS.
 * 
 * @param {string} str 
 * @returns {string}
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/**
 * Displays a global system notification banner.
 * 
 * @param {string} message 
 * @param {string} type 'success' | 'danger'
 */
function showAlert(message, type = 'success') {
    const alert = document.getElementById('alertContainer');
    if (!alert) {
        // Fallback to standard alert if no container exists
        window.alert(message);
        return;
    }
    
    alert.classList.remove('hidden', 'bg-green-50', 'bg-red-50', 'text-green-600', 'text-red-600', 'border-green-200', 'border-red-200', 'dark:bg-green-950/30', 'dark:bg-red-950/30', 'dark:border-green-900/50', 'dark:border-red-900/50');
    
    if (type === 'success') {
        alert.classList.add('bg-green-50', 'text-green-600', 'border-green-200', 'dark:bg-green-950/30', 'dark:border-green-900/50');
        alert.innerHTML = `<i class="fa-solid fa-circle-check text-base"></i> <span>${message}</span>`;
    } else {
        alert.classList.add('bg-red-50', 'text-red-600', 'border-red-200', 'dark:bg-red-950/30', 'dark:border-red-900/50');
        alert.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-base"></i> <span>${message}</span>`;
    }
    
    alert.classList.remove('hidden');
    
    // Smooth scroll to top to ensure user sees the alert
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Hide after 5 seconds
    setTimeout(() => {
        alert.classList.add('hidden');
    }, 5000);
}

/**
 * Renders pagination controls HTML dynamically.
 * 
 * @param {number} totalItems 
 * @param {number} totalPages 
 * @param {number} currentPage 
 * @param {number} itemsPerPage 
 * @param {string} containerId 
 * @param {function} changePageCallback 
 * @param {string} itemLabel 
 */
function renderPagination(totalItems, totalPages, currentPage, itemsPerPage, containerId, changePageCallback, itemLabel = 'data') {
    const pagination = document.getElementById(containerId);
    if (!pagination) return;
    
    if (totalItems === 0 || totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    const startRange = (currentPage - 1) * itemsPerPage + 1;
    const endRange = Math.min(currentPage * itemsPerPage, totalItems);
    
    let buttons = '';
    
    // Prev Button
    buttons += `
        <button onclick="${changePageCallback.name}(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} 
                class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none transition-colors">
            Sebelumnya
        </button>
    `;
    
    // Page Numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            buttons += `
                <button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white shadow-md shadow-blue-500/20">
                    ${i}
                </button>
            `;
        } else {
            buttons += `
                <button onclick="${changePageCallback.name}(${i})" 
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    ${i}
                </button>
            `;
        }
    }
    
    // Next Button
    buttons += `
        <button onclick="${changePageCallback.name}(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} 
                class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none transition-colors">
            Selanjutnya
        </button>
    `;
    
    pagination.innerHTML = `
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Menampilkan <span class="font-bold text-gray-700 dark:text-gray-300">${startRange}-${endRange}</span> dari <span class="font-bold text-gray-700 dark:text-gray-300">${totalItems}</span> ${itemLabel}
        </div>
        <div class="flex items-center gap-1">
            ${buttons}
        </div>
    `;
}
