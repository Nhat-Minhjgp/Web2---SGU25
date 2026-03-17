// ============================================
// KHỞI TẠO
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin script loaded');
    
    // Xác định trang hiện tại để active menu
    const currentPage = window.location.pathname.split('/').pop();
    highlightActiveMenu(currentPage);
});

// ============================================
// MENU FUNCTIONS
// ============================================

// Highlight active menu
function highlightActiveMenu(currentPage) {
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    let activeLink = null;
    
    if (currentPage === 'dashboard.php') {
        activeLink = document.querySelector('a[href="dashboard.php"]');
    } else if (currentPage === 'users.php') {
        activeLink = document.querySelector('a[href="users.php"]');
    } else if (currentPage === 'categories.php') {
        activeLink = document.querySelector('a[href="categories.php"]');
    } else if (currentPage === 'products.php') {
        activeLink = document.querySelector('a[href="products.php"]');
    } else if (currentPage === 'import.php') {
        activeLink = document.querySelector('a[href="import.php"]');
    } else if (currentPage === 'price.php') {
        activeLink = document.querySelector('a[href="price.php"]');
    } else if (currentPage === 'orders.php') {
        activeLink = document.querySelector('a[href="orders.php"]');
    } else if (currentPage === 'inventory.php') {
        activeLink = document.querySelector('a[href="inventory.php"]');
    }
    
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

// ============================================
// MODAL FUNCTIONS
// ============================================

let currentModal = null;

function openModal(title, content, footer = '') {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalFooter = document.getElementById('modalFooter');
    
    if (!modal) return;
    
    modalTitle.innerHTML = title;
    modalBody.innerHTML = content;
    modalFooter.innerHTML = footer;
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    setTimeout(() => {
        modal.addEventListener('click', clickOutsideToClose);
    }, 100);
}

function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        modal.removeEventListener('click', clickOutsideToClose);
    }
}

function clickOutsideToClose(event) {
    if (event.target === document.getElementById('modal')) {
        closeModal();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatMoney(amount) {
    if (!amount && amount !== 0) return '0đ';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') + 'đ';
}

function showAlert(message, type = 'success') {
    alert(message);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ============================================
// LOGOUT
// ============================================

function logout() {
    if (confirm('Bạn có chắc muốn đăng xuất?')) {
        window.location.href = 'logout.php';
    }
}