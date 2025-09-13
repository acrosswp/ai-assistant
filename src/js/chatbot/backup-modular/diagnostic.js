/**
 * AI Assistant Chatbot - Diagnostic Helper
 *
 * This is a temporary diagnostic script to help debug loading issues.
 * It can be removed once the chatbot is working properly.
 */

(function() {
    'use strict';

    // Track script loading
    console.log('🔍 AI Assistant Diagnostic: Script loading started');

    // Check if wpApiSettings is available immediately
    console.log('🔍 wpApiSettings available at script load:', typeof wpApiSettings !== 'undefined');

    // Wait for DOM ready and check again
    jQuery(document).ready(function($) {
        console.log('🔍 DOM Ready - wpApiSettings available:', typeof wpApiSettings !== 'undefined');

        if (typeof wpApiSettings !== 'undefined') {
            console.log('🔍 wpApiSettings content:', wpApiSettings);
        }

        // Check module availability
        setTimeout(function() {
            console.log('🔍 Module availability check:');
            console.log('- Utils:', typeof window.AiAssistantChatbotUtils);
            console.log('- API:', typeof window.AiAssistantChatbotAPI);
            console.log('- UI:', typeof window.AiAssistantChatbotUI);
            console.log('- Core:', typeof window.AiAssistantChatbot);
            console.log('- wpApiSettings:', typeof wpApiSettings);

            if (typeof window.AiAssistantChatbotUtils !== 'undefined') {
                const validation = window.AiAssistantChatbotUtils.validateApiSettings();
                console.log('🔍 API Validation Result:', validation);
            }
        }, 500);
    });
})();
