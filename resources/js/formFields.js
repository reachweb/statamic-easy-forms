export default function formFields(fields, honeypot, hideFields, prepopulatedData, precognitionEnabled = false) {
    return {
        submitFields: {},
        previousFields: {},
        fields: [],
        fieldsMap: {},
        honeypot: '',
        precognitionEnabled: precognitionEnabled,
        
        init() {
            this.honeypot = honeypot
            this.fields = fields.filter(field => field.input_type !== 'hidden')
                              .filter(field => !hideFields.includes(field.handle))
            this.submitFields = this.initializeFields(fields)
            this.previousFields = JSON.parse(JSON.stringify(this.submitFields))
            
            // Create a second map for quick field lookup by handle of all fields
            this.fieldsMap = fields.reduce((acc, field) => ({
                ...acc,
                [field.handle]: field
            }), {})

            if (prepopulatedData && Object.keys(prepopulatedData).length > 0) {
                this.loadPrepopulatedData(prepopulatedData)
            }

            // Watch for changes and dispatch to parent with debouncing
            let debounceTimer
            let validationDebounceTimers = {}
            
            this.$watch('submitFields', (newValue) => {
                clearTimeout(debounceTimer)
                debounceTimer = setTimeout(() => {
                    this.$dispatch('fields-changed', this.submitFields)
                }, 100)

                // If precognition is enabled, detect which field changed and validate it
                if (this.precognitionEnabled) {
                    Object.keys(newValue).forEach(key => {
                        const newVal = JSON.stringify(newValue[key])
                        const oldVal = JSON.stringify(this.previousFields[key])
                        
                        if (newVal !== oldVal) {
                            // Debounce validation per field to avoid too many requests
                            clearTimeout(validationDebounceTimers[key])
                            validationDebounceTimers[key] = setTimeout(() => {
                                this.$dispatch('validate-field', key)
                            }, 300)
                        }
                    })
                    
                    // Update previous values
                    this.previousFields = JSON.parse(JSON.stringify(newValue))
                }
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
                } else {
                    defaultValue = field.default || ''
                }
                return {
                    ...acc,
                    [field.handle]: defaultValue
                }
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
    }
}