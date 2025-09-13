/**
 * AI Assistant Chatbot - Core Module
 *
 * This is the main coordination module for the AI Assistant chatbot.
 * It orchestrates all other modules and handles the overall chatbot lifecycle.
 *
 * @since 0.0.5
 * @package Ai_Assistant
 * @requires jQuery
 * @requires AiAssistantChatbotUtils
 * @requires AiAssistantChatbotAPI
 * @requires AiAssistantChatbotUI
 */

(function($, window, document, undefined) {
    'use strict';

    /**
     * Chatbot Core Controller
     *
     * Main coordination center for the chatbot functionality.
     * Manages initialization, state, and coordinates between modules.
     */
    window.AiAssistantChatbot = {

        /**
         * Core Configuration
         * @private
         */
        _config: {
            version: '0.0.7',
            ready: false,
            initialized: false,
            processingMessage: false
        },

        /**
         * Dependencies check
         * @private
         */
        _dependencies: {
            'AiAssistantChatbotUtils': false,
            'AiAssistantChatbotAPI': false,
            'AiAssistantChatbotUI': false
        },

        /**
         * Initialize Chatbot
         *
         * Main initialization method that sets up the entire chatbot system.
         * Checks dependencies, validates API settings, creates UI, and sets up event handlers.
         *
         * @since 0.0.5
         * @returns {Promise<boolean>} True if initialization successful
         */
        init: function() {
            const self = this;
            const utils = window.AiAssistantChatbotUtils;

            return new Promise(function(resolve, reject) {
                try {
                    utils.log('info', 'Initializing AI Assistant Chatbot v' + self._config.version);

                    // Check if already initialized
                    if (self._config.initialized) {
                        utils.log('warn', 'Chatbot already initialized');
                        resolve(true);
                        return;
                    }

                    // Validate dependencies
                    if (!self._validateDependencies()) {
                        const error = 'Missing required dependencies';
                        utils.log('error', error);
                        reject(new Error(error));
                        return;
                    }

                    // Validate API configuration
                    if (!self._validateApiConfiguration()) {
                        const error = 'Invalid API configuration';
                        utils.log('error', error);
                        reject(new Error(error));
                        return;
                    }

                    // Create user interface
                    if (!window.AiAssistantChatbotUI.createInterface()) {
                        const error = 'Failed to create chatbot interface';
                        utils.log('error', error);
                        reject(new Error(error));
                        return;
                    }

                    // Setup event handlers
                    self._setupEventHandlers();

                    // Load message history
                    self._loadInitialHistory()
                        .then(function() {
                            self._config.initialized = true;
                            self._config.ready = true;

                            utils.log('info', 'Chatbot initialization completed successfully');
                            resolve(true);
                        })
                        .catch(function(error) {
                            utils.log('error', 'Failed to load initial history', error);
                            // Don't fail initialization if history load fails
                            self._config.initialized = true;
                            self._config.ready = true;
                            resolve(true);
                        });

                } catch (error) {
                    utils.log('error', 'Chatbot initialization failed', error);
                    reject(error);
                }
            });
        },

        /**
         * Send Message
         *
         * Handles sending a user message and processing the AI response.
         *
         * @since 0.0.5
         * @param {string} message - The user message to send
         * @returns {Promise<boolean>} True if message sent successfully
         */
        sendMessage: function(message) {
            const self = this;
            const utils = window.AiAssistantChatbotUtils;
            const api = window.AiAssistantChatbotAPI;
            const ui = window.AiAssistantChatbotUI;

            return new Promise(function(resolve, reject) {
                try {
                    // Validate input
                    if (!message || typeof message !== 'string' || !message.trim()) {
                        utils.log('warn', 'Empty or invalid message');
                        reject(new Error('Message cannot be empty'));
                        return;
                    }

                    // Check if already processing
                    if (self._config.processingMessage) {
                        utils.log('warn', 'Already processing a message');
                        reject(new Error('Please wait for the current message to complete'));
                        return;
                    }

                    // Check if chatbot is ready
                    if (!self._config.ready) {
                        utils.log('error', 'Chatbot not ready');
                        reject(new Error('Chatbot is not ready yet'));
                        return;
                    }

                    self._config.processingMessage = true;
                    const trimmedMessage = message.trim();

                    utils.log('info', 'Sending user message', {
                        length: trimmedMessage.length,
                        preview: trimmedMessage.substring(0, 50)
                    });

                    // Add user message to UI
                    ui.addMessage(trimmedMessage, 'user');
                    ui.showLoading();

                    // Send to API
                    api.sendMessage(trimmedMessage)
                        .then(function(response) {
                            ui.hideLoading();

                            if (response && response.message) {
                                ui.addMessage(response.message, 'bot');
                                utils.log('info', 'AI response received and displayed');
                            } else {
                                throw new Error('Invalid response format');
                            }

                            self._config.processingMessage = false;
                            resolve(true);
                        })
                        .catch(function(error) {
                            ui.hideLoading();

                            const errorMessage = error.message || 'Sorry, something went wrong. Please try again.';
                            ui.addMessage(errorMessage, 'bot', 'error');

                            utils.log('error', 'Failed to send message', error);
                            self._config.processingMessage = false;

                            reject(error);
                        });

                } catch (error) {
                    self._config.processingMessage = false;
                    ui.hideLoading();
                    utils.log('error', 'Message sending failed', error);
                    reject(error);
                }
            });
        },

        /**
         * Clear Chat History
         *
         * Clears all chat messages with user confirmation.
         *
         * @since 0.0.5
         * @returns {Promise<boolean>} True if history cleared successfully
         */
        clearHistory: function() {
            const self = this;
            const utils = window.AiAssistantChatbotUtils;
            const api = window.AiAssistantChatbotAPI;
            const ui = window.AiAssistantChatbotUI;

            return new Promise(function(resolve, reject) {
                try {
                    // Confirm with user
                    if (!confirm('Are you sure you want to clear all chat history? This action cannot be undone.')) {
                        utils.log('info', 'User cancelled history clear');
                        resolve(false);
                        return;
                    }

                    utils.log('info', 'Clearing chat history');

                    api.clearHistory()
                        .then(function() {
                            ui.clearMessages(true); // Keep welcome message
                            utils.log('info', 'Chat history cleared successfully');
                            resolve(true);
                        })
                        .catch(function(error) {
                            utils.log('error', 'Failed to clear history', error);
                            ui.addMessage('Failed to clear history. Please try again.', 'bot', 'error');
                            reject(error);
                        });

                } catch (error) {
                    utils.log('error', 'History clearing failed', error);
                    reject(error);
                }
            });
        },

        /**
         * Show Chatbot
         *
         * Displays the chatbot interface.
         *
         * @since 0.0.5
         */
        show: function() {
            const utils = window.AiAssistantChatbotUtils;
            const ui = window.AiAssistantChatbotUI;

            if (!this._config.ready) {
                utils.log('error', 'Cannot show chatbot: not ready');
                return;
            }

            ui.show(function() {
                ui.focusInput();
            });
        },

        /**
         * Hide Chatbot
         *
         * Hides the chatbot interface.
         *
         * @since 0.0.5
         */
        hide: function() {
            const ui = window.AiAssistantChatbotUI;
            ui.hide();
        },

        /**
         * Get Status
         *
         * Returns current chatbot status information.
         *
         * @since 0.0.5
         * @returns {Object} Status information
         */
        getStatus: function() {
            return {
                version: this._config.version,
                ready: this._config.ready,
                initialized: this._config.initialized,
                processing: this._config.processingMessage,
                dependencies: this._dependencies,
                modular: true,
                timestamp: new Date().toISOString()
            };
        },

        /**
         * Validate Dependencies
         *
         * Checks if all required modules are available.
         *
         * @since 0.0.5
         * @private
         * @returns {boolean} True if all dependencies available
         */
        _validateDependencies: function() {
            const utils = window.AiAssistantChatbotUtils;
            let allValid = true;

            for (const dep in this._dependencies) {
                const available = typeof window[dep] === 'object' && window[dep] !== null;
                this._dependencies[dep] = available;

                if (!available) {
                    utils.log('error', 'Missing dependency: ' + dep);
                    allValid = false;
                }
            }

            utils.log('info', 'Dependencies validation', this._dependencies);
            return allValid;
        },

        /**
         * Validate API Configuration
         *
         * Checks if API settings are properly configured.
         *
         * @since 0.0.5
         * @private
         * @returns {boolean} True if API configuration valid
         */
        _validateApiConfiguration: function() {
            const utils = window.AiAssistantChatbotUtils;

            try {
                const validation = utils.validateApiSettings();

                if (!validation.isValid) {
                    utils.log('error', 'API validation failed', validation.missing);
                    return false;
                }

                utils.log('info', 'API configuration validated successfully');
                return true;

            } catch (error) {
                utils.log('error', 'API configuration validation failed', error);
                return false;
            }
        },

        /**
         * Setup Event Handlers
         *
         * Attaches all necessary event listeners for chatbot interaction.
         *
         * @since 0.0.5
         * @private
         */
        _setupEventHandlers: function() {
            const self = this;
            const utils = window.AiAssistantChatbotUtils;
            const ui = window.AiAssistantChatbotUI;

            // Toggle button click
            $(document).on('click', '#ai-assistant-toggle', function(e) {
                e.preventDefault();
                self.show();
            });

            // Close button click
            $(document).on('click', '.chatbot-close', function(e) {
                e.preventDefault();
                self.hide();
            });

            // Clear history button click
            $(document).on('click', '.chatbot-clear', function(e) {
                e.preventDefault();
                self.clearHistory();
            });

            // Form submission
            $(document).on('submit', '#chatbot-form', function(e) {
                e.preventDefault();

                const message = ui.getInputValue();
                if (!message) {
                    return;
                }

                ui.clearInput();

                self.sendMessage(message)
                    .catch(function(error) {
                        // Error already handled in sendMessage
                        ui.focusInput();
                    });
            });

            // Input field enter key (for better UX)
            $(document).on('keypress', '#chatbot-input', function(e) {
                if (e.which === 13 && !e.shiftKey) { // Enter key without shift
                    e.preventDefault();
                    $('#chatbot-form').submit();
                }
            });

            // Escape key to close chatbot
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#ai-assistant-chatbot').is(':visible')) {
                    self.hide();
                }
            });

            utils.log('info', 'Event handlers attached');
        },

        /**
         * Load Initial History
         *
         * Loads and displays existing chat history on initialization.
         *
         * @since 0.0.5
         * @private
         * @returns {Promise<boolean>} True if history loaded successfully
         */
        _loadInitialHistory: function() {
            const utils = window.AiAssistantChatbotUtils;
            const api = window.AiAssistantChatbotAPI;
            const ui = window.AiAssistantChatbotUI;

            return new Promise(function(resolve, reject) {
                utils.log('info', 'Loading initial chat history');

                api.loadHistory()
                    .then(function(messages) {
                        if (messages && messages.length > 0) {
                            // Clear welcome message to show history
                            ui.clearMessages(false);

                            // Add each message to UI
                            messages.forEach(function(msg) {
                                const messageType = msg.role === 'user' ? 'user' : 'bot';
                                ui.addMessage(msg.content, messageType);
                            });

                            utils.log('info', 'Loaded ' + messages.length + ' messages from history');
                        } else {
                            utils.log('info', 'No existing chat history found');
                        }

                        resolve(true);
                    })
                    .catch(function(error) {
                        utils.log('warn', 'Failed to load chat history', error);
                        // Don't reject - this shouldn't prevent initialization
                        resolve(true);
                    });
            });
        }
    };

    /**
     * Auto-initialization when DOM is ready
     *
     * The chatbot will automatically initialize when all dependencies are loaded
     * and the DOM is ready.
     */
    $(document).ready(function() {
        const utils = window.AiAssistantChatbotUtils;

        // Log module loading status
        utils.log('info', 'Modular chatbot loading status', {
            utils: typeof window.AiAssistantChatbotUtils,
            api: typeof window.AiAssistantChatbotAPI,
            ui: typeof window.AiAssistantChatbotUI,
            core: typeof window.AiAssistantChatbot,
            wpApiSettings: typeof window.wpApiSettings
        });

        // Small delay to ensure all modules are loaded
        setTimeout(function() {
            if (window.AiAssistantChatbot) {
                window.AiAssistantChatbot.init()
                    .then(function() {
                        utils.log('info', 'Modular chatbot initialization completed successfully');

                        // Make status available globally for debugging
                        window.chatbotStatus = window.AiAssistantChatbot.getStatus();
                        console.log('AI Assistant Chatbot Status:', window.chatbotStatus);
                    })
                    .catch(function(error) {
                        utils.log('error', 'Modular chatbot initialization failed', error);
                        console.error('AI Assistant Chatbot Error:', error);
                    });
            } else {
                utils.log('error', 'AiAssistantChatbot core module not found');
            }
        }, 100);
    });

})(jQuery, window, document);
