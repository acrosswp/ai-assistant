/**
 * AI Assistant Chatbot - User Interface
 *
 * This file handles all UI-related functionality for the chatbot including:
 * - Creating the chatbot interface HTML
 * - Managing chatbot styles
 * - Handling UI interactions and animations
 * - Message display and formatting
 *
 * @since 0.0.5
 * @package Ai_Assistant
 * @requires jQuery
 * @requires AiAssistantChatbotUtils
 */

(function($, window, document, undefined) {
    'use strict';

    /**
     * Chatbot UI Manager
     *
     * Manages all user interface aspects of the chatbot including:
     * - HTML structure creation
     * - CSS styling injection
     * - Message rendering
     * - UI state management
     */
    window.AiAssistantChatbotUI = {

        /**
         * UI Configuration
         * @private
         */
        _config: {
            chatbotId: 'ai-assistant-chatbot',
            toggleButtonId: 'ai-assistant-toggle',
            messagesContainerId: 'chatbot-messages',
            inputId: 'chatbot-input',
            formId: 'chatbot-form',
            loadingMessageId: 'loading-message',
            animationDuration: 300
        },

        /**
         * Create Chatbot Interface
         *
         * Creates and injects the complete chatbot HTML structure into the page.
         * This includes the chat container, header, messages area, and input form.
         *
         * @since 0.0.5
         * @returns {boolean} True if interface was created successfully
         */
        createInterface: function() {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;

            utils.log('info', 'Creating chatbot interface');

            try {
                // Check if chatbot already exists
                if ($('#' + config.chatbotId).length > 0) {
                    utils.log('warn', 'Chatbot interface already exists');
                    return false;
                }

                const chatbotHTML = this._generateHTML();
                $('body').append(chatbotHTML);

                utils.log('info', 'Chatbot HTML injected into page');

                this._injectStyles();
                utils.log('info', 'Chatbot styles injected');

                // Add initial welcome message
                this._addWelcomeMessage();

                return true;

            } catch (error) {
                utils.log('error', 'Failed to create chatbot interface', error);
                return false;
            }
        },

        /**
         * Show Chatbot
         *
         * Displays the chatbot interface with animation.
         *
         * @since 0.0.5
         * @param {Function} callback - Optional callback when animation completes
         */
        show: function(callback) {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;

            const $chatbot = $('#' + config.chatbotId);
            const $toggle = $('#' + config.toggleButtonId);

            if ($chatbot.length === 0) {
                utils.log('error', 'Cannot show chatbot: interface not found');
                return;
            }

            utils.log('info', 'Showing chatbot interface');

            $chatbot.fadeIn(config.animationDuration, callback);
            $toggle.hide();

            // Focus on input field
            setTimeout(function() {
                $('#' + config.inputId).focus();
            }, config.animationDuration);
        },

        /**
         * Hide Chatbot
         *
         * Hides the chatbot interface with animation.
         *
         * @since 0.0.5
         * @param {Function} callback - Optional callback when animation completes
         */
        hide: function(callback) {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;

            const $chatbot = $('#' + config.chatbotId);
            const $toggle = $('#' + config.toggleButtonId);

            utils.log('info', 'Hiding chatbot interface');

            $chatbot.fadeOut(config.animationDuration, callback);
            $toggle.show();
        },

        /**
         * Add Message to Chat
         *
         * Adds a message to the chat interface with proper formatting and animation.
         *
         * @since 0.0.5
         * @param {string} content - The message content (will be HTML escaped)
         * @param {string} type - Message type ('user' or 'bot')
         * @param {string} messageType - Optional message type ('error', 'success', etc.)
         */
        addMessage: function(content, type, messageType) {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;

            if (!content || typeof content !== 'string') {
                utils.log('warn', 'Invalid message content');
                return;
            }

            const $messagesContainer = $('#' + config.messagesContainerId);
            if ($messagesContainer.length === 0) {
                utils.log('error', 'Messages container not found');
                return;
            }

            // Determine message class and role
            const messageClass = type === 'user' ? 'user-message' : 'bot-message';
            const roleClass = messageType ? messageType + '-message' : '';

            // Create message HTML with escaped content
            const messageId = utils.generateId('msg');
            const escapedContent = utils.escapeHtml(content);
            const timestamp = new Date().toLocaleTimeString();

            const messageHTML = `
                <div class="message ${messageClass} ${roleClass}" id="${messageId}" data-timestamp="${timestamp}">
                    <div class="message-content">
                        <p>${escapedContent}</p>
                        <span class="message-time">${timestamp}</span>
                    </div>
                </div>
            `;

            // Add message with fade-in animation
            const $message = $(messageHTML).hide();
            $messagesContainer.append($message);
            $message.fadeIn(200);

            // Scroll to bottom
            this.scrollToBottom();

            utils.log('info', 'Added ' + type + ' message', {
                id: messageId,
                length: content.length
            });
        },

        /**
         * Show Loading Indicator
         *
         * Displays a loading animation while waiting for AI response.
         *
         * @since 0.0.5
         */
        showLoading: function() {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;

            const $messagesContainer = $('#' + config.messagesContainerId);
            if ($messagesContainer.length === 0) {
                return;
            }

            // Remove existing loading message
            this.hideLoading();

            const loadingHTML = `
                <div class="message bot-message" id="${config.loadingMessageId}">
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

            this.scrollToBottom();
            utils.log('info', 'Loading indicator shown');
        },

        /**
         * Hide Loading Indicator
         *
         * Removes the loading animation.
         *
         * @since 0.0.5
         */
        hideLoading: function() {
            const config = this._config;
            $('#' + config.loadingMessageId).fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Clear Messages
         *
         * Removes all messages from the chat interface.
         *
         * @since 0.0.5
         * @param {boolean} keepWelcome - Whether to keep the welcome message
         */
        clearMessages: function(keepWelcome) {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;

            const $messagesContainer = $('#' + config.messagesContainerId);
            if ($messagesContainer.length === 0) {
                return;
            }

            $messagesContainer.empty();

            if (keepWelcome !== false) {
                this._addWelcomeMessage();
            }

            utils.log('info', 'Messages cleared', {keepWelcome: keepWelcome});
        },

        /**
         * Scroll to Bottom
         *
         * Scrolls the messages container to show the latest message.
         *
         * @since 0.0.5
         */
        scrollToBottom: function() {
            const utils = window.AiAssistantChatbotUtils;
            const config = this._config;
            utils.scrollToBottom('#' + config.messagesContainerId, true);
        },

        /**
         * Get Input Value
         *
         * Gets the current value from the chat input field.
         *
         * @since 0.0.5
         * @returns {string} The trimmed input value
         */
        getInputValue: function() {
            const config = this._config;
            return $('#' + config.inputId).val().trim();
        },

        /**
         * Clear Input
         *
         * Clears the chat input field.
         *
         * @since 0.0.5
         */
        clearInput: function() {
            const config = this._config;
            $('#' + config.inputId).val('');
        },

        /**
         * Focus Input
         *
         * Focuses the chat input field.
         *
         * @since 0.0.5
         */
        focusInput: function() {
            const config = this._config;
            $('#' + config.inputId).focus();
        },

        /**
         * Generate HTML Structure
         *
         * @since 0.0.5
         * @private
         * @returns {string} Complete chatbot HTML
         */
        _generateHTML: function() {
            const config = this._config;

            return `
                <div id="${config.chatbotId}" class="ai-assistant-chatbot" style="display: none;">
                    <div class="chatbot-header">
                        <h3>AI Assistant</h3>
                        <div class="chatbot-header-actions">
                            <button class="chatbot-clear" aria-label="Clear History" title="Clear Chat History">🗑️</button>
                            <button class="chatbot-close" aria-label="Close Chatbot">&times;</button>
                        </div>
                    </div>
                    <div class="chatbot-messages" id="${config.messagesContainerId}">
                        <!-- Messages will be added here dynamically -->
                    </div>
                    <div class="chatbot-input">
                        <form id="${config.formId}">
                            <input type="text" id="${config.inputId}" placeholder="Type your message..." autocomplete="off" maxlength="1000">
                            <button type="submit" id="chatbot-send">Send</button>
                        </form>
                    </div>
                </div>
                <button id="${config.toggleButtonId}" class="ai-assistant-toggle">
                    💬 Need Help?
                </button>
            `;
        },

        /**
         * Add Welcome Message
         *
         * @since 0.0.5
         * @private
         */
        _addWelcomeMessage: function() {
            const welcomeMessage = 'Hello! How can I help you with your WordPress site today?';
            this.addMessage(welcomeMessage, 'bot');
        },

        /**
         * Inject CSS Styles
         *
         * @since 0.0.5
         * @private
         */
        _injectStyles: function() {
            if ($('#ai-assistant-chatbot-styles').length > 0) {
                return; // Styles already injected
            }

            const styles = `
                <style id="ai-assistant-chatbot-styles">
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

                .chatbot-header-actions {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }

                .chatbot-close, .chatbot-clear {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 16px;
                    cursor: pointer;
                    padding: 4px;
                    line-height: 1;
                    border-radius: 3px;
                    transition: background-color 0.2s ease;
                }

                .chatbot-close:hover, .chatbot-clear:hover {
                    background: rgba(255, 255, 255, 0.2);
                }

                .chatbot-close {
                    font-size: 20px;
                    padding: 0;
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
                    opacity: 0;
                    animation: fadeInMessage 0.3s ease forwards;
                }

                @keyframes fadeInMessage {
                    to { opacity: 1; }
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
                    position: relative;
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

                .error-message .message-content {
                    background: #dc3545;
                    color: white;
                }

                .message-time {
                    font-size: 11px;
                    opacity: 0.7;
                    display: block;
                    margin-top: 4px;
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
                    transition: background-color 0.2s ease;
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
        }
    };

})(jQuery, window, document);
