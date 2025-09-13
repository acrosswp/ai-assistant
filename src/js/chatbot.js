/**
 * AI Assistant Chatbot Script
 *
 * @since 0.0.1
 */

(function($) {
    'use strict';

    // Chatbot functionality
    const AiAssistantChatbot = {

        /**
         * Initialize the chatbot
         */
        init: function() {
            this.createChatbotInterface();
            this.bindEvents();
        },

        /**
         * Create the chatbot interface
         */
        createChatbotInterface: function() {
            const chatbotHTML = `
                <div id="ai-assistant-chatbot" class="ai-assistant-chatbot" style="display: none;">
                    <div class="chatbot-header">
                        <h3>AI Assistant</h3>
                        <button class="chatbot-close" aria-label="Close Chatbot">&times;</button>
                    </div>
                    <div class="chatbot-messages" id="chatbot-messages">
                        <div class="message bot-message">
                            <div class="message-content">
                                <p>Hello! How can I help you with your WordPress site today?</p>
                            </div>
                        </div>
                    </div>
                    <div class="chatbot-input">
                        <form id="chatbot-form">
                            <input type="text" id="chatbot-input" placeholder="Type your message..." autocomplete="off">
                            <button type="submit" id="chatbot-send">Send</button>
                        </form>
                    </div>
                </div>
                <button id="ai-assistant-toggle" class="ai-assistant-toggle">
                    💬 Need Help?
                </button>
            `;

            $('body').append(chatbotHTML);
            this.addStyles();
        },

        /**
         * Add chatbot styles
         */
        addStyles: function() {
            const styles = `
                <style>
                .ai-assistant-toggle {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #0073aa;
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 25px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000;
                    transition: all 0.3s ease;
                }

                .ai-assistant-toggle:hover {
                    background: #005a87;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
                }

                .ai-assistant-chatbot {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    width: 350px;
                    height: 500px;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                    z-index: 1001;
                    display: flex;
                    flex-direction: column;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }

                .chatbot-header {
                    background: #0073aa;
                    color: white;
                    padding: 15px 20px;
                    border-radius: 10px 10px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .chatbot-header h3 {
                    margin: 0;
                    font-size: 16px;
                    font-weight: 600;
                }

                .chatbot-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 20px;
                    cursor: pointer;
                    padding: 0;
                    line-height: 1;
                }

                .chatbot-messages {
                    flex: 1;
                    padding: 20px;
                    overflow-y: auto;
                    background: #f8f9fa;
                }

                .message {
                    margin-bottom: 15px;
                    display: flex;
                }

                .bot-message {
                    justify-content: flex-start;
                }

                .user-message {
                    justify-content: flex-end;
                }

                .message-content {
                    max-width: 80%;
                    padding: 10px 15px;
                    border-radius: 18px;
                    font-size: 14px;
                    line-height: 1.4;
                }

                .bot-message .message-content {
                    background: white;
                    border: 1px solid #e1e5e9;
                    color: #333;
                }

                .user-message .message-content {
                    background: #0073aa;
                    color: white;
                }

                .chatbot-input {
                    padding: 20px;
                    border-top: 1px solid #e1e5e9;
                    background: white;
                    border-radius: 0 0 10px 10px;
                }

                .chatbot-input form {
                    display: flex;
                    gap: 10px;
                }

                .chatbot-input input {
                    flex: 1;
                    padding: 10px 15px;
                    border: 1px solid #ddd;
                    border-radius: 20px;
                    outline: none;
                    font-size: 14px;
                }

                .chatbot-input input:focus {
                    border-color: #0073aa;
                }

                .chatbot-input button {
                    background: #0073aa;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 20px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                }

                .chatbot-input button:hover {
                    background: #005a87;
                }

                .chatbot-loading {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    color: #666;
                    font-style: italic;
                }

                .loading-dots {
                    display: inline-flex;
                    gap: 2px;
                }

                .loading-dots span {
                    width: 4px;
                    height: 4px;
                    background: #666;
                    border-radius: 50%;
                    animation: loadingDots 1.4s infinite ease-in-out;
                }

                .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
                .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

                @keyframes loadingDots {
                    0%, 80%, 100% { transform: scale(0); }
                    40% { transform: scale(1); }
                }

                @media (max-width: 480px) {
                    .ai-assistant-chatbot {
                        width: calc(100vw - 40px);
                        height: calc(100vh - 40px);
                        bottom: 20px;
                        right: 20px;
                    }
                }
                </style>
            `;
            $('head').append(styles);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Toggle chatbot
            $(document).on('click', '#ai-assistant-toggle', function() {
                self.toggleChatbot();
            });

            // Close chatbot
            $(document).on('click', '.chatbot-close', function() {
                self.closeChatbot();
            });

            // Send message
            $(document).on('submit', '#chatbot-form', function(e) {
                e.preventDefault();
                self.sendMessage();
            });

            // Close on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#ai-assistant-chatbot').is(':visible')) {
                    self.closeChatbot();
                }
            });
        },

        /**
         * Toggle chatbot visibility
         */
        toggleChatbot: function() {
            const chatbot = $('#ai-assistant-chatbot');
            const toggle = $('#ai-assistant-toggle');

            if (chatbot.is(':visible')) {
                this.closeChatbot();
            } else {
                chatbot.show();
                toggle.hide();
                $('#chatbot-input').focus();
            }
        },

        /**
         * Close chatbot
         */
        closeChatbot: function() {
            $('#ai-assistant-chatbot').hide();
            $('#ai-assistant-toggle').show();
        },

        /**
         * Send message to chatbot
         */
        sendMessage: function() {
            const input = $('#chatbot-input');
            const message = input.val().trim();

            if (!message) return;

            // Add user message to chat
            this.addMessage(message, 'user');
            input.val('');

            // Show loading
            this.showLoading();

            // Send to API
            this.callChatbotAPI(message);
        },

        /**
         * Add message to chat
         */
        addMessage: function(content, type) {
            const messagesContainer = $('#chatbot-messages');
            const messageClass = type === 'user' ? 'user-message' : 'bot-message';

            const messageHTML = `
                <div class="message ${messageClass}">
                    <div class="message-content">
                        <p>${this.escapeHtml(content)}</p>
                    </div>
                </div>
            `;

            messagesContainer.append(messageHTML);
            this.scrollToBottom();
        },

        /**
         * Show loading indicator
         */
        showLoading: function() {
            const messagesContainer = $('#chatbot-messages');
            const loadingHTML = `
                <div class="message bot-message" id="loading-message">
                    <div class="message-content chatbot-loading">
                        <span>Thinking</span>
                        <div class="loading-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            messagesContainer.append(loadingHTML);
            this.scrollToBottom();
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            $('#loading-message').remove();
        },

        /**
         * Call chatbot API
         */
        callChatbotAPI: function(message) {
            const self = this;

            // Prepare message data
            const messageData = {
                role: 'user',
                parts: [{
                    channel: 'content',
                    type: 'text',
                    text: message
                }],
                type: 'regular'
            };

            $.ajax({
                url: (typeof wpApiSettings !== 'undefined' && wpApiSettings.messages_endpoint) ? wpApiSettings.messages_endpoint : ajaxurl.replace('admin-ajax.php', 'rest/ai-assistant/v1/messages'),
                method: 'POST',
                headers: {
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                data: JSON.stringify(messageData),
                contentType: 'application/json',
                success: function(response) {
                    self.hideLoading();

                    if (response && response.parts && response.parts[0] && response.parts[0].text) {
                        self.addMessage(response.parts[0].text, 'bot');
                    } else {
                        self.addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    console.error('Chatbot API Error:', error);
                    self.addMessage('Sorry, I\'m having trouble connecting right now. Please try again later.', 'bot');
                }
            });
        },

        /**
         * Scroll to bottom of messages
         */
        scrollToBottom: function() {
            const messagesContainer = $('#chatbot-messages');
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if user has permission (admin pages)
        if (typeof ajaxurl !== 'undefined') {
            AiAssistantChatbot.init();
        }
    });

})(jQuery);
