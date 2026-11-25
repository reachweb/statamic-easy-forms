export default function formHandler(formHandle = 'formSubmitted', formId = null, recaptchaSiteKey = null, precognitionEnabled = false) {
    return {
        formHandle: formHandle,
        formId: formId || formHandle,
        recaptchaSiteKey: recaptchaSiteKey,
        precognitionEnabled: precognitionEnabled,
        submitData: {},
        errors: {},
        fatalError: false,
        sessionExpired: false,
        rateLimited: false,
        disableSubmit: false,
        successMessage: false,
        hasReCaptcha: !!recaptchaSiteKey,
        isSubmitting: false,
        precogForm: null,
        precogInitialized: false,

        init() {
            if (this.recaptchaSiteKey) {
                this.loadReCaptcha();
            }
        },

        /**
         * Initialize Laravel Precognition for real-time validation.
         * Requires the laravel-precognition-alpine plugin to be loaded.
         */
        initPrecognition() {
            if (typeof this.$form !== 'function') {
                console.warn('Easy Forms: Precognition is enabled but laravel-precognition-alpine is not loaded.');
                this.precognitionEnabled = false;
                return;
            }

            this.precogForm = this.$form('post', this.$refs.form.action, this.submitData);
            this.precogForm.setValidationTimeout(300);
            this.precogInitialized = true;

            // Sync precognition errors to our errors object for template display
            this.$watch('precogForm.errors', (newErrors) => {
                this.errors = this.fieldErrors(newErrors);
            });
        },

        /**
         * Update submit data and sync to precognition form.
         * Called when form fields change via the 'fields-changed' event.
         */
        updateSubmitData(data) {
            this.submitData = data;

            if (this.precognitionEnabled && !this.precogInitialized) {
                this.initPrecognition();
            }

            if (this.precogForm) {
                Object.keys(data).forEach(key => {
                    this.precogForm[key] = data[key];
                });
            }
        },

        /**
         * Validate a specific field using precognition.
         * Called when fieldtypes dispatch 'validate-field' events.
         */
        validateField(fieldHandle) {
            if (!this.precognitionEnabled || !this.precogForm) return;
            if (fieldHandle === 'g-recaptcha-response' || fieldHandle === 'recaptcha') return;

            // Skip precognition validation for grid row fields (paths with numeric segments
            // like "grid_field.0.name") because Laravel validation rules use wildcards
            // (grid_field.*.name) which don't match Precognition's exact field matching.
            // Group fields (e.g. "group.field") use the same dot path in both JS and Laravel
            // rules, so they CAN be validated by Precognition.
            if (/\.\d+\./.test(fieldHandle)) {
                return;
            }

            // Sync the latest value before validating (fields-changed is debounced)
            if (this.submitData[fieldHandle] !== undefined) {
                this.precogForm[fieldHandle] = this.submitData[fieldHandle];
            }

            // Force validation using 'only' to bypass change detection
            this.precogForm.validate({ only: [fieldHandle] });
        },

        async formSubmit() {
            // Double-submit prevention
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            // Dispatch submit event
            this.$refs.form.dispatchEvent(new CustomEvent('form:submit', {
                bubbles: true,
                detail: { submitData: this.submitData, formHandle: this.formHandle, formId: this.formId }
            }))

            try {
                this.clearErrors()
                this.toggleSubmit()

                let token = null
                if (this.recaptchaSiteKey) {
                    try {
                        token = await this.getReCaptchaToken()
                    } catch (recaptchaError) {
                        // Handle ReCAPTCHA-specific errors
                        this.fatalError = true
                        this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                            bubbles: true,
                            detail: {
                                error: 'ReCAPTCHA verification failed. Please refresh the page.',
                                fatalError: true,
                                formHandle: this.formHandle,
                                formId: this.formId
                            }
                        }))
                        return
                    }
                }

                const formData = this.buildFormData(token, this.getSubmittableData())

                const response = await fetch(this.$refs.form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                })

                if (!response.ok) {
                    await this.handleErrors(response)
                    return
                }

                const data = await response.json()
                this.handleSuccess(data)
            } catch (error) {
                this.fatalError = true

                // Dispatch error event
                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { error: error.message, fatalError: true, formHandle: this.formHandle, formId: this.formId }
                }))
            } finally {
                // Always reset submission state
                this.toggleSubmit()
                this.isSubmitting = false
            }
        },

        /**
         * Get the formFields component, which keeps the field config and conditions.
         * x-ref="fields" is registered on that component itself, not on this form component.
         */
        getFieldsComponent() {
            const fieldsEl = this.$refs.form.querySelector('[x-ref="fields"]')

            return fieldsEl && window.Alpine?.$data(fieldsEl)
        },

        /**
         * Fields hidden by their conditions are not posted (unless they always save), as Statamic's
         * own form drivers do, so stale values don't reach validation, logic or emails.
         */
        isFieldSubmittable(fields, handle, key) {
            if (!fields?.shouldShowField) return true

            // Fields hidden with the hide_fields parameter still post their (prepopulated) values,
            // whether the parameter names the field or one of its group fields ("address.postcode")
            if ([handle, key].some(name => fields.hideFields.includes(name) || fields.fieldsMap[name]?.always_save)) return true

            return fields.shouldShowField(handle) && (key === handle || fields.shouldShowField(key))
        },

        /**
         * Get the values to post, leaving out fields hidden by their conditions.
         */
        getSubmittableData(data = this.submitData) {
            const fields = this.getFieldsComponent()

            return Object.fromEntries(
                Object.entries(data).filter(([key]) => {
                    // Nested group and grid values ("address.city", "rows.0.name") belong to their field
                    return key.startsWith('_grid_count_') || this.isFieldSubmittable(fields, key.split('.')[0], key)
                })
            )
        },

        buildFormData(token, data = this.submitData) {
            const formData = new FormData()

            Object.entries(data).forEach(([key, value]) => {
                // Skip internal grid count trackers
                if (key.startsWith('_grid_count_')) return

                // Convert dotted notation to bracket notation for nested fields (e.g., group fields)
                // "parent.child" becomes "parent[child]"
                // "grid.0.field" becomes "grid[0][field]"
                const formKey = key.includes('.')
                    ? key.replace(/\.([^.]+)/g, '[$1]')
                    : key

                if (value instanceof FileList) {
                    Array.from(value).forEach(file => formData.append(`${formKey}[]`, file))
                } else if (value instanceof File) {
                    formData.append(formKey, value)
                } else if (Array.isArray(value)) {
                    value.forEach(item => formData.append(`${formKey}[]`, item))
                } else if (value !== null && typeof value === 'object') {
                    // Objects (e.g. date ranges) are posted as "handle[start]", "handle[end]"
                    Object.entries(value).forEach(([subKey, item]) => formData.append(`${formKey}[${subKey}]`, item ?? ''))
                } else if (typeof value === 'boolean') {
                    // FormData would send "false", which Statamic's Toggle casts to true
                    formData.append(formKey, value ? '1' : '0')
                } else if (value !== null && value !== undefined) {
                    formData.append(formKey, value)
                }
            })

            // The honeypot isn't one of the form's fields, so it's only posted when a bot filled it in
            const honeypot = this.$refs.honeypot
            if (honeypot?.value) formData.append(honeypot.name, honeypot.value)

            formData.append('_token', this.$refs.form.dataset.csrfToken)
            if (token) formData.append('g-recaptcha-response', token)

            return formData
        },

        toggleSubmit() {
            this.disableSubmit = !this.disableSubmit
        },

        handleSuccess(data) {
            // Multi-page forms (Statamic Forms Pro) answer every page but the last with the next page to show,
            // and render in page mode (pageHandler.js). A next_page here means the form was posted in a single
            // request, so only its first page was saved and the submission is not complete.
            if (data.next_page) {
                this.fatalError = true;
                // window.console, so the production build (which drops console calls) keeps this message
                window.console.error(`Easy Forms: the "${this.formHandle}" form has multiple pages (Statamic Forms Pro) but was submitted in a single request, so only its first page was saved and the submission was not completed. Render it with a view that supports page mode (republish any Easy Forms views you have published) and clear any cached copies of the page.`);

                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { error: 'The form has multiple pages but was submitted in a single request.', fatalError: true, formHandle: this.formHandle, formId: this.formId }
                }))
                return;
            }

            if (data.success) {
                this.successMessage = true;

                // Reset precognition form if enabled
                if (this.precognitionEnabled && this.precogForm) {
                    this.precogForm.reset();
                }

                // Dispatch success event
                this.$refs.form.dispatchEvent(new CustomEvent('form:success', {
                    bubbles: true,
                    detail: { data: data, submitData: this.submitData, formHandle: this.formHandle, formId: this.formId }
                }))
            }
        },

        async handleErrors(response) {
            try {
                if (response.status === 419) {
                    // CSRF token expired / session expired
                    this.sessionExpired = true
                } else if (response.status === 429) {
                    // Statamic throttles form submissions per IP (every page of a multi-page form counts)
                    this.rateLimited = true
                } else if (response.status === 500) {
                    this.fatalError = true
                } else {
                    const errorData = await response.json()
                    const rawErrors = errorData?.error ?? errorData?.errors ?? {}
                    if (rawErrors && typeof rawErrors === 'object' && !Array.isArray(rawErrors)) {
                        this.errors = this.fieldErrors(rawErrors)
                    } else if (rawErrors) {
                        this.errors = { form: String(rawErrors) }
                    } else {
                        this.errors = {}
                    }

                    // Sync errors to precognition form if enabled
                    if (this.precognitionEnabled && this.precogForm) {
                        this.precogForm.setErrors(this.errors);
                    }
                }

                // Dispatch error event
                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { errors: this.errors, status: response.status, fatalError: this.fatalError, sessionExpired: this.sessionExpired, rateLimited: this.rateLimited, formHandle: this.formHandle, formId: this.formId }
                }))

                // Scroll to first error field if outside of viewport
                this.$nextTick(() => {
                    this.scrollToFirstError()
                })
            } catch (parseError) {
                // If we can't parse the response, treat it as a fatal error
                this.fatalError = true

                // Dispatch error event for parse errors too
                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { errors: {}, fatalError: true, sessionExpired: false, formHandle: this.formHandle, formId: this.formId }
                }))
            }
        },

        /**
         * Key errors by the field that shows them, with one message each (templates use x-text).
         * Laravel returns arrays like {"email": ["Invalid email"]}, and keys errors for single files
         * or options by their index ("photos.0"), which belong to their field ("photos").
         */
        fieldErrors(rawErrors) {
            const errors = {}

            Object.entries(rawErrors).forEach(([key, value]) => {
                const fieldKey = key.replace(/\.\d+$/, '')

                if (!(fieldKey in errors)) {
                    errors[fieldKey] = Array.isArray(value) ? value[0] : value
                }
            })

            return errors
        },

        scrollToFirstError() {
            const errorsToCheck = this.precognitionEnabled && this.precogForm
                ? this.precogForm.errors
                : this.errors;

            if (Object.keys(errorsToCheck).length === 0) return

            const firstErrorHandle = Object.keys(errorsToCheck)[0]

            // Build the form-scoped field ID
            // Convert dotted notation to underscore for nested fields (e.g., "parent.child" -> "parent_child")
            const fieldIdSuffix = firstErrorHandle.replace(/\./g, '_')
            const fieldId = `${this.formId}_${fieldIdSuffix}`

            // Find field by label or fallback to input element
            // (escaped, since form-level error keys such as "*" are not valid selectors)
            const label = this.$refs.form.querySelector(`label[for="${CSS.escape(fieldId)}"]`)
            const fieldElement = label ? label.closest('div') : this.$refs.form.querySelector(`#${CSS.escape(fieldId)}`)

            if (!fieldElement) return

            // Check if element is in viewport
            const rect = fieldElement.getBoundingClientRect()
            const isInViewport = rect.top >= 0 && rect.bottom <= window.innerHeight

            // Only scroll if not in viewport
            if (!isInViewport) {
                fieldElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                })
            }
        },

        clearErrors() {
            this.errors = {}
            this.fatalError = false
            this.sessionExpired = false
            this.rateLimited = false

            // Clear precognition errors if enabled
            if (this.precognitionEnabled && this.precogForm) {
                Object.keys(this.precogForm.errors).forEach(field => {
                    this.precogForm.forgetError(field);
                });
            }
        },

        /**
         * Handle grid row removal by shifting error indices.
         * Errors for rows after the removed index shift down by 1.
         * Errors for the removed row are discarded. Other errors stay intact.
         */
        handleGridRowRemoved({ handle, removedIndex }) {
            const gridPattern = new RegExp(`^${handle}\\.(\\d+)\\.(.+)$`)
            const shifted = {}

            for (const [key, value] of Object.entries(this.errors)) {
                const match = key.match(gridPattern)

                if (!match) {
                    shifted[key] = value
                    continue
                }

                const rowIndex = parseInt(match[1], 10)
                if (rowIndex < removedIndex) {
                    shifted[key] = value
                } else if (rowIndex > removedIndex) {
                    shifted[`${handle}.${rowIndex - 1}.${match[2]}`] = value
                }
                // rowIndex === removedIndex: discard
            }

            this.errors = shifted

            // Clear precognition grid errors (will be re-validated on next interaction)
            if (this.precognitionEnabled && this.precogForm) {
                Object.keys(this.precogForm.errors).forEach(field => {
                    if (field.startsWith(`${handle}.`)) {
                        this.precogForm.forgetError(field)
                    }
                })
            }
        },

        loadReCaptcha() {
            if (!this.recaptchaSiteKey) {
                return;
            }

            if (!document.querySelector('script[src*="recaptcha"]')) {
                const script = document.createElement('script')
                script.src = `https://www.google.com/recaptcha/api.js?render=${this.recaptchaSiteKey}`                
                script.async = true
                script.defer = true
                document.head.appendChild(script)
            }
        },

        getReCaptchaToken() {
            return new Promise((resolve, reject) => {
                if (!this.recaptchaSiteKey) {
                    resolve(null);
                    return;
                }

                // Timeout after 10 seconds
                const timeout = setTimeout(() => {
                    reject(new Error('ReCAPTCHA timeout'));
                }, 10000);

                let retries = 0;
                const maxRetries = 100; // 100 * 100ms = 10 seconds max

                const checkRecaptcha = () => {
                    if (typeof grecaptcha !== 'undefined' && grecaptcha.ready) {
                        grecaptcha.ready(() => {
                            grecaptcha.execute(this.recaptchaSiteKey, { action: 'submit' })
                                .then((token) => {
                                    clearTimeout(timeout);
                                    resolve(token);
                                })
                                .catch((error) => {
                                    clearTimeout(timeout);
                                    reject(error);
                                });
                        });
                    } else if (retries++ < maxRetries) {
                        // Retry after 100ms if grecaptcha not loaded yet
                        setTimeout(checkRecaptcha, 100);
                    } else {
                        clearTimeout(timeout);
                        reject(new Error('ReCAPTCHA failed to load'));
                    }
                };

                checkRecaptcha();
            });
        },
    }
}