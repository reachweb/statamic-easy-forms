export default function formFields(fields, honeypot, hideFields, prepopulatedData, formId) {
    return {
        submitFields: {},
        fields: [],
        fieldsMap: {},
        formId: formId || '',
        honeypot: '',
        hideFields: [],
        TRACKING_COOKIE_PREFIX: 'ef_track_',
        COOKIE_EXPIRY_DAYS: 30,

        init() {
            this.honeypot = honeypot
            this.hideFields = hideFields || []

            // Capture tracking params from URL and store in cookie
            this.captureTrackingParams()

            this.fields = fields.filter(field => field.input_type !== 'hidden')
                              .filter(field => !hideFields.includes(field.handle))
            this.submitFields = this.initializeFields(fields)
            
            // Create a second map for quick field lookup by handle of all fields
            this.fieldsMap = fields.reduce((acc, field) => {
                acc[field.handle] = field;
                // Also add nested fields from groups with their full key
                if (field.type === 'group' && field.group_fields) {
                    field.group_fields.forEach(nestedField => {
                        acc[nestedField.field_key] = nestedField;
                    })
                }
                // Also add nested fields from grids (template fields for reference)
                if (field.type === 'grid' && field.grid_fields) {
                    field.grid_fields.forEach(nestedField => {
                        // Store template field for reference (uses __INDEX__ placeholder)
                        acc[`${field.handle}._template_.${nestedField.handle}`] = nestedField;
                    })
                }
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

        // Method to check if a field should be shown based on Statamic conditions
        shouldShowField(fieldHandle) {
            let field = this.fieldsMap[fieldHandle]

            // For grid fields, convert indexed key (grid.0.field) to template key (grid._template_.field)
            if (!field) {
                const templateKey = fieldHandle.replace(/\.(\d+)\./, '._template_.')
                field = this.fieldsMap[templateKey]
            }

            if (!field) return false

            // Check if field is in the hideFields array (from hide_fields tag parameter)
            if (this.hideFields.includes(fieldHandle)) {
                return false
            }

            // If no conditions defined, show the field
            if (!field.if && !field.unless && !field.show_when && !field.hide_when) {
                return true
            }

            try {
                if (typeof window.Statamic === 'undefined' || !window.Statamic?.$conditions?.showField) {
                    return true
                }
                return window.Statamic.$conditions.showField(field, this.submitFields, fieldHandle)
            } catch (error) {
                // If Statamic conditions fail, show the field
                return true
            }
        },

        initializeFields(fields) {
            return fields.reduce((acc, field) => {
                // Handle group fields - initialize nested fields with dot notation
                if (field.type === 'group' && field.group_fields) {
                    field.group_fields.forEach(nestedField => {
                        const nestedHandle = `${field.handle}.${nestedField.handle}`
                        acc[nestedHandle] = this.getFieldDefaultValue(nestedField)
                    })
                    return acc
                }

                // Handle grid fields - initialize with flat indexed keys
                if (field.type === 'grid' && field.grid_fields) {
                    let rowCount
                    if (field.dynamic_rows_field) {
                        const controlValue = parseInt(acc[field.dynamic_rows_field]) || 0
                        rowCount = Math.max(controlValue, field.min_rows || 0)
                        if (field.max_rows) {
                            rowCount = Math.min(rowCount, field.max_rows)
                        }
                    } else {
                        rowCount = field.fixed_rows || field.min_rows || 1
                    }
                    // Initialize flat state with indexed keys for each row
                    for (let i = 0; i < rowCount; i++) {
                        field.grid_fields.forEach(nestedField => {
                            acc[`${field.handle}.${i}.${nestedField.handle}`] = this.getFieldDefaultValue(nestedField)
                        })
                    }
                    // Track row count for this grid
                    acc[`_grid_count_${field.handle}`] = rowCount
                    return acc
                }

                // Initialize different field types with appropriate defaults
                acc[field.handle] = this.getFieldDefaultValue(field)
                return acc
            }, {})
        },

        getFieldDefaultValue(field) {
            if (field.type === 'assets') {
                return null
            } else if (field.type === 'toggle') {
                return field.default ?? false
            } else if (field.type === 'checkboxes') {
                return field.default || []
            } else if (field.handle === 'tracking_id' && field.input_type === 'hidden') {
                // Auto-populate tracking_id: URL first (immediate), then cookie (returning visitors)
                return this.getTrackingId() || field.default || ''
            } else {
                return field.default || ''
            }
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
                    // Validate: max 200 chars, alphanumeric with common tracking ID characters
                    if (value.length <= 200 && /^[\w\-_.]+$/.test(value)) {
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

        // Grid field methods

        /**
         * Add a row to a grid field.
         */
        addGridRow(handle) {
            const field = this.fieldsMap[handle]
            if (!field?.grid_fields) return

            const countKey = `_grid_count_${handle}`
            const currentCount = this.submitFields[countKey] || 0

            // Check max_rows limit
            if (field.max_rows && currentCount >= field.max_rows) return

            // Add new row fields to state
            field.grid_fields.forEach(f => {
                this.submitFields[`${handle}.${currentCount}.${f.handle}`] = this.getFieldDefaultValue(f)
            })
            this.submitFields[countKey] = currentCount + 1

            // Clone template row in DOM
            this.cloneGridRow(handle, currentCount, true)
        },

        /**
         * Remove a row from a grid field.
         */
        removeGridRow(handle, index) {
            const field = this.fieldsMap[handle]
            if (!field?.grid_fields) return

            const countKey = `_grid_count_${handle}`
            const currentCount = this.submitFields[countKey] || 0
            index = parseInt(index)

            // Check min_rows limit
            if (field.min_rows && currentCount <= field.min_rows) return

            const gridId = this.formId ? `${this.formId}_${handle}` : handle
            const container = document.querySelector(`[data-grid-rows="${gridId}"]`)
            const row = container?.querySelector(`[data-grid-row="${index}"]`)

            const cleanup = () => {
                // Remove row fields from state
                field.grid_fields.forEach(f => {
                    delete this.submitFields[`${handle}.${index}.${f.handle}`]
                })

                // Shift remaining rows down
                for (let i = index + 1; i < currentCount; i++) {
                    field.grid_fields.forEach(f => {
                        const oldKey = `${handle}.${i}.${f.handle}`
                        const newKey = `${handle}.${i - 1}.${f.handle}`
                        this.submitFields[newKey] = this.submitFields[oldKey]
                        delete this.submitFields[oldKey]
                    })
                }

                this.submitFields[countKey] = currentCount - 1

                // Shift errors in the parent formHandler component
                this.$dispatch('grid-row-removed', { handle, removedIndex: index })

                // Rebuild all DOM rows so Alpine creates fresh, correct bindings
                this.rebuildGridRows(handle)
            }

            if (row) {
                this.animateGridRowOut(row, cleanup)
            } else {
                cleanup()
            }
        },

        /**
         * Check if more rows can be added to a grid.
         */
        canAddGridRow(handle) {
            const field = this.fieldsMap[handle]
            if (!field?.grid_fields || field.is_fixed) return false
            if (!field.max_rows) return true
            const currentCount = this.submitFields[`_grid_count_${handle}`] || 0
            return currentCount < field.max_rows
        },

        /**
         * Check if rows can be removed from a grid.
         */
        canRemoveGridRow(handle) {
            const field = this.fieldsMap[handle]
            if (!field?.grid_fields || field.is_fixed) return false
            const minRows = field.min_rows || 1
            const currentCount = this.submitFields[`_grid_count_${handle}`] || 0
            return currentCount > minRows
        },

        /**
         * Clone template row in DOM and replace __INDEX__ placeholders.
         */
        cloneGridRow(handle, index, animate = false) {
            const gridId = this.formId ? `${this.formId}_${handle}` : handle
            const template = document.querySelector(`[data-grid-template="${gridId}"]`)
            const container = document.querySelector(`[data-grid-rows="${gridId}"]`)
            if (!template || !container) return

            const clone = template.content.cloneNode(true)
            const row = clone.firstElementChild

            // Replace __INDEX__ with actual index in all relevant attributes
            const replaceIndex = (str) => str.replace(/__INDEX__/g, index)

            // Replace __INDEX__ in all attributes, including inside nested <template> elements
            const processElement = (root) => {
                // Replace in attributes of root (no-op for DocumentFragment which has no attributes)
                // and all descendant elements
                const elements = [root, ...root.querySelectorAll('*')]
                for (const el of elements) {
                    for (const attr of Array.from(el.attributes || [])) {
                        if (attr.value.includes('__INDEX__')) {
                            el.setAttribute(attr.name, replaceIndex(attr.value))
                        }
                    }
                    // Recurse into nested <template> content (e.g., x-for templates)
                    if (el.tagName === 'TEMPLATE' && el.content) {
                        processElement(el.content)
                    }
                }
            }

            processElement(row)

            // Update row number display
            const rowNumber = row.querySelector('.row-number')
            if (rowNumber) {
                rowNumber.textContent = index + 1
            }

            row.setAttribute('data-grid-row', index)
            if (animate) {
                row.classList.add('ef-row-enter')
                row.addEventListener('animationend', () => row.classList.remove('ef-row-enter'), { once: true })
            }
            container.appendChild(row)
        },

        /**
         * Rebuild all grid rows from scratch.
         * This ensures Alpine creates fresh bindings with the correct indices,
         * avoiding stale bindings that occur when DOM attributes are updated
         * on already-initialized Alpine components.
         */
        rebuildGridRows(handle) {
            const gridId = this.formId ? `${this.formId}_${handle}` : handle
            const container = document.querySelector(`[data-grid-rows="${gridId}"]`)
            if (!container) return

            // Remove all existing rows (Alpine auto-cleans up via MutationObserver)
            container.innerHTML = ''

            // Re-clone all rows from template with correct indices
            const count = this.submitFields[`_grid_count_${handle}`] || 0
            for (let i = 0; i < count; i++) {
                this.cloneGridRow(handle, i)
            }
        },

        /**
         * Set the row count for a grid field, adding or removing rows as needed.
         */
        setGridRowCount(handle, newCount) {
            const field = this.fieldsMap[handle]
            if (!field?.grid_fields) return

            let count = Math.max(parseInt(newCount) || 0, field.min_rows || 0)
            if (field.max_rows) {
                count = Math.min(count, field.max_rows)
            }

            const countKey = `_grid_count_${handle}`
            const currentCount = this.submitFields[countKey] || 0
            if (count === currentCount) return

            const gridId = this.formId ? `${this.formId}_${handle}` : handle
            const container = document.querySelector(`[data-grid-rows="${gridId}"]`)

            // Clean up any rows still animating out from a previous change
            if (container) {
                container.querySelectorAll('.ef-row-exit').forEach(row => row.remove())
            }

            if (count > currentCount) {
                for (let i = currentCount; i < count; i++) {
                    field.grid_fields.forEach(f => {
                        this.submitFields[`${handle}.${i}.${f.handle}`] = this.getFieldDefaultValue(f)
                    })
                    this.cloneGridRow(handle, i, true)
                }
            } else {
                for (let i = currentCount - 1; i >= count; i--) {
                    field.grid_fields.forEach(f => {
                        delete this.submitFields[`${handle}.${i}.${f.handle}`]
                    })
                    const row = container?.querySelector(`[data-grid-row="${i}"]`)
                    if (row) {
                        this.animateGridRowOut(row)
                    }
                }
            }

            this.submitFields[countKey] = count
        },

        /**
         * Animate a grid row out: fade + translate, then collapse height.
         */
        animateGridRowOut(row, onComplete) {
            row.classList.add('ef-row-exit')
            row.addEventListener('animationend', () => {
                // Phase 2: smoothly collapse the space
                row.style.height = row.offsetHeight + 'px'
                row.style.overflow = 'hidden'
                row.offsetHeight // force reflow
                row.style.transition = 'height .15s ease-in, margin .15s ease-in'
                row.style.height = '0'
                row.style.marginBottom = '0'
                row.addEventListener('transitionend', () => {
                    row.remove()
                    if (onComplete) onComplete()
                }, { once: true })
            }, { once: true })
        },

        /**
         * Initialize dynamic grid rows that react to another field's value.
         */
        initDynamicGridRows(handle, controlFieldHandle) {
            this.$watch(
                () => this.submitFields[controlFieldHandle],
                (newValue) => this.setGridRowCount(handle, newValue)
            )
        },
    }
}