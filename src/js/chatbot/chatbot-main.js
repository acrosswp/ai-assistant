/**
 * AI Assistant Chatbot - Main Module (Combined)
 *
 * This is a combined, optimized version of the modular chatbot that avoids
 * timing issues with wpApiSettings while maintaining clean code organization.
 *
 * @since 0.0.8
 * @package Ai_Assistant
 * @requires jQuery
 */

(function($, window, document, undefined) {
    'use strict';

    // Ensure wpApiSettings is available
    if (typeof wpApiSettings === 'undefined') {
        console.error('AI Assistant Chatbot: wpApiSettings not loaded. Please refresh the page.');
        return;
    }

    console.log('🚀 AI Assistant Chatbot: Starting initialization with wpApiSettings:', wpApiSettings);

    /**
     * Utility Functions
     */
    const ChatbotUtils = {
        escapeHtml: function(text) {
            if (!text || typeof text !== 'string') {
                return '';
            }
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        log: function(level, message, data) {
            const timestamp = new Date().toISOString();
            const logMessage = `[${timestamp}] AI Chatbot ${level.toUpperCase()}: ${message}`;

            if (data) {
                console[level](logMessage, data);
            } else {
                console[level](logMessage);
            }
        },

        validateApiSettings: function() {
            const result = {
                isValid: false,
                missing: []
            };

            if (typeof wpApiSettings === 'undefined') {
                result.missing.push('wpApiSettings object');
                return result;
            }

            const requiredProps = ['nonce', 'messages_endpoint', 'root'];
            requiredProps.forEach(function(prop) {
                if (!wpApiSettings[prop]) {
                    result.missing.push(prop);
                }
            });

            result.isValid = result.missing.length === 0;
            return result;
        },

        generateId: function(prefix) {
            return (prefix || 'id') + '-' + Math.random().toString(36).substr(2, 9);
        },

        scrollToBottom: function(selector) {
            const element = $(selector)[0];
            if (element) {
                element.scrollTop = element.scrollHeight;
            }
        }
    };

    /**
     * API Communication
     */
    const ChatbotAPI = {
        sendMessage: function(messageText) {
            return new Promise(function(resolve, reject) {
                const validation = ChatbotUtils.validateApiSettings();
                if (!validation.isValid) {
                    reject(new Error('API configuration error: ' + validation.missing.join(', ')));
                    return;
                }

                const messageData = {
                    role: 'user',
                    parts: [{
                        channel: 'content',
                        type: 'text',
                        text: messageText
                    }],
                    type: 'regular'
                };

                ChatbotUtils.log('info', 'Sending message to API', {
                    endpoint: wpApiSettings.messages_endpoint,
                    messageLength: messageText.length
                });

                $.ajax({
                    url: wpApiSettings.messages_endpoint,
                    method: 'POST',
                    data: JSON.stringify(messageData),
                    contentType: 'application/json',
                    headers: {
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    timeout: 30000
                }).done(function(response) {
                    ChatbotUtils.log('info', 'Message sent successfully');
                    if (response && response.message) {
                        resolve({
                            message: response.message,
                            role: 'assistant'
                        });
                    } else {
                        reject(new Error('Invalid response format'));
                    }
                }).fail(function(xhr, status, error) {
                    ChatbotUtils.log('error', 'API request failed', {
                        status: xhr.status,
                        error: error,
                        response: xhr.responseText
                    });
                    reject(new Error(error || 'Request failed'));
                });
            });
        },

        loadHistory: function() {
            return new Promise(function(resolve, reject) {
                const validation = ChatbotUtils.validateApiSettings();
                if (!validation.isValid) {
                    reject(new Error('Cannot load history: ' + validation.missing.join(', ')));
                    return;
                }

                $.ajax({
                    url: wpApiSettings.messages_endpoint,
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    timeout: 15000
                }).done(function(response) {
                    if (Array.isArray(response)) {
                        ChatbotUtils.log('info', 'Loaded ' + response.length + ' messages from history');
                        resolve(response);
                    } else {
                        resolve([]);
                    }
                }).fail(function(xhr, status, error) {
                    ChatbotUtils.log('warn', 'Failed to load history', error);
                    resolve([]); // Don't fail initialization if history load fails
                });
            });
        },

        clearHistory: function() {
            return new Promise(function(resolve, reject) {
                const validation = ChatbotUtils.validateApiSettings();
                if (!validation.isValid) {
                    reject(new Error('Cannot clear history: ' + validation.missing.join(', ')));
                    return;
                }

                const resetUrl = wpApiSettings.messages_endpoint.replace('/messages', '/messages/reset');

                $.ajax({
                    url: resetUrl,
                    method: 'DELETE',
                    headers: {
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    timeout: 15000
                }).done(function(response) {
                    ChatbotUtils.log('info', 'Message history cleared successfully');
                    resolve(response);
                }).fail(function(xhr, status, error) {
                    ChatbotUtils.log('error', 'Failed to clear history', error);
                    reject(new Error(error || 'Failed to clear history'));
                });
            });
        }
    };

    /**
     * UI Management
     */
    const ChatbotUI = {
        chatbotId: 'ai-assistant-chatbot',
        toggleButtonId: 'ai-assistant-toggle',
        messagesContainerId: 'chatbot-messages',
        inputId: 'chatbot-input',
        formId: 'chatbot-form',

        createInterface: function() {
            if ($('#' + this.chatbotId).length > 0) {
                ChatbotUtils.log('warn', 'Chatbot interface already exists');
                return false;
            }

            const chatbotHTML = `
                <div id="${this.chatbotId}" class="ai-assistant-chatbot" style="display: none;">
                    <div class="chatbot-header">
                        <h3>AI Assistant</h3>
                        <div class="chatbot-header-actions">
                            <button class="chatbot-clear" aria-label="Clear History" title="Clear Chat History">🗑️</button>
                            <button class="chatbot-close" aria-label="Close Chatbot">&times;</button>
                        </div>
                    </div>
                    <div class="chatbot-messages" id="${this.messagesContainerId}">
                        <!-- Messages will be added here dynamically -->
                    </div>
                    <div class="chatbot-input">
                        <form id="${this.formId}">
                            <input type="text" id="${this.inputId}" placeholder="Type your message..." autocomplete="off" maxlength="1000">
                            <button type="submit" id="chatbot-send">Send</button>
                        </form>
                    </div>
                </div>
                <button id="${this.toggleButtonId}" class="ai-assistant-toggle">
                    💬 Need Help?
                </button>
            `;

            $('body').append(chatbotHTML);
            this.injectStyles();
            this.addWelcomeMessage();

            ChatbotUtils.log('info', 'Chatbot interface created');
            return true;
        },

        show: function() {
            $('#' + this.chatbotId).fadeIn(300);
            $('#' + this.toggleButtonId).hide();
            $('#' + this.inputId).focus();
        },

        hide: function() {
            $('#' + this.chatbotId).fadeOut(300);
            $('#' + this.toggleButtonId).show();
        },

        addMessage: function(content, type, messageType) {
            const $messagesContainer = $('#' + this.messagesContainerId);
            if ($messagesContainer.length === 0) return;

            const messageClass = type === 'user' ? 'user-message' : 'bot-message';
            const roleClass = messageType ? messageType + '-message' : '';
            const messageId = ChatbotUtils.generateId('msg');
            const escapedContent = ChatbotUtils.escapeHtml(content);
            const timestamp = new Date().toLocaleTimeString();

            const messageHTML = `
                <div class="message ${messageClass} ${roleClass}" id="${messageId}">
                    <div class="message-content">
                        <p>${escapedContent}</p>
                        <span class="message-time">${timestamp}</span>
                    </div>
                </div>
            `;

            const $message = $(messageHTML).hide();
            $messagesContainer.append($message);
            $message.fadeIn(200);

            ChatbotUtils.scrollToBottom('#' + this.messagesContainerId);
        },

        showLoading: function() {
            const $messagesContainer = $('#' + this.messagesContainerId);
            this.hideLoading(); // Remove any existing loading

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

            const $loading = $(loadingHTML).hide();
            $messagesContainer.append($loading);
            $loading.fadeIn(200);
            ChatbotUtils.scrollToBottom('#' + this.messagesContainerId);
        },

        hideLoading: function() {
            $('#loading-message').fadeOut(200, function() {
                $(this).remove();
            });
        },

        clearMessages: function(keepWelcome) {
            $('#' + this.messagesContainerId).empty();
            if (keepWelcome !== false) {
                this.addWelcomeMessage();
            }
        },

        addWelcomeMessage: function() {
            this.addMessage('Hello! How can I help you with your WordPress site today?', 'bot');
        },

        getInputValue: function() {
            return $('#' + this.inputId).val().trim();
        },

        clearInput: function() {
            $('#' + this.inputId).val('');
        },

        focusInput: function() {
            $('#' + this.inputId).focus();
        },

        injectStyles: function() {
            if ($('#ai-assistant-chatbot-styles').length > 0) return;

            const styles = `
                <style id="ai-assistant-chatbot-styles">
                .ai-assistant-toggle {
                    position: fixed; bottom: 20px; right: 20px; background: #0073aa; color: white;
                    border: none; padding: 12px 20px; border-radius: 25px; cursor: pointer;
                    font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000; transition: all 0.3s ease;
                }
                .ai-assistant-toggle:hover {
                    background: #005a87; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.2);
                }
                .ai-assistant-chatbot {
                    position: fixed; bottom: 20px; right: 20px; width: 350px; height: 500px;
                    background: white; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                    z-index: 1001; display: flex; flex-direction: column;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                .chatbot-header {
                    background: #0073aa; color: white; padding: 15px 20px; border-radius: 10px 10px 0 0;
                    display: flex; justify-content: space-between; align-items: center;
                }
                .chatbot-header h3 { margin: 0; font-size: 16px; font-weight: 600; }
                .chatbot-header-actions { display: flex; gap: 10px; align-items: center; }
                .chatbot-close, .chatbot-clear {
                    background: none; border: none; color: white; font-size: 16px; cursor: pointer;
                    padding: 4px; line-height: 1; border-radius: 3px; transition: background-color 0.2s ease;
                }
                .chatbot-close:hover, .chatbot-clear:hover { background: rgba(255, 255, 255, 0.2); }
                .chatbot-close { font-size: 20px; padding: 0; }
                .chatbot-messages {
                    flex: 1; padding: 20px; overflow-y: auto; background: #f8f9fa;
                }
                .message {
                    margin-bottom: 15px; display: flex; opacity: 0; animation: fadeInMessage 0.3s ease forwards;
                }
                @keyframes fadeInMessage { to { opacity: 1; } }
                .bot-message { justify-content: flex-start; }
                .user-message { justify-content: flex-end; }
                .message-content {
                    max-width: 80%; padding: 10px 15px; border-radius: 18px; font-size: 14px;
                    line-height: 1.4; position: relative;
                }
                .bot-message .message-content { background: white; border: 1px solid #e1e5e9; color: #333; }
                .user-message .message-content { background: #0073aa; color: white; }
                .error-message .message-content { background: #dc3545; color: white; }
                .message-time { font-size: 11px; opacity: 0.7; display: block; margin-top: 4px; }
                .chatbot-input {
                    padding: 20px; border-top: 1px solid #e1e5e9; background: white; border-radius: 0 0 10px 10px;
                }
                .chatbot-input form { display: flex; gap: 10px; }
                .chatbot-input input {
                    flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 20px;
                    outline: none; font-size: 14px;
                }
                .chatbot-input input:focus { border-color: #0073aa; }
                .chatbot-input button {
                    background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 20px;
                    cursor: pointer; font-size: 14px; font-weight: 500; transition: background-color 0.2s ease;
                }
                .chatbot-input button:hover { background: #005a87; }
                .chatbot-loading { display: flex; align-items: center; gap: 8px; color: #666; font-style: italic; }
                .loading-dots { display: inline-flex; gap: 2px; }
                .loading-dots span {
                    width: 4px; height: 4px; background: #666; border-radius: 50%;
                    animation: loadingDots 1.4s infinite ease-in-out;
                }
                .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
                .loading-dots span:nth-child(2) { animation-delay: -0.16s; }
                @keyframes loadingDots { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
                @media (max-width: 480px) {
                    .ai-assistant-chatbot { width: calc(100vw - 40px); height: calc(100vh - 40px); }
                }
                </style>
            `;

            $('head').append(styles);
        }
    };

    /**
     * Main Chatbot Controller
     */
    const AiAssistantChatbot = {
        initialized: false,
        processingMessage: false,

        init: function() {
            if (this.initialized) {
                ChatbotUtils.log('warn', 'Chatbot already initialized');
                return Promise.resolve(true);
            }

            ChatbotUtils.log('info', 'Initializing AI Assistant Chatbot v0.0.8');

            return new Promise((resolve, reject) => {
                try {
                    // Validate API settings
                    const validation = ChatbotUtils.validateApiSettings();
                    if (!validation.isValid) {
                        throw new Error('API validation failed: ' + validation.missing.join(', '));
                    }

                    // Create UI
                    if (!ChatbotUI.createInterface()) {
                        throw new Error('Failed to create chatbot interface');
                    }

                    // Setup event handlers
                    this.setupEventHandlers();

                    // Load message history
                    ChatbotAPI.loadHistory()
                        .then((messages) => {
                            if (messages && messages.length > 0) {
                                ChatbotUI.clearMessages(false);
                                messages.forEach((msg) => {
                                    const messageType = msg.role === 'user' ? 'user' : 'bot';
                                    ChatbotUI.addMessage(msg.content, messageType);
                                });
                            }

                            this.initialized = true;
                            ChatbotUtils.log('info', 'Chatbot initialization completed successfully');
                            resolve(true);
                        })
                        .catch((error) => {
                            ChatbotUtils.log('warn', 'Failed to load history, continuing anyway', error);
                            this.initialized = true;
                            resolve(true);
                        });

                } catch (error) {
                    ChatbotUtils.log('error', 'Chatbot initialization failed', error);
                    reject(error);
                }
            });
        },

        sendMessage: function(message) {
            if (this.processingMessage) {
                return Promise.reject(new Error('Already processing a message'));
            }

            if (!message || !message.trim()) {
                return Promise.reject(new Error('Message cannot be empty'));
            }

            this.processingMessage = true;
            const trimmedMessage = message.trim();

            ChatbotUtils.log('info', 'Sending message', { length: trimmedMessage.length });

            ChatbotUI.addMessage(trimmedMessage, 'user');
            ChatbotUI.showLoading();

            return ChatbotAPI.sendMessage(trimmedMessage)
                .then((response) => {
                    ChatbotUI.hideLoading();
                    if (response && response.message) {
                        ChatbotUI.addMessage(response.message, 'bot');
                        ChatbotUtils.log('info', 'AI response received and displayed');
                    }
                    this.processingMessage = false;
                    return true;
                })
                .catch((error) => {
                    ChatbotUI.hideLoading();
                    const errorMessage = error.message || 'Sorry, something went wrong. Please try again.';
                    ChatbotUI.addMessage(errorMessage, 'bot', 'error');
                    ChatbotUtils.log('error', 'Failed to send message', error);
                    this.processingMessage = false;
                    throw error;
                });
        },

        clearHistory: function() {
            if (!confirm('Are you sure you want to clear all chat history? This action cannot be undone.')) {
                return Promise.resolve(false);
            }

            return ChatbotAPI.clearHistory()
                .then(() => {
                    ChatbotUI.clearMessages(true);
                    ChatbotUtils.log('info', 'Chat history cleared successfully');
                    return true;
                })
                .catch((error) => {
                    ChatbotUI.addMessage('Failed to clear history. Please try again.', 'bot', 'error');
                    throw error;
                });
        },

        setupEventHandlers: function() {
            // Toggle button click
            $(document).on('click', '#' + ChatbotUI.toggleButtonId, (e) => {
                e.preventDefault();
                ChatbotUI.show();
            });

            // Close button click
            $(document).on('click', '.chatbot-close', (e) => {
                e.preventDefault();
                ChatbotUI.hide();
            });

            // Clear history button click
            $(document).on('click', '.chatbot-clear', (e) => {
                e.preventDefault();
                this.clearHistory();
            });

            // Form submission
            $(document).on('submit', '#' + ChatbotUI.formId, (e) => {
                e.preventDefault();
                const message = ChatbotUI.getInputValue();
                if (!message) return;

                ChatbotUI.clearInput();
                this.sendMessage(message).catch(() => {
                    ChatbotUI.focusInput();
                });
            });

            // Enter key handling
            $(document).on('keypress', '#' + ChatbotUI.inputId, (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $('#' + ChatbotUI.formId).submit();
                }
            });

            // Escape key to close
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('#' + ChatbotUI.chatbotId).is(':visible')) {
                    ChatbotUI.hide();
                }
            });

            ChatbotUtils.log('info', 'Event handlers attached');
        }
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        ChatbotUtils.log('info', 'DOM ready, starting chatbot initialization');

        AiAssistantChatbot.init()
            .then(() => {
                ChatbotUtils.log('info', 'Chatbot successfully initialized and ready');
                // Make available globally for debugging
                window.AiAssistantChatbot = AiAssistantChatbot;
                console.log('✅ AI Assistant Chatbot ready! Use window.AiAssistantChatbot for debugging.');
            })
            .catch((error) => {
                ChatbotUtils.log('error', 'Chatbot initialization failed', error);
                console.error('❌ AI Assistant Chatbot failed to initialize:', error);
            });
    });

})(jQuery, window, document);
