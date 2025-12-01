/**
 * Statamic Easy Forms - Frontend JavaScript
 *
 * This file imports and initializes the form handler and field management
 * components for use with Alpine.js in Statamic forms.
 * 
 * For Precognition support, also load easy-forms-precognition.js
 */

// Import CSS
import '../css/frontend.css'

// Import Alpine.js form components
import formHandler from './formHandler.js'
import formFields from './formFields.js'
import wizardHandler from './wizardHandler.js'

// Make components globally available for Alpine.js
// These can be used in templates via x-data="formHandler()" or x-data="formFields(...)"
window.formHandler = formHandler
window.formFields = formFields
window.wizardHandler = wizardHandler

// Export for module usage
export { formHandler, formFields, wizardHandler }