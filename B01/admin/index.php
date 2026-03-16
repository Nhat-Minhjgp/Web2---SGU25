<?php
session_start();

// Nếu đã đăng nhập thì chuyển thẳng vào dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Kiểm tra đăng nhập (nên kiểm tra từ database)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng nhập Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #111827;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .login-container {
            background-color: #1F2937;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 28rem;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            color: #06b6d4;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #d1d5db;
            margin-bottom: 0.25rem;
        }

        input {
            width: 100%;
            background-color: #374151;
            color: white;
            border: 1px solid #4B5563;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
        }

        input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.3);
        }

        input::placeholder {
            color: #9ca3af;
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .login-btn {
            width: 100%;
            background-color: #06b6d4;
            color: #111827;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .login-btn:hover {
            background-color: #0891b2;
        }
    </style>
</head>

    <div class="login-container">
        <h2>Đăng nhập Admin</h2>

        <form id="adminLoginForm">
            <div id="errorMsg" class="error-message">
                Tên tài khoản hoặc mật khẩu không chính xác.
            </div>

            <div class="form-group">
                <label for="username">Tên tài khoản</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username"
                    placeholder="Nhập tên tài khoản"
                />
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    placeholder="Nhập mật khẩu"
                />
            </div>

            <button type="submit" class="login-btn">
                Đăng nhập
            </button>
        </form>
    </div>
    <script>    
        const defaultAdmins = [
        {
          username: "admin",
          password: "admin123",
          name: "Quản trị viên",
        },
      ];

      const form = document.getElementById("adminLoginForm");
      const errorMsg = document.getElementById("errorMsg");

      form.addEventListener("submit", function (event) {
        event.preventDefault();
        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value.trim();

        const foundAdmin = defaultAdmins.find(
          (u) => u.username === username && u.password === password
        );

        if (foundAdmin) {
          localStorage.setItem("currentAdmin", JSON.stringify(foundAdmin));
          errorMsg.classList.add("hidden");
          window.location.href = "dashboard.php"; // chuyển đến giao diện quản lý
        } else {
          errorMsg.classList.remove("hidden");
        }
      });
    </script>