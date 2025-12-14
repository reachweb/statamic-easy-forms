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
                this.errors = { ...newErrors };
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

                const formData = this.buildFormData(token)

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

        buildFormData(token) {
            const formData = new FormData()

            Object.entries(this.submitData).forEach(([key, value]) => {
                if (value instanceof FileList) {
                    Array.from(value).forEach(file => formData.append(`${key}[]`, file))
                } else if (value instanceof File) {
                    formData.append(key, value)
                } else if (Array.isArray(value)) {
                    value.forEach(item => formData.append(`${key}[]`, item))
                } else if (value !== null && value !== undefined) {
                    formData.append(key, value)
                }
            })

            formData.append('_token', this.$refs.form.dataset.csrfToken)
            if (token) formData.append('g-recaptcha-response', token)

            return formData
        },

        toggleSubmit() {
            this.disableSubmit = !this.disableSubmit
        },

        handleSuccess(data) {
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
                } else if (response.status === 500) {
                    this.fatalError = true
                } else {
                    const errorData = await response.json()
                    this.errors = errorData?.error || errorData?.errors || {}

                    // Sync errors to precognition form if enabled
                    if (this.precognitionEnabled && this.precogForm) {
                        this.precogForm.setErrors(this.errors);
                    }
                }

                // Dispatch error event
                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { errors: this.errors, status: response.status, fatalError: this.fatalError, sessionExpired: this.sessionExpired, formHandle: this.formHandle, formId: this.formId }
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

        scrollToFirstError() {
            const errorsToCheck = this.precognitionEnabled && this.precogForm
                ? this.precogForm.errors
                : this.errors;

            if (Object.keys(errorsToCheck).length === 0) return

            const firstErrorHandle = Object.keys(errorsToCheck)[0]

            // Build the form-scoped field ID
            const fieldId = `${this.formId}_${firstErrorHandle}`

            // Find field by label or fallback to input element
            const label = this.$refs.form.querySelector(`label[for="${fieldId}"]`)
            const fieldElement = label ? label.closest('div') : this.$refs.form.querySelector(`#${fieldId}`)

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

            // Clear precognition errors if enabled
            if (this.precognitionEnabled && this.precogForm) {
                Object.keys(this.precogForm.errors).forEach(field => {
                    this.precogForm.forgetError(field);
                });
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