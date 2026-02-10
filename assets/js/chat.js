/* Syraa AI Chat Widget Logic (Gemini/Spark UI) */
(function($) {
    $(document).ready(function() {
        const $chatToggle = $('.chat-toggle-btn');
        const $chatWindow = $('.chat-window');
        const $chatMessages = $('.chat-messages');
        const $chatInput = $('.chat-input');
        const $chatSend = $('.chat-send-btn');
        const $dataBtn = $('.chat-notification-btn');
        const $typingIndicator = $('.typing-dots');
        
        let isTyping = false;

        // Toggle chat visibility
        $chatToggle.on('click', function() {
            var $this = $(this);
            $this.toggleClass('active');
            if ($chatWindow.hasClass('open')) {
                $chatWindow.removeClass('open');
                setTimeout(() => $chatWindow.hide(), 300); 
            } else {
                $chatWindow.css('display', 'flex');
                setTimeout(() => $chatWindow.addClass('open'), 10);
                
                if (!$('.chat-message').length) {
                    appendMessage('bot', "Hello! I'm Syraa ✨. I can help you **Login**, **Register**, **Create a Team**, or check **Notifications**.");
                }
                scrollToBottom();
                
                // Auto-check notifications on open
                checkNotifications(true);
            }
        });

        // Notification Button Click
        $dataBtn.on('click', function() {
            // Send hidden check command
            checkNotifications(false);
        });

        function checkNotifications(silent) {
            sendMessage('__check_notifications__', true, silent);
        }

        // Send message handler
        function sendMessage(text, isHidden = false, isSilent = false) {
            const message = text || $chatInput.val().trim();
            if (!message) return;

            // Add user message if not hidden
            if (!isHidden) {
                appendMessage('user', message);
                $chatInput.val('');
            }
            
            // If silent, don't show typing indicator (for background checks)
            if (!isSilent) showTyping(true);

            // AJAX request
            $.ajax({
                url: 'sparkBackend.php?action=chat_query',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ message: message }),
                success: function(response) {
                    // For silent checks, only show if there's a meaningful reply (not "No new notifications" if strictly silent?)
                    // For now, let's just process normal flow.
                    if (isSilent) {
                         // Logic: if response is "You have no new notifications..." and it was an auto-check, maybe don't show it?
                         // But the backend returns that string. Let's just show it if it's NOT the default "no new" message, 
                         // or maybe just update the dot.
                         // For simplicity in this iteration, we'll process it normally but maybe skip typing delay.
                         handleResponse(response);
                    } else {
                        setTimeout(() => {
                            showTyping(false);
                            handleResponse(response);
                        }, 600);
                    }
                },
                error: function() {
                    if (!isSilent) {
                        showTyping(false);
                        appendMessage('bot', "Sorry, I'm having trouble connecting right now. 😔");
                    }
                }
            });
        }

        // Handle Backend Response
        function handleResponse(response) {
            // 1. Reply
            if (response.reply) {
                // Check if it's the "No new notifications" message and we want to supress it? 
                // We'll just show it for clarity.
                appendMessage('bot', response.reply);
            }

            // 2. Options
            if (response.options && Array.isArray(response.options)) {
                renderOptions(response.options);
            }

            // 3. Input Type
            if (response.input_type === 'password') {
                $chatInput.attr('type', 'password').attr('placeholder', 'Enter password...');
            } else {
                $chatInput.attr('type', 'text').attr('placeholder', 'Type a message...');
            }

            // 4. Actions
            if (response.action) {
                if (response.action === 'highlight_register') {
                    // ... existing logic
                } 
                else if (response.action === 'reload') {
                    setTimeout(() => location.reload(), 1500);
                }
                else if (response.action === 'scroll_schedule') {
                     scrollToSection('#schedule');
                }
                else if (response.action === 'scroll_tracks') {
                     scrollToSection('#tracks');
                }
            }
        }

        // Event Listeners
        $chatSend.on('click', function() { sendMessage(); });
        $chatInput.on('keypress', function(e) {
            if (e.which === 13) sendMessage();
        });

        // Option Click Delegate
        $(document).on('click', '.chat-option-btn', function() {
            const val = $(this).text();
            $(this).parent().fadeOut(200, function(){ $(this).remove(); }); // Remove options after click
            sendMessage(val);
        });

        // Helper: Append message
        function appendMessage(sender, text) {
            const formattedText = text
                .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
                .replace(/\n/g, '<br>');

            const $msgDiv = $('<div>')
                .addClass('chat-message ' + sender)
                .html(formattedText);
            
            $msgDiv.insertBefore($typingIndicator.parent());
            scrollToBottom();
        }

        // Helper: Render Options
        function renderOptions(options) {
            const $optDiv = $('<div>').addClass('chat-options');
            
            options.forEach(opt => {
                let cls = 'chat-option-btn';
                if (opt.toLowerCase() === 'accept') cls += ' accept';
                if (opt.toLowerCase() === 'decline') cls += ' decline';
                
                $('<button>')
                    .addClass(cls)
                    .text(opt)
                    .appendTo($optDiv);
            });
            
            $optDiv.insertBefore($typingIndicator.parent());
            scrollToBottom();
        }

        function showTyping(show) {
            if (show) {
                $typingIndicator.show().css('display', 'inline-flex');
            } else {
                $typingIndicator.hide();
            }
            scrollToBottom();
        }

        function scrollToBottom() {
            $chatMessages.animate({ scrollTop: $chatMessages[0].scrollHeight }, 300);
        }

        function scrollToSection(selector) {
            if ($(selector).length) {
                $('html, body').animate({
                    scrollTop: $(selector).offset().top - 80
                }, 800);
            }
        }
    });
})(jQuery);
