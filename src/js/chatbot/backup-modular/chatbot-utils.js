/**
 * AI Assistant Chatbot - Utility Functions
 *
 * This file contains utility functions used throughout the chatbot system.
 * These are pure functions with no side effects that can be reused across components.
 *
 * @since 0.0.5
 * @package Ai_Assistant
 */

(function($, window, document, undefined) {
    'use strict';

    /**
     * Chatbot Utilities Namespace
     *
     * Contains utility functions for the chatbot system including:
     * - HTML escaping for security
     * - DOM element validation
     * - API settings validation
     * - Message formatting helpers
     */
    window.AiAssistantChatbotUtils = {

        /**
         * Escape HTML to prevent XSS attacks
         *
         * Converts potentially dangerous HTML characters to their safe entities.
         * This is crucial for preventing Cross-Site Scripting (XSS) attacks when
         * displaying user-generated content in the chatbot.
         *
         * @since 0.0.5
         * @param {string} text - The text to escape
         * @returns {string} The escaped text safe for HTML insertion
         *
         * @example
         * const userInput = '<script>alert("xss")</script>';
         * const safeText = AiAssistantChatbotUtils.escapeHtml(userInput);
         * // Returns: '&lt;script&gt;alert("xss")&lt;/script&gt;'
         */
        escapeHtml: function(text) {
            if (!text || typeof text !== 'string') {
                return '';
            }

            const htmlEscapeMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return text.replace(/[&<>"']/g, function(match) {
                return htmlEscapeMap[match];
            });
        },

        /**
         * Validate API Settings
         *
         * Checks if the WordPress API settings are properly loaded and contain
         * all required properties for the chatbot to function correctly.
         *
         * @since 0.0.5
         * @returns {Object} Validation result with isValid boolean and missing array
         *
         * @example
         * const validation = AiAssistantChatbotUtils.validateApiSettings();
         * if (!validation.isValid) {
         *     console.error('Missing API settings:', validation.missing);
         * }
         */
        validateApiSettings: function() {
            const result = {
                isValid: false,
                missing: [],
                settings: null,
                debug: {
                    wpApiSettingsExists: typeof wpApiSettings !== 'undefined',
                    wpApiSettingsType: typeof wpApiSettings,
                    windowKeys: Object.keys(window).filter(key => key.includes('wpApi') || key.includes('ajax'))
                }
            };

            // Check if wpApiSettings exists
            if (typeof wpApiSettings === 'undefined') {
                result.missing.push('wpApiSettings object not loaded');
                this.log('error', 'wpApiSettings validation failed', result.debug);
                return result;
            }

            result.settings = wpApiSettings;

            // Check required properties
            const requiredProps = ['nonce', 'messages_endpoint', 'root'];
            requiredProps.forEach(function(prop) {
                if (!wpApiSettings[prop]) {
                    result.missing.push(prop + ' property');
                }
            });

            result.isValid = result.missing.length === 0;

            if (!result.isValid) {
                this.log('error', 'API settings validation failed', {
                    missing: result.missing,
                    available: Object.keys(wpApiSettings),
                    debug: result.debug
                });
            }

            return result;
        },

        /**
         * Wait for API Settings to be Available
         *
         * Waits for wpApiSettings to become available with timeout.
         * This is useful when modules load before WordPress localization is complete.
         *
         * @since 0.0.7
         * @param {number} timeout - Maximum time to wait in milliseconds (default: 5000)
         * @returns {Promise<boolean>} True if settings become available
         */
        waitForApiSettings: function(timeout) {
            timeout = timeout || 5000;
            const self = this;

            return new Promise(function(resolve, reject) {
                // If already available, resolve immediately
                if (typeof wpApiSettings !== 'undefined') {
                    resolve(true);
                    return;
                }

                let attempts = 0;
                const maxAttempts = timeout / 100; // Check every 100ms

                const checkInterval = setInterval(function() {
                    attempts++;

                    if (typeof wpApiSettings !== 'undefined') {
                        clearInterval(checkInterval);
                        self.log('info', 'wpApiSettings became available after ' + (attempts * 100) + 'ms');
                        resolve(true);
                        return;
                    }

                    if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        self.log('error', 'wpApiSettings timeout after ' + timeout + 'ms');
                        reject(new Error('wpApiSettings not available after timeout'));
                        return;
                    }
                }, 100);
            });
        },

        /**
         * Format Message for Display
         *
         * Formats a message object for display in the chatbot interface.
         * Handles different message types and ensures proper structure.
         *
         * @since 0.0.5
         * @param {Object} message - The message object to format
         * @param {string} message.role - The role ('user' or 'model')
         * @param {Array} message.parts - Array of message parts
         * @param {string} message.type - Message type ('regular', 'error', etc.)
         * @returns {Object} Formatted message ready for display
         */
        formatMessage: function(message) {
            if (!message || typeof message !== 'object') {
                return null;
            }

            const formatted = {
                role: message.role || 'bot',
                type: message.type || 'regular',
                content: '',
                timestamp: new Date()
            };

            // Extract text from message parts
            if (message.parts && Array.isArray(message.parts) && message.parts.length > 0) {
                const firstPart = message.parts[0];
                if (firstPart && firstPart.text) {
                    formatted.content = this.escapeHtml(firstPart.text);
                }
            }

            return formatted;
        },

        /**
         * Generate Unique ID
         *
         * Generates a unique identifier for messages or UI elements.
         * Uses timestamp and random number for uniqueness.
         *
         * @since 0.0.5
         * @param {string} prefix - Optional prefix for the ID
         * @returns {string} Unique identifier
         */
        generateId: function(prefix) {
            const timestamp = Date.now();
            const random = Math.random().toString(36).substr(2, 9);
            return (prefix || 'ai') + '-' + timestamp + '-' + random;
        },

        /**
         * Debounce Function
         *
         * Creates a debounced version of a function that delays execution
         * until after a specified delay has passed since its last invocation.
         * Useful for preventing excessive API calls or UI updates.
         *
         * @since 0.0.5
         * @param {Function} func - The function to debounce
         * @param {number} delay - Delay in milliseconds
         * @returns {Function} Debounced function
         */
        debounce: function(func, delay) {
            let timeoutId;
            return function() {
                const context = this;
                const args = arguments;

                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    func.apply(context, args);
                }, delay);
            };
        },

        /**
         * Scroll Element to Bottom
         *
         * Smoothly scrolls an element to its bottom position.
         * Used for keeping the chat messages view at the latest message.
         *
         * @since 0.0.5
         * @param {jQuery|string} element - jQuery element or selector
         * @param {boolean} smooth - Whether to use smooth scrolling
         */
        scrollToBottom: function(element, smooth) {
            const $element = $(element);
            if ($element.length === 0) {
                return;
            }

            const scrollTop = $element[0].scrollHeight;

            if (smooth && $element[0].scrollTo) {
                $element[0].scrollTo({
                    top: scrollTop,
                    behavior: 'smooth'
                });
            } else {
                $element.scrollTop(scrollTop);
            }
        },

        /**
         * Log Debug Information
         *
         * Centralized logging function with different levels.
         * Can be easily disabled in production or filtered by level.
         *
         * @since 0.0.5
         * @param {string} level - Log level ('info', 'warn', 'error')
         * @param {string} message - Log message
         * @param {*} data - Additional data to log
         */
        log: function(level, message, data) {
            if (!window.AI_ASSISTANT_DEBUG && level === 'info') {
                return;
            }

            const prefix = '[AI Assistant Chatbot]';

            switch (level) {
                case 'error':
                    console.error(prefix, message, data || '');
                    break;
                case 'warn':
                    console.warn(prefix, message, data || '');
                    break;
                case 'info':
                default:
                    console.log(prefix, message, data || '');
                    break;
            }
        }
    };

    // Enable debug mode if URL contains debug parameter
    if (window.location.search.indexOf('ai_debug=1') !== -1) {
        window.AI_ASSISTANT_DEBUG = true;
        AiAssistantChatbotUtils.log('info', 'Debug mode enabled');
    }

})(jQuery, window, document);
