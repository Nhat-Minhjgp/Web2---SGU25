<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  </head>
  <body class="bg-gray-900 text-white min-h-screen overflow-hidden">
    <!-- HEADER -->
    <header
      class="flex justify-between items-center px-6 py-4 bg-gray-800 shadow-md sticky top-0 z-50"
    >
      <h1 class="text-2xl font-bold text-cyan-400">Admin Panel - Xin chào quản trị viên</h1>
      <div class="flex items-center space-x-4">
        <!-- Thông tin tài khoản admin -->
        <div class="flex items-center space-x-3 bg-gray-700 px-4 py-2 rounded-lg">
          <div class="w-10 h-10 rounded-full bg-cyan-500 flex items-center justify-center">
            <i class="fas fa-user text-white"></i>
          </div>
          <div>
            <p class="font-semibold text-sm" id="adminName">MASH</p>
            <p class="text-xs text-gray-300" id="adminRole">Quản trị viên</p>
          </div>
        </div>
        <button
          onclick="logout()"
          class="bg-cyan-500 hover:bg-cyan-600 text-gray-900 font-semibold px-4 py-2 rounded-full text-sm transition"
        >
          Đăng xuất
        </button>
      </div>
    </header>

    <div class="flex h-[calc(100vh-64px)]">
      <!-- SIDEBAR -->
      <aside class="w-80 bg-gray-800 p-6 space-y-4 overflow-y-auto">
        <div class="pb-4 border-b border-gray-700">
          <h3 class="text-lg font-semibold text-cyan-400 mb-2">Thông tin tài khoản</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-400">Tên đăng nhập:</span>
              <span id="adminEmail">quanly1</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-400">Đăng nhập lúc:</span>
              <span id="loginTime">10:30, 15/06/2023</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-400">Trạng thái:</span>
              <span class="text-green-400">Đang hoạt động</span>
            </div>
          </div>
        </div>
        
        <nav class="space-y-2 pt-2">
         

          <button
            class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700"
          >
            <i class="fas fa-users mr-2"></i> Quản lý người dùng
          </button>
          <button
            class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700 transition"
          >
            <i class="fas fa-tags mr-2"></i> Loại sản phẩm
          </button>
         
          <button
            class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700 transition"
          >
            <i class="fas fa-boxes mr-2"></i>  Danh mục sản phẩm
          </button>
          <button
          class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700 transition"
        >
          <i class="fas fa-arrow-down mr-2"></i> Quản lý hàng nhập
        </button>
          <button
            class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700 transition"
          >
            <i class="fas fa-receipt mr-2"></i> Quản lý đơn đặt hàng
          </button>
          <button
            class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700 transition"
          >
            <i class="fas fa-tag mr-2"></i> Giá bán
          </button>
          
          <button
            class="block w-full text-left px-4 py-2 rounded-lg hover:text-cyan-400 hover:bg-gray-700 transition"
          >
            <i class="fas fa-warehouse mr-2"></i> Tồn kho
          </button>
        </nav>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="flex-1 p-8 overflow-y-auto">
        <section id="content" class="bg-gray-800 p-6 rounded-xl shadow-lg">
          <h2 class="text-2xl font-bold text-cyan-400 mb-2">
            Chào mừng đến với hệ thống quản lý
          </h2>
          <p class="text-gray-300">
            Chọn chức năng từ menu bên trái để bắt đầu quản lý.
          </p>
          
          <!-- Thông tin tài khoản chi tiết -->
          <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-700 p-6 rounded-lg">
              <h3 class="text-xl font-semibold text-cyan-400 mb-4">Thông tin tài khoản</h3>
              <div class="space-y-3">
                <div class="flex items-center">
                  <i class="fas fa-user text-cyan-400 w-6"></i>
                  <span class="ml-2">Họ tên: <span id="detailAdminName">Quán lý 1</span></span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-envelope text-cyan-400 w-6"></i>
                  <span class="ml-2">Tên tài khoản: <span id="detailAdminEmail">quanly1</span></span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-user-tag text-cyan-400 w-6"></i>
                  <span class="ml-2">Vai trò: <span id="detailAdminRole">Quản trị viên</span></span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-calendar text-cyan-400 w-6"></i>
                  <span class="ml-2">Ngày tạo: <span id="accountCreated">01/01/2023</span></span>
                </div>
              </div>
            </div>
            
            <div class="bg-gray-700 p-6 rounded-lg">
              <h3 class="text-xl font-semibold text-cyan-400 mb-4">Hoạt động gần đây</h3>
              <div class="space-y-3">
                <div class="flex items-start">
                  <i class="fas fa-check-circle text-green-400 mt-1 mr-2"></i>
                  <div>
                    <p class="font-medium">Đăng nhập thành công</p>
                    <p class="text-sm text-gray-400">10:30, 15/06/2023</p>
                  </div>
                </div>
                <div class="flex items-start">
                  <i class="fas fa-edit text-blue-400 mt-1 mr-2"></i>
                  <div>
                    <p class="font-medium">Cập nhật sản phẩm</p>
                    <p class="text-sm text-gray-400">09:15, 15/06/2023</p>
                  </div>
                </div>
                <div class="flex items-start">
                  <i class="fas fa-plus-circle text-green-400 mt-1 mr-2"></i>
                  <div>
                    <p class="font-medium">Thêm khách hàng mới</p>
                    <p class="text-sm text-gray-400">14:20, 14/06/2023</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- SCRIPT -->
    <script>
      // Dữ liệu mẫu cho tài khoản admin
      const adminData = {
        name: " Quản Lý 1",
        email: "quanly1",
        role: "Quản trị viên",
        createdDate: "01/01/2023",
        loginTime: new Date().toLocaleString('vi-VN')
      };

      // Hàm cập nhật thông tin admin lên giao diện
      function updateAdminInfo() {
        document.getElementById('adminName').textContent = adminData.name;
        document.getElementById('adminRole').textContent = adminData.role;
        document.getElementById('adminEmail').textContent = adminData.email;
        document.getElementById('loginTime').textContent = adminData.loginTime;
        
        document.getElementById('detailAdminName').textContent = adminData.name;
        document.getElementById('detailAdminEmail').textContent = adminData.email;
        document.getElementById('detailAdminRole').textContent = adminData.role;
        document.getElementById('accountCreated').textContent = adminData.createdDate;
      }

      // Gọi hàm khi trang tải
      document.addEventListener('DOMContentLoaded', updateAdminInfo);

      function loadPage(page) {
        const content = document.getElementById("content");
        let title = "";
        let description = "";

        switch (page) {
          
          default:
            title = "Trang chủ";
            description = "Chọn chức năng từ menu bên trái để bắt đầu quản lý.";
        }

        content.innerHTML = `
        <h2 class="text-2xl font-bold text-cyan-400 mb-2">${title}</h2>
        <p class="text-gray-300">${description}</p>
        
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-gray-700 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-cyan-400 mb-4">Thông tin tài khoản</h3>
            <div class="space-y-3">
              <div class="flex items-center">
                <i class="fas fa-user text-cyan-400 w-6"></i>
                <span class="ml-2">Họ tên: <span id="detailAdminName">${adminData.name}</span></span>
              </div>
              <div class="flex items-center">
                <i class="fas fa-envelope text-cyan-400 w-6"></i>
                <span class="ml-2">Email: <span id="detailAdminEmail">${adminData.email}</span></span>
              </div>
              <div class="flex items-center">
                <i class="fas fa-user-tag text-cyan-400 w-6"></i>
                <span class="ml-2">Vai trò: <span id="detailAdminRole">${adminData.role}</span></span>
              </div>
              <div class="flex items-center">
                <i class="fas fa-calendar text-cyan-400 w-6"></i>
                <span class="ml-2">Ngày tạo: <span id="accountCreated">${adminData.createdDate}</span></span>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-700 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-cyan-400 mb-4">Hoạt động gần đây</h3>
            <div class="space-y-3">
              <div class="flex items-start">
                <i class="fas fa-check-circle text-green-400 mt-1 mr-2"></i>
                <div>
                  <p class="font-medium">Đăng nhập thành công</p>
                  <p class="text-sm text-gray-400">${adminData.loginTime}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="fas fa-edit text-blue-400 mt-1 mr-2"></i>
                <div>
                  <p class="font-medium">Cập nhật sản phẩm</p>
                  <p class="text-sm text-gray-400">09:15, 15/06/2023</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="fas fa-plus-circle text-green-400 mt-1 mr-2"></i>
                <div>
                  <p class="font-medium">Thêm khách hàng mới</p>
                  <p class="text-sm text-gray-400">14:20, 14/06/2023</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
      }

      function logout() {
        if (confirm("Bạn có chắc chắn muốn đăng xuất?")) {
          alert("Đã đăng xuất!");
          window.location.href = "index.php";
        }
      }
    </script>
  </body>
</html>