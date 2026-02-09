<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    $redirectUrl = 'studentDashboard.php'; // Default
    switch ($role) {
        case 'student':
            $redirectUrl = 'studentDashboard.php';
            break;
        case 'studentaffairs':
            $redirectUrl = 'studentAffairs.php';
            break;
        case 'departmentcoordinator':
            $redirectUrl = 'departmentCoordinator.php';
            break;
        case 'admin':
            $redirectUrl = 'sparkAdmin.php';
            break;
    }
    header("Location: $redirectUrl");
    exit();
}

$error = '';
$success = '';

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Register Form -->
    <div class="auth-container">
        <div class="auth-grid-split">
            <div class="auth-info-side">
                <a href="index.php" class="btn-back-home"><i class="ri-arrow-left-line"></i> Back to Home</a>
                <h1>JOIN SPARK <span>'26</span></h1>
                <p>Register to Showcase Your Innovation</p>
            </div>
            <div class="auth-form-side">
                <div class="auth-card register-card">
                    <div class="auth-header">
                        <h2>Create Account</h2>
                        <p>Join SPARK'26 Innovation Showcase</p>
                    </div>

                    <form id="registerForm" method="POST" action="sparkBackend.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="form-input" placeholder="John Doe"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-input"
                                    placeholder="johndoe" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="department" class="form-label">Department</label>
                                <select id="department" name="department" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <option value="AIDS">AIDS</option>
                                    <option value="AIML">AIML</option>
                                    <option value="CSE">CSE</option>
                                    <option value="CSBS">CSBS</option>
                                    <option value="CYBER">CYBER</option>
                                    <option value="ECE">ECE</option>
                                    <option value="EEE">EEE</option>
                                    <option value="MECH">MECH</option>
                                    <option value="CIVIL">CIVIL</option>
                                    <option value="IT">IT</option>
                                    <option value="VLSI">VLSI</option>
                                    <option value="MBA">MBA</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="year" class="form-label">Year</label>
                                <select id="year" name="year" class="form-select" required>
                                    <option value="">Select Year</option>
                                    <option value="I year">I Year</option>
                                    <option value="II year">II Year</option>
                                    <option value="III year">III Year</option>
                                    <option value="IV year">IV Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="rollNumber" class="form-label">Register Number</label>
                                <input type="text" id="rollNumber" name="reg_no" class="form-input"
                                    placeholder="Select department & year first" maxlength="12" required>
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input"
                                    placeholder="john@example.com" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-input"
                                placeholder="Create a password" required>
                        </div>

                        <button type="submit" name="register" class="btn-submit">
                            <i class="ri-user-add-line"></i> Create Account
                        </button>
                    </form>

                    <div class="auth-footer">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show error/success messages
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: '<?php echo addslashes($error); ?>',
                confirmButtonColor: '#2563eb'
            });
        <?php endif; ?>

        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($success); ?>',
                confirmButtonColor: '#2563eb'
            }).then(() => {
                window.location.href = 'login.php';
            });
        <?php endif; ?>

        // Auto-fill roll number logic
        const departmentSelect = document.getElementById('department');
        const yearSelect = document.getElementById('year');
        const rollNumberInput = document.getElementById('rollNumber');

        let currentFixedPrefix = '';
        let isRollPrefixLocked = false;

        const deptCodes = {
            'AIDS': 'BAD',
            'AIML': 'BAM',
            'CSE': 'BCS',
            'CSBS': 'BCB',
            'CYBER': 'BSC',
            'ECE': 'BEC',
            'EEE': 'BEE',
            'MECH': 'BME',
            'CIVIL': 'BCE',
            'IT': 'BIT',
            'VLSI': 'BEV',
            'MBA': 'MBA',
            'MCA': 'MCA'
        };

        const yearCodes = {
            'I year': '927625',
            'II year': '927624',
            'III year': '927623',
            'IV year': '927622'
        };

        // Enforce the prefix if locked
        rollNumberInput.addEventListener('input', function () {
            if (isRollPrefixLocked && currentFixedPrefix) {
                if (!this.value.startsWith(currentFixedPrefix)) {
                    this.value = currentFixedPrefix;
                }
            }
        });

        // Prevent deleting the prefix via backspace for better UX
        rollNumberInput.addEventListener('keydown', function (e) {
            if (isRollPrefixLocked && currentFixedPrefix) {
                if (this.selectionStart <= currentFixedPrefix.length && e.key === 'Backspace') {
                    e.preventDefault();
                }
            }
        });

        function checkAutoFillRollNumber() {
            const dept = departmentSelect.value;

            // Logic for CYBER department: Only I Year allowed
            if (dept === 'CYBER') {
                // Hide all options except I Year
                Array.from(yearSelect.options).forEach(opt => {
                    if (opt.value === 'I year' || opt.value === '') {
                        opt.style.display = 'block';
                        opt.disabled = false;
                    } else {
                        opt.style.display = 'none';
                        opt.disabled = true;
                    }
                });

                // If currently selected year is hidden/disabled, reset to I year
                if (yearSelect.value && yearSelect.value !== 'I year') {
                    yearSelect.value = 'I year';
                }
            } else {
                // Reset: Show all options
                Array.from(yearSelect.options).forEach(opt => {
                    opt.style.display = 'block';
                    opt.disabled = false;
                });
            }

            const year = yearSelect.value;
            let prefix = '';

            // Check if both valid
            if (dept && year && yearCodes[year]) {
                const yCode = yearCodes[year];
                let dCode = deptCodes[dept] || '';

                // SPECIAL CASE: For AIML only if year == IV means prefix is 927622BAL
                if (dept === 'AIML' && year === 'IV year') {
                    // Override dCode logic
                    // Standard AIML is BAM, but IV Year is BAL
                    dCode = 'BAL';
                }

                if (dCode) {
                    prefix = yCode + dCode;
                }
            }

            if (prefix) {
                // Start locking
                if (currentFixedPrefix !== prefix) {
                    // If prefix changed (or started), update input
                    rollNumberInput.value = prefix;
                } else if (!rollNumberInput.value.startsWith(prefix)) {
                    // If same prefix but user cleared it
                    rollNumberInput.value = prefix;
                }

                currentFixedPrefix = prefix;
                isRollPrefixLocked = true;
            } else {
                // No valid combination (maybe valid year/dept but no code defined)
                isRollPrefixLocked = false;
                currentFixedPrefix = '';
            }
        }

        departmentSelect.addEventListener('change', checkAutoFillRollNumber);
        yearSelect.addEventListener('change', checkAutoFillRollNumber);

        // Real-time Roll Number Validation
        let rollTimer;
        const rollDoneTypingInterval = 500;

        function setRollFeedback(status, input) {
            input.classList.remove('valid', 'invalid');
            if (status === 'valid') {
                input.classList.add('valid');
            } else if (status === 'invalid') {
                input.classList.add('invalid');
            }
        }

        rollNumberInput.addEventListener('keyup', function () {
            clearTimeout(rollTimer);
            const val = this.value;
            if (!val) {
                setRollFeedback('', this);
                return;
            }

            rollTimer = setTimeout(() => {
                if (val.length === 12) {
                    setRollFeedback('valid', this);
                } else {
                    setRollFeedback('invalid', this);
                }
            }, rollDoneTypingInterval);
        });

        // Form validation before submit
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const rollNumber = rollNumberInput.value;
            if (rollNumber.length !== 12) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Register Number',
                    text: 'Register number must be exactly 12 characters',
                    confirmButtonColor: '#2563eb'
                });
            }
        });
    </script>
</body>

</html>