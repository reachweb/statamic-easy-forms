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

            if (Object.values(prepopulatedData).length > 0) {
                this.loadPrepopulatedData(prepopulatedData)
            }
                        
            // Watch for changes and dispatch to parent
            this.$watch('submitFields', () => {
                this.$dispatch('fields-changed', this.submitFields)
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