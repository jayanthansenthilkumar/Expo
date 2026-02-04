<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--bg-surface) 0%, white 100%);
        }
        .auth-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 450px;
            border: 1px solid var(--border);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-family: inherit;
            transition: all 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: var(--primary);
            font-weight: 600;
        }
    </style>
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
            <form action="studentDashboard.php"> <!-- For demo purposes linking to dashboard -->
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-input" placeholder="student@college.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-input" placeholder="••••••••" required>
                </div>
                <div class="form-group" style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox"> Remember me
                    </label>
                    <a href="#" style="color: var(--primary);">Forgot Password?</a>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; border: none; cursor: pointer;">Login</button>
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

</body>
</html>
