<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

</head>

<body>

    <!-- Navbar (Simple) -->
    <nav class="navbar" style="position: relative;">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                SPARK <span>'26</span>
            </a>
            <a href="index.php" class="btn-outline">Back to Home</a>
        </div>
    </nav>

    <!-- Login Section -->
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p style="color: var(--text-muted);">Login to manage your projects</p>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="student@college.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                <div class="form-group" style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox"> Remember me
                    </label>
                    <a href="#" style="color: var(--primary);">Forgot Password?</a>
                </div>
                <button type="submit" class="btn-primary"
                    style="width: 100%; border: none; cursor: pointer;">Login</button>
            </form>
            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center" style="font-size: 0.9rem; color: #64748b;">
                &copy; 2026 College Innovation Council. All rights reserved.
            </div>
        </div>
    </footer>


    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#loginForm').on('submit', function (e) {
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: 'api/login.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function () {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>

</html>