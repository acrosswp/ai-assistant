/**
 * AI Assistant Chatbot - Settings Loader
 *
 * This script ensures wpApiSettings is available immediately when loaded.
 * It should be loaded first before all other chatbot modules.
 */

console.log('🔧 AI Assistant Settings Loader: SCRIPT LOADED');
console.log('🔧 wpApiSettings type:', typeof wpApiSettings);
console.log('🔧 wpApiSettings value:', wpApiSettings);

// Make wpApiSettings available globally immediately
if (typeof window.wpApiSettings === 'undefined' && typeof wpApiSettings !== 'undefined') {
    window.wpApiSettings = wpApiSettings;
    console.log('🔧 wpApiSettings copied to window.wpApiSettings');
}

// Also ensure jQuery is available
if (typeof window.$ === 'undefined' && typeof jQuery !== 'undefined') {
    window.$ = jQuery;
    console.log('🔧 jQuery copied to window.$');
}

console.log('🔧 AI Assistant Settings Loader: wpApiSettings available =', typeof wpApiSettings !== 'undefined');
console.log('🔧 AI Assistant Settings Loader: Final check complete');
