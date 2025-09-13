/**
 * AI Assistant Chatbot - API Communication
 *
 * This file handles all API communication between the chatbot and the WordPress REST API.
 * It manages message sending, history loading, and chat history clearing operations.
 *
 * @since 0.0.5
 * @package Ai_Assistant
 * @requires jQuery
 * @requires AiAssistantChatbotUtils
 */

(function($, window, document, undefined) {
    'use strict';

    /**
     * Chatbot API Manager
     *
     * Handles all API communications for the chatbot including:
     * - Sending messages to the AI assistant
     * - Loading message history
     * - Clearing chat history
     * - Error handling and retries
     */
    window.AiAssistantChatbotAPI = {

        /**
         * Configuration object for API settings
         * @private
         */
        _config: {
            maxRetries: 3,
            retryDelay: 1000,
            timeout: 30000
        },

        /**
         * Send Message to AI Assistant
         *
         * Sends a user message to the AI assistant via the WordPress REST API
         * and returns the response through callbacks.
         *
         * @since 0.0.5
         * @param {string} messageText - The user's message text
         * @param {Object} callbacks - Success and error callback functions
         * @param {Function} callbacks.onSuccess - Called with the AI response
         * @param {Function} callbacks.onError - Called if an error occurs
         * @param {Function} callbacks.onProgress - Called during processing (optional)
         *
         * @example
         * AiAssistantChatbotAPI.sendMessage('Hello!', {
         *     onSuccess: function(response) { console.log('AI said:', response.text); },
         *     onError: function(error) { console.error('Failed:', error); }
         * });
         */
        sendMessage: function(messageText) {
            const utils = window.AiAssistantChatbotUtils;
            const self = this;

            return new Promise(function(resolve, reject) {
                if (!messageText || typeof messageText !== 'string') {
                    const error = new Error('Invalid message text provided');
                    utils.log('error', error.message);
                    reject(error);
                    return;
                }

                // Wait for API settings to be available, then proceed
                utils.waitForApiSettings(5000)
                    .then(function() {
                        return self._sendMessageWithSettings(messageText);
                    })
                    .then(resolve)
                    .catch(function(error) {
                        const errorMsg = 'Configuration error: wpApiSettings not loaded. Please refresh the page.';
                        const configError = new Error(errorMsg);
                        utils.log('error', errorMsg, error);
                        reject(configError);
                    });
            });
        },

        /**
         * Internal method to send message after API settings are confirmed available
         * @private
         */
        _sendMessageWithSettings: function(messageText) {
            const utils = window.AiAssistantChatbotUtils;
            const self = this;

            return new Promise(function(resolve, reject) {
                // Validate API settings (should be available now)
                const validation = utils.validateApiSettings();
                if (!validation.isValid) {
                    const error = new Error('API configuration error: ' + validation.missing.join(', '));
                    utils.log('error', error.message);
                    reject(error);
                    return;
                }

                // Prepare message data according to API schema
                const messageData = {
                    role: 'user',
                    parts: [{
                        channel: 'content',
                        type: 'text',
                        text: messageText
                    }],
                    type: 'regular'
                };

                utils.log('info', 'Sending message to API', {
                    endpoint: validation.settings.messages_endpoint,
                    messageLength: messageText.length
                });

                // Make the API request
                self._makeRequest({
                    url: validation.settings.messages_endpoint,
                    method: 'POST',
                    data: JSON.stringify(messageData),
                    headers: {
                        'X-WP-Nonce': validation.settings.nonce,
                        'Content-Type': 'application/json'
                    }
                }, {
                    onSuccess: function(response) {
                        utils.log('info', 'Message sent successfully');

                        // Format the response
                        const formattedResponse = utils.formatMessage(response);
                        if (formattedResponse) {
                            resolve(formattedResponse);
                        } else {
                            utils.log('error', 'Invalid response format', response);
                            reject(new Error('Invalid response format from server'));
                        }
                    },
                    onError: function(error) {
                        utils.log('error', 'API request failed', error);
                        reject(new Error(error));
                    }
                });
            });
        },

        /**
         * Load Message History
         *
         * Retrieves the user's chat history from the server.
         *
         * @since 0.0.5
         * @param {Object} callbacks - Success and error callback functions
         * @param {Function} callbacks.onSuccess - Called with array of messages
         * @param {Function} callbacks.onError - Called if an error occurs
         */
        loadHistory: function(callbacks) {
            const utils = window.AiAssistantChatbotUtils;
            const self = this;

            // Wait for API settings to be available, then proceed
            utils.waitForApiSettings(5000)
                .then(function() {
                    self._loadHistoryWithSettings(callbacks);
                })
                .catch(function(error) {
                    const errorMsg = 'Configuration error: wpApiSettings not loaded. Please refresh the page.';
                    utils.log('error', errorMsg, error);
                    if (callbacks.onError) {
                        callbacks.onError(errorMsg);
                    }
                });
        },

        /**
         * Internal method to load history after API settings are confirmed available
         * @private
         */
        _loadHistoryWithSettings: function(callbacks) {
            const utils = window.AiAssistantChatbotUtils;

            // Validate API settings (should be available now)
            const validation = utils.validateApiSettings();
            if (!validation.isValid) {
                const errorMsg = 'Cannot load history: ' + validation.missing.join(', ');
                utils.log('warn', errorMsg);
                if (callbacks.onError) {
                    callbacks.onError(errorMsg);
                }
                return;
            }

            utils.log('info', 'Loading message history');

            this._makeRequest({
                url: validation.settings.messages_endpoint,
                method: 'GET',
                headers: {
                    'X-WP-Nonce': validation.settings.nonce
                }
            }, {
                onSuccess: function(response) {
                    if (Array.isArray(response)) {
                        utils.log('info', 'Loaded ' + response.length + ' messages from history');

                        // Format all messages
                        const formattedMessages = response
                            .map(utils.formatMessage.bind(utils))
                            .filter(function(msg) { return msg !== null; });

                        if (callbacks.onSuccess) {
                            callbacks.onSuccess(formattedMessages);
                        }
                    } else {
                        utils.log('warn', 'No message history found or invalid format');
                        if (callbacks.onSuccess) {
                            callbacks.onSuccess([]);
                        }
                    }
                },
                onError: function(error) {
                    utils.log('error', 'Failed to load message history', error);
                    if (callbacks.onError) {
                        callbacks.onError(error);
                    }
                }
            });
        },

        /**
         * Clear Message History
         *
         * Clears all chat history for the current user.
         *
         * @since 0.0.5
         * @param {Object} callbacks - Success and error callback functions
         * @param {Function} callbacks.onSuccess - Called when history is cleared
         * @param {Function} callbacks.onError - Called if an error occurs
         */
        clearHistory: function(callbacks) {
            const utils = window.AiAssistantChatbotUtils;
            const self = this;

            // Wait for API settings to be available, then proceed
            utils.waitForApiSettings(5000)
                .then(function() {
                    self._clearHistoryWithSettings(callbacks);
                })
                .catch(function(error) {
                    const errorMsg = 'Configuration error: wpApiSettings not loaded. Please refresh the page.';
                    utils.log('error', errorMsg, error);
                    if (callbacks.onError) {
                        callbacks.onError(errorMsg);
                    }
                });
        },

        /**
         * Internal method to clear history after API settings are confirmed available
         * @private
         */
        _clearHistoryWithSettings: function(callbacks) {
            const utils = window.AiAssistantChatbotUtils;

            // Validate API settings (should be available now)
            const validation = utils.validateApiSettings();
            if (!validation.isValid) {
                const errorMsg = 'Cannot clear history: ' + validation.missing.join(', ');
                utils.log('error', errorMsg);
                if (callbacks.onError) {
                    callbacks.onError(errorMsg);
                }
                return;
            }

            // Build the reset endpoint URL
            const resetUrl = validation.settings.messages_endpoint.replace('/messages', '/messages/reset');

            utils.log('info', 'Clearing message history');

            this._makeRequest({
                url: resetUrl,
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': validation.settings.nonce
                }
            }, {
                onSuccess: function(response) {
                    utils.log('info', 'Message history cleared successfully');
                    if (callbacks.onSuccess) {
                        callbacks.onSuccess(response);
                    }
                },
                onError: function(error) {
                    utils.log('error', 'Failed to clear message history', error);
                    if (callbacks.onError) {
                        callbacks.onError(error);
                    }
                }
            });
        },

        /**
         * Make HTTP Request
         *
         * Generic HTTP request method with retry logic and error handling.
         * Used internally by other API methods.
         *
         * @since 0.0.5
         * @private
         * @param {Object} requestConfig - Request configuration
         * @param {Object} callbacks - Success and error callbacks
         * @param {number} retryCount - Current retry attempt (internal)
         */
        _makeRequest: function(requestConfig, callbacks, retryCount) {
            const self = this;
            const utils = window.AiAssistantChatbotUtils;
            retryCount = retryCount || 0;

            const ajaxConfig = {
                url: requestConfig.url,
                method: requestConfig.method,
                timeout: this._config.timeout,
                headers: requestConfig.headers || {},
                success: function(response) {
                    if (callbacks.onSuccess) {
                        callbacks.onSuccess(response);
                    }
                },
                error: function(xhr, status, error) {
                    const errorDetails = {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    };

                    utils.log('error', 'API request failed', errorDetails);

                    // Retry logic for certain types of errors
                    if (retryCount < self._config.maxRetries && self._shouldRetry(xhr.status)) {
                        utils.log('info', 'Retrying request (attempt ' + (retryCount + 1) + ')');

                        setTimeout(function() {
                            self._makeRequest(requestConfig, callbacks, retryCount + 1);
                        }, self._config.retryDelay);
                        return;
                    }

                    // Final error callback
                    if (callbacks.onError) {
                        callbacks.onError(self._formatError(errorDetails));
                    }
                }
            };

            // Add data if provided
            if (requestConfig.data) {
                ajaxConfig.data = requestConfig.data;
                ajaxConfig.contentType = requestConfig.headers['Content-Type'] || 'application/json';
            }

            // Make the jQuery AJAX request
            $.ajax(ajaxConfig);
        },

        /**
         * Determine if Request Should be Retried
         *
         * @since 0.0.5
         * @private
         * @param {number} statusCode - HTTP status code
         * @returns {boolean} Whether the request should be retried
         */
        _shouldRetry: function(statusCode) {
            // Retry on server errors and timeouts, but not on client errors
            return statusCode >= 500 || statusCode === 0 || statusCode === 408;
        },

        /**
         * Format Error Message
         *
         * @since 0.0.5
         * @private
         * @param {Object} errorDetails - Error details from jQuery AJAX
         * @returns {string} Formatted error message
         */
        _formatError: function(errorDetails) {
            if (errorDetails.status === 0) {
                return 'Network error: Please check your internet connection';
            } else if (errorDetails.status === 403) {
                return 'Permission denied: Please refresh the page and try again';
            } else if (errorDetails.status === 404) {
                return 'Service not found: The chatbot service may be temporarily unavailable';
            } else if (errorDetails.status >= 500) {
                return 'Server error: Please try again in a few moments';
            } else {
                return 'Request failed: ' + (errorDetails.statusText || 'Unknown error');
            }
        }
    };

})(jQuery, window, document);
