<?php
// includes/bot.php
$chatUserRole = $_SESSION['role'] ?? '';
$chatUserName = $_SESSION['name'] ?? '';
$chatIsLoggedIn = isset($_SESSION['user_id']) ? 'true' : 'false';
?>
<div class="chat-widget-container" data-role="<?php echo htmlspecialchars($chatUserRole); ?>" data-logged-in="<?php echo $chatIsLoggedIn; ?>" data-user="<?php echo htmlspecialchars($chatUserName); ?>">
    <button class="chat-toggle-btn" aria-label="Open chat">
        <i class="ri-chat-smile-3-line chat-icon-open"></i>
        <i class="ri-close-line chat-icon-close"></i>
    </button>
    <div class="chat-window">
        <div class="chat-header">
            <div class="chat-avatar">
                <i class="ri-robot-2-line"></i>
            </div>
            <div class="chat-info">
                <h3>Syraa AI</h3>
                <span class="chat-status"><span class="status-dot"></span> Online</span>
            </div>
            <div class="chat-controls">
                <button class="chat-notification-btn" title="Notifications">
                    <i class="ri-notification-3-line"></i>
                    <span class="notification-dot"></span>
                </button>
                <button class="close-chat" aria-label="Close chat">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </div>
        <div class="chat-messages">
            <!-- Messages injected by JS -->
            <div class="typing-indicator" style="display:none;">
                <div class="chat-message bot typing-bubble">
                    <div class="typing-dots">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" placeholder="Type a message..." autocomplete="off">
            <button class="chat-send-btn" aria-label="Send message">
                <i class="ri-send-plane-fill"></i>
            </button>
        </div>
    </div>
</div>

<!-- Scripts for Chat -->
<!-- Check if jQuery is already loaded to avoid double loading if possible, but for simplicity we include it. 
     If the main page already includes it, this might be redundant but usually harmless if version matches. 
     However, to be safe, we will rely on this include for jQuery if it's missing in other pages. -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/chat.js"></script>
