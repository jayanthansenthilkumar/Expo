<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SPARK'26</title>
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

    <!-- Register Section -->
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p style="color: var(--text-muted);">Register your team or join as a viewer</p>
            </div>
            <form id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-input" placeholder="John" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-input" placeholder="Doe" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">College / Institution</label>
                    <input type="text" name="college" class="form-input" placeholder="Institute of Technology" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">Select Role...</option>
                        <option value="student">Student Innovator</option>
                        <option value="advisor">Faculty / Advisor</option>
                        <option value="visitor">Visitor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Create a password" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; border: none; cursor: pointer;">Create
                    Account</button>
            </form>
            <div class="auth-footer">
                Already have an account? <a href="login.php">Login</a>
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
        $(document).ready(function() {
            $('#registerForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    type: 'POST',
                    url: 'api/register.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            window.location.href = 'login.php';
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>

</html>