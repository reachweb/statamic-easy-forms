export default function formFields(fields, honeypot, hideFields, prepopulatedData) {
    return {
        submitFields: {},
        fields: [],
        fieldsMap: {},
        honeypot: '',
        
        init() {
            this.honeypot = honeypot

            // Capture tracking params from URL and store in cookie
            this.captureTrackingParams()

            this.fields = fields.filter(field => field.input_type !== 'hidden')
                              .filter(field => !hideFields.includes(field.handle))
            this.submitFields = this.initializeFields(fields)
            
            // Create a second map for quick field lookup by handle of all fields
            this.fieldsMap = fields.reduce((acc, field) => {
                acc[field.handle] = field;
                return acc;
            }, {})

            if (prepopulatedData && Object.keys(prepopulatedData).length > 0) {
                this.loadPrepopulatedData(prepopulatedData)
            }

            // Watch for changes and dispatch to parent with debouncing
            let debounceTimer
            
            this.$watch('submitFields', (newValue) => {
                clearTimeout(debounceTimer)
                debounceTimer = setTimeout(() => {
                    this.$dispatch('fields-changed', this.submitFields)
                }, 100)
            }, { deep: true })

            // Initial dispatch
            this.$nextTick(() => {
                this.$dispatch('fields-changed', this.submitFields)
            })
        },

        // Method to check if a field should be shown
        shouldShowField(fieldHandle) {
            const field = this.fieldsMap[fieldHandle]
            if (!field) return false

            // Fallback if Statamic conditions aren't available
            try {
                if (typeof window.Statamic === 'undefined' || !window.Statamic?.$conditions?.showField) {
                    return true
                }
                return window.Statamic.$conditions.showField(field, this.submitFields)
            } catch (error) {
                // If Statamic is not available or throws an error, show all fields
                return true
            }
        },

        initializeFields(fields) {
            return fields.reduce((acc, field) => {
                // Initialize different field types with appropriate defaults
                let defaultValue
                if (field.type === 'assets') {
                    defaultValue = null
                } else if (field.type === 'checkboxes') {
                    defaultValue = field.default || []
                } else if (field.handle === 'tracking_id' && field.input_type === 'hidden') {
                    // Auto-populate tracking_id from stored cookie
                    defaultValue = this.getCookie('ef_tracking_id') || field.default || ''
                } else {
                    defaultValue = field.default || ''
                }
                acc[field.handle] = defaultValue;
                return acc;
            }, {})
        },

        loadPrepopulatedData(data) {
            // Create a new object with the prepopulated data to ensure reactivity
            const updatedFields = { ...this.submitFields }
            Object.keys(data).forEach(key => {
                if (updatedFields.hasOwnProperty(key)) {
                    updatedFields[key] = data[key]
                }
            })

            this.submitFields = updatedFields
        },

        /**
         * Capture URL tracking parameters and store in cookie.
         * Default params: gclid, gbraid, wbraid (Google Ads)
         * Custom params can be set via data-track-params attribute on form element
         */
        captureTrackingParams() {
            const defaultParams = ['gclid', 'gbraid', 'wbraid']

            // Check for custom params on form element
            const formEl = this.$el?.closest('form')
            const customParams = formEl?.dataset?.trackParams?.split(',').map(p => p.trim().toLowerCase())
            const trackedParams = customParams?.length ? customParams : defaultParams

            const urlParams = new URLSearchParams(window.location.search)
            for (const [name, value] of urlParams) {
                if (trackedParams.includes(name.toLowerCase())) {
                    // Validate tracking ID: max 500 chars, alphanumeric with common tracking ID characters
                    if (value.length <= 500 && /^[\w\-_.]+$/.test(value)) {
                        this.setCookie('ef_tracking_id', value, 30)
                    }
                    break
                }
            }
        },

        setCookie(name, value, days) {
            const expires = new Date(Date.now() + days * 864e5).toUTCString()
            const secure = window.location.protocol === 'https:' ? '; Secure' : ''
            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax${secure}`
        },

        getCookie(name) {
            const value = `; ${document.cookie}`
            const parts = value.split(`; ${name}=`)
            if (parts.length === 2) {
                return decodeURIComponent(parts.pop().split(';').shift())
            }
            return null
        },
    }
}