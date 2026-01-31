export default function formFields(fields, honeypot, hideFields, prepopulatedData) {
    return {
        submitFields: {},
        fields: [],
        fieldsMap: {},
        honeypot: '',
        TRACKING_COOKIE_PREFIX: 'ef_track_',
        COOKIE_EXPIRY_DAYS: 30,

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
                    // Auto-populate tracking_id: URL first (immediate), then cookie (returning visitors)
                    defaultValue = this.getTrackingId() || field.default || ''
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
         * Get tracking ID from URL params merged with cookies.
         * Returns formatted query string: gclid=abc123&gbraid=xyz456
         */
        getTrackingId() {
            const urlParams = this.getAllTrackingParamsFromUrl()
            const cookieParams = this.getAllTrackingCookies()

            // URL params take precedence, merge with cookies
            const merged = { ...cookieParams, ...urlParams }

            return this.formatTrackingValue(merged)
        },

        /**
         * Get all tracking parameters from URL.
         * Returns object: { gclid: 'abc123', gbraid: 'xyz456' }
         */
        getAllTrackingParamsFromUrl() {
            const trackedParams = this.getTrackedParams()
            const urlParams = new URLSearchParams(window.location.search)
            const result = {}

            for (const [name, value] of urlParams) {
                const normalizedName = name.toLowerCase()
                if (trackedParams.includes(normalizedName)) {
                    // Validate: max 500 chars, alphanumeric with common tracking ID characters
                    if (value.length <= 500 && /^[\w\-_.]+$/.test(value)) {
                        result[normalizedName] = value
                    }
                }
            }
            return result
        },

        /**
         * Get all tracking cookies.
         * Returns object: { gclid: 'abc123', gbraid: 'xyz456' }
         */
        getAllTrackingCookies() {
            const result = {}
            const trackedParams = this.getTrackedParams()

            trackedParams.forEach(paramName => {
                const value = this.getCookie(`${this.TRACKING_COOKIE_PREFIX}${paramName}`)
                if (value) {
                    result[paramName] = value
                }
            })

            return result
        },

        /**
         * Format tracking params as query string: gclid=abc123&gbraid=xyz456
         */
        formatTrackingValue(params) {
            const entries = Object.entries(params)
            if (entries.length === 0) return ''

            return entries
                .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
                .join('&')
        },

        /**
         * Get the list of tracked parameters.
         * Default: gclid, gbraid, wbraid (Google Ads)
         * Custom params can be set via data-track-params attribute on form element
         */
        getTrackedParams() {
            const defaultParams = ['gclid', 'gbraid', 'wbraid']
            const formEl = this.$el?.closest('form')
            const customParams = formEl?.dataset?.trackParams?.split(',').map(p => p.trim().toLowerCase())
            return customParams?.length ? customParams : defaultParams
        },

        /**
         * Capture URL tracking parameters and store each in its own cookie.
         * Cookie names: ef_track_gclid, ef_track_gbraid, etc.
         */
        captureTrackingParams() {
            const urlParams = this.getAllTrackingParamsFromUrl()

            Object.entries(urlParams).forEach(([name, value]) => {
                this.setCookie(`${this.TRACKING_COOKIE_PREFIX}${name}`, value, this.COOKIE_EXPIRY_DAYS)
            })
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