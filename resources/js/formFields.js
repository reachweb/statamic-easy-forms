export default function formFields(fields, honeypot, hideFields, prepopulatedData) {
    return {
        submitFields: {},
        fields: [],
        fieldsMap: {},
        honeypot: '',
        
        init() {
            this.honeypot = honeypot
            this.fields = fields.filter(field => field.input_type !== 'hidden')
                              .filter(field => !hideFields.includes(field.handle))
            this.submitFields = this.initializeFields(fields)
            
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
            this.$watch('submitFields', () => {
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
            if (!window.Statamic?.$conditions?.showField) {
                return true
            }

            return Statamic.$conditions.showField(field, this.submitFields)
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