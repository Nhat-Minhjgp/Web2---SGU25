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

// Toggle submenu
function toggleSubmenu(id, button) {
    const submenu = document.getElementById(id);
    const arrow = button.querySelector('.arrow-icon');
    
    if (submenu) {
        submenu.classList.toggle('show');
        arrow.classList.toggle('rotated');
    }
}

// Highlight active menu dựa trên trang hiện tại
function highlightActiveMenu(currentPage) {
    // Bỏ active tất cả menu
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Active menu dựa vào trang
    let activeLink = null;
    
    if (currentPage === 'dashboard.php' || currentPage === 'index.html') {
        activeLink = document.querySelector('a[href="dashboard.php"]');
    } else if (currentPage === 'users.php') {
        activeLink = document.querySelector('a[href="users.php"]');
    } else if (currentPage === 'categories.php') {
        activeLink = document.querySelector('a[href="categories.php"]');
    } else if (currentPage.includes('products')) {
        activeLink = document.querySelector('[onclick="toggleSubmenu(\'productSubmenu\', this)"]');
        // Mở submenu sản phẩm
        document.getElementById('productSubmenu')?.classList.add('show');
        const productArrow = document.querySelector('[onclick="toggleSubmenu(\'productSubmenu\', this)"] .arrow-icon');
        if (productArrow) productArrow.classList.add('rotated');
    } else if (currentPage.includes('import')) {
        activeLink = document.querySelector('[onclick="toggleSubmenu(\'importSubmenu\', this)"]');
        document.getElementById('importSubmenu')?.classList.add('show');
        const importArrow = document.querySelector('[onclick="toggleSubmenu(\'importSubmenu\', this)"] .arrow-icon');
        if (importArrow) importArrow.classList.add('rotated');
    } else if (currentPage.includes('order')) {
        activeLink = document.querySelector('[onclick="toggleSubmenu(\'orderSubmenu\', this)"]');
        document.getElementById('orderSubmenu')?.classList.add('show');
        const orderArrow = document.querySelector('[onclick="toggleSubmenu(\'orderSubmenu\', this)"] .arrow-icon');
        if (orderArrow) orderArrow.classList.add('rotated');
    } else if (currentPage.includes('price')) {
        activeLink = document.querySelector('[onclick="toggleSubmenu(\'priceSubmenu\', this)"]');
        document.getElementById('priceSubmenu')?.classList.add('show');
        const priceArrow = document.querySelector('[onclick="toggleSubmenu(\'priceSubmenu\', this)"] .arrow-icon');
        if (priceArrow) priceArrow.classList.add('rotated');
    } else if (currentPage.includes('report') || currentPage.includes('inventory')) {
        activeLink = document.querySelector('[onclick="toggleSubmenu(\'reportSubmenu\', this)"]');
        document.getElementById('reportSubmenu')?.classList.add('show');
        const reportArrow = document.querySelector('[onclick="toggleSubmenu(\'reportSubmenu\', this)"] .arrow-icon');
        if (reportArrow) reportArrow.classList.add('rotated');
    }
    
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

// ============================================
// MODAL FUNCTIONS
// ============================================

// Mở modal
function openModal(title, content, footer = '') {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalFooter = document.getElementById('modalFooter');
    
    if (!modal) {
        alert('Không tìm thấy modal');
        return;
    }
    
    if (modalTitle) modalTitle.innerHTML = title;
    if (modalBody) modalBody.innerHTML = content;
    if (modalFooter) modalFooter.innerHTML = footer;
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Thêm sự kiện click outside
    setTimeout(() => {
        modal.addEventListener('click', clickOutsideToClose);
    }, 100);
}

// Đóng modal
function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        modal.removeEventListener('click', clickOutsideToClose);
    }
}

// Click outside để đóng
function clickOutsideToClose(event) {
    if (event.target === document.getElementById('modal')) {
        closeModal();
    }
}

// Đóng bằng phím ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Format tiền
function formatMoney(amount) {
    if (!amount) return '0đ';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') + 'đ';
}

// Hiển thị thông báo
function showAlert(message, type = 'success') {
    alert(message); // Tạm thời dùng alert
}

// Xác nhận hành động
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