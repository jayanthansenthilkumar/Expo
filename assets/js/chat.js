/* Syraa AI Chat Widget — Real-time Chat Feel */
(function ($) {
    $(document).ready(function () {
        const $chatToggle = $('.chat-toggle-btn');
        const $chatWindow = $('.chat-window');
        const $chatMessages = $('.chat-messages');
        const $chatInput = $('.chat-input');
        const $chatSend = $('.chat-send-btn');
        const $dataBtn = $('.chat-notification-btn');
        const $closeBtn = $('.close-chat');
        const $typingIndicator = $('.typing-indicator');

        let chatOpened = false;
        let isProcessing = false;

        // ── Helpers ──────────────────────────────────────

        function getTimestamp() {
            const now = new Date();
            let h = now.getHours();
            const m = String(now.getMinutes()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return h + ':' + m + ' ' + ampm;
        }

        function scrollToBottom(instant) {
            const el = $chatMessages[0];
            if (instant) {
                el.scrollTop = el.scrollHeight;
            } else {
                el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
            }
        }

        function formatText(text) {
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
        }

        // ── Open / Close ─────────────────────────────────

        function openChat() {
            $chatToggle.addClass('active');
            $chatWindow.addClass('open');

            if (!chatOpened) {
                chatOpened = true;
                // Staggered welcome
                setTimeout(function () {
                    appendBotMessage("Hey there! I'm **Syraa** ✨ — your SPARK'26 assistant.");
                    setTimeout(function () {
                        showSuggestions();
                    }, 400);
                }, 300);

                // Silent notification check
                checkNotifications(true);
            }
            setTimeout(function () {
                $chatInput.focus();
                scrollToBottom(true);
            }, 350);
        }

        function closeChat() {
            $chatToggle.removeClass('active');
            $chatWindow.removeClass('open');
        }

        $chatToggle.on('click', function () {
            if ($chatWindow.hasClass('open')) {
                closeChat();
            } else {
                openChat();
            }
        });

        $closeBtn.on('click', function () {
            closeChat();
        });

        // ── Quick Suggestions ────────────────────────────

        function showSuggestions() {
            var suggestions = [
                { icon: 'ri-user-add-line', text: 'Register' },
                { icon: 'ri-login-box-line', text: 'Login' },
                { icon: 'ri-team-line', text: 'Create Team' },
                { icon: 'ri-calendar-line', text: 'Schedule' }
            ];

            var $wrap = $('<div>').addClass('chat-suggestions');
            suggestions.forEach(function (s) {
                $('<button>')
                    .addClass('chat-suggestion-btn')
                    .html('<i class="' + s.icon + '"></i> ' + s.text)
                    .appendTo($wrap);
            });

            $wrap.insertBefore($typingIndicator);
            scrollToBottom();
        }

        // ── Notification check ───────────────────────────

        $dataBtn.on('click', function () {
            checkNotifications(false);
        });

        function checkNotifications(silent) {
            sendMessage('__check_notifications__', true, silent);
        }

        // ── Append Messages ──────────────────────────────

        function appendUserMessage(text) {
            var $wrapper = $('<div>').addClass('chat-msg-wrapper user-wrapper');
            var $bubble = $('<div>').addClass('chat-message user').html(formatText(text));
            var $time = $('<span>').addClass('chat-timestamp').text(getTimestamp());
            $wrapper.append($bubble).append($time);
            $wrapper.insertBefore($typingIndicator);
            scrollToBottom();
        }

        function appendBotMessage(text, callback) {
            var $wrapper = $('<div>').addClass('chat-msg-wrapper bot-wrapper');
            var $bubble = $('<div>').addClass('chat-message bot');
            var $time = $('<span>').addClass('chat-timestamp').text(getTimestamp());
            $wrapper.append($bubble).append($time);
            $wrapper.insertBefore($typingIndicator);

            // Streaming word-by-word effect
            var formatted = formatText(text);
            streamText($bubble, formatted, function () {
                scrollToBottom();
                if (callback) callback();
            });
        }

        // ── Streaming / Typing Effect ────────────────────

        function streamText($el, html, callback) {
            // Parse the HTML into tokens: tags stay intact, text is split by words
            var tokens = [];
            var tagRegex = /(<[^>]+>)/g;
            var parts = html.split(tagRegex);

            parts.forEach(function (part) {
                if (part.match(tagRegex)) {
                    tokens.push({ type: 'tag', value: part });
                } else {
                    // Split text into words (keep spaces)
                    var words = part.split(/(\s+)/);
                    words.forEach(function (w) {
                        if (w.length > 0) {
                            tokens.push({ type: 'word', value: w });
                        }
                    });
                }
            });

            var i = 0;
            var buffer = '';
            var speed = 25; // ms per word — fast but visible

            function nextToken() {
                if (i >= tokens.length) {
                    $el.html(buffer);
                    if (callback) callback();
                    return;
                }
                var token = tokens[i++];
                buffer += token.value;
                $el.html(buffer);

                if (token.type === 'tag') {
                    // Tags render instantly, proceed to next
                    nextToken();
                } else {
                    scrollToBottom();
                    setTimeout(nextToken, speed);
                }
            }

            nextToken();
        }

        // ── Show / Hide Typing Indicator ─────────────────

        function showTyping() {
            $typingIndicator.show();
            scrollToBottom();
        }

        function hideTyping() {
            $typingIndicator.hide();
        }

        // ── Render Options ───────────────────────────────

        function renderOptions(options) {
            var $optDiv = $('<div>').addClass('chat-options');

            options.forEach(function (opt) {
                var cls = 'chat-option-btn';
                if (opt.toLowerCase() === 'accept') cls += ' accept';
                if (opt.toLowerCase() === 'decline') cls += ' decline';

                $('<button>')
                    .addClass(cls)
                    .text(opt)
                    .appendTo($optDiv);
            });

            $optDiv.insertBefore($typingIndicator);
            scrollToBottom();
        }

        // ── Send Message ─────────────────────────────────

        function sendMessage(text, isHidden, isSilent) {
            var message = text || $chatInput.val().trim();
            if (!message || isProcessing) return;

            // Remove suggestions on first real message
            $('.chat-suggestions').remove();

            if (!isHidden) {
                appendUserMessage(message);
                $chatInput.val('');
                isProcessing = true;
                $chatSend.css('opacity', '0.6');
            }

            if (!isSilent) {
                // Small delay before showing typing (feels natural)
                setTimeout(showTyping, 200);
            }

            $.ajax({
                url: 'sparkBackend.php?action=chat_query',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ message: message }),
                success: function (response) {
                    if (isSilent) {
                        // For silent checks, only show if meaningful
                        if (response.reply && response.reply.indexOf('no new') === -1 && response.reply.indexOf('No new') === -1) {
                            handleResponse(response);
                        }
                        // Show notification dot if there's an invitation
                        if (response.options && response.options.length) {
                            $('.notification-dot').show();
                        }
                        return;
                    }

                    // Simulate realistic bot "thinking" delay (700-1200ms)
                    var thinkDelay = 600 + Math.random() * 500;
                    setTimeout(function () {
                        hideTyping();
                        handleResponse(response);
                        isProcessing = false;
                        $chatSend.css('opacity', '1');
                    }, thinkDelay);
                },
                error: function () {
                    hideTyping();
                    isProcessing = false;
                    $chatSend.css('opacity', '1');
                    if (!isSilent) {
                        appendBotMessage("Oops! Something went wrong. Please try again. 😔");
                    }
                }
            });
        }

        // ── Handle Response ──────────────────────────────

        function handleResponse(response) {
            if (response.reply) {
                appendBotMessage(response.reply, function () {
                    // After message streams in, render options if any
                    if (response.options && Array.isArray(response.options)) {
                        renderOptions(response.options);
                    }
                });
            } else if (response.options && Array.isArray(response.options)) {
                renderOptions(response.options);
            }

            // Input type
            if (response.input_type === 'password') {
                $chatInput.attr('type', 'password').attr('placeholder', '\u2022 \u2022 \u2022 \u2022 \u2022 \u2022 \u2022 \u2022');
            } else {
                $chatInput.attr('type', 'text').attr('placeholder', 'Type a message...');
            }

            // Actions
            if (response.action) {
                if (response.action === 'reload') {
                    setTimeout(function () { location.reload(); }, 2000);
                } else if (response.action === 'scroll_schedule') {
                    scrollToSection('#schedule');
                } else if (response.action === 'scroll_tracks') {
                    scrollToSection('#tracks');
                }
            }
        }

        // ── Event Listeners ──────────────────────────────

        $chatSend.on('click', function () {
            sendMessage();
        });

        $chatInput.on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Suggestion click
        $(document).on('click', '.chat-suggestion-btn', function () {
            var val = $(this).text().trim();
            $('.chat-suggestions').fadeOut(200, function () { $(this).remove(); });
            sendMessage(val);
        });

        // Option click
        $(document).on('click', '.chat-option-btn', function () {
            var val = $(this).text();
            $(this).parent('.chat-options').fadeOut(200, function () { $(this).remove(); });
            sendMessage(val);
        });

        // ── Page scroll helper ───────────────────────────

        function scrollToSection(selector) {
            if ($(selector).length) {
                $('html, body').animate({
                    scrollTop: $(selector).offset().top - 80
                }, 800);
            }
        }
    });
})(jQuery);
