export default function formHandler(formHandle = 'formSubmitted', recaptchaSiteKey = null, precognitionEnabled = false) {
    return {
        formHandle: formHandle,
        recaptchaSiteKey: recaptchaSiteKey,
        precognitionEnabled: precognitionEnabled,
        submitData: {},
        errors: {},
        fatalError: false,
        disableSubmit: false,
        successMessage: false,
        hasReCaptcha: !!recaptchaSiteKey,
        isSubmitting: false,
        
        // Precognition-specific properties
        precogForm: null,
        precogInitialized: false,
        validating: false,
        touched: {},

        init() {
            if (this.recaptchaSiteKey) {
                this.loadReCaptcha();
            }
        },

        initPrecognition() {
            // Check if the $form magic is available (Precognition plugin loaded)
            if (typeof this.$form !== 'function') {
                console.warn('Easy Forms: Precognition is enabled but the laravel-precognition-alpine plugin is not loaded. Load easy-forms-precognition.js to enable precognition support.');
                this.precognitionEnabled = false;
                return;
            }

            // Initialize the precognition form with the action URL and initial data
            this.precogForm = this.$form(
                'post',
                this.$refs.form.action,
                this.submitData
            );

            // Set a reasonable debounce timeout for validation
            this.precogForm.setValidationTimeout(300);
            this.precogInitialized = true;

            // Watch precogForm.errors and sync to our errors object
            // This ensures the template's error display works with precognition
            this.$watch('precogForm.errors', (newErrors) => {
                this.errors = { ...newErrors };
            });
        },

        updateSubmitData(data) {
            this.submitData = data;
            
            // Initialize precognition on first data update if enabled
            if (this.precognitionEnabled && !this.precogInitialized) {
                this.initPrecognition();
            }
            
            // Sync data to precognition form if enabled
            if (this.precognitionEnabled && this.precogForm) {
                Object.keys(data).forEach(key => {
                    this.precogForm[key] = data[key];
                });
            }
        },

        // Validate a specific field using precognition
        validateField(fieldHandle) {
            if (!this.precognitionEnabled || !this.precogForm) {
                return;
            }

            // Skip validation for captcha fields
            if (fieldHandle === 'g-recaptcha-response' || fieldHandle === 'recaptcha') {
                return;
            }

            // Mark field as touched
            this.touched[fieldHandle] = true;

            // Trigger precognition validation for this field
            this.precogForm.validate(fieldHandle);
        },

        // Check if a field is valid (precognition)
        isValid(fieldHandle) {
            if (!this.precognitionEnabled || !this.precogForm) {
                return !this.errors[fieldHandle];
            }
            return this.precogForm.valid(fieldHandle);
        },

        // Check if a field is invalid (precognition)
        isInvalid(fieldHandle) {
            if (!this.precognitionEnabled || !this.precogForm) {
                return !!this.errors[fieldHandle];
            }
            return this.precogForm.invalid(fieldHandle);
        },

        // Check if a field has been touched
        isTouched(fieldHandle) {
            return !!this.touched[fieldHandle];
        },

        // Get error message for a field
        getError(fieldHandle) {
            if (this.precognitionEnabled && this.precogForm) {
                return this.precogForm.errors[fieldHandle];
            }
            return this.errors[fieldHandle];
        },

        // Check if form is currently validating (precognition)
        get isValidating() {
            if (this.precognitionEnabled && this.precogForm) {
                return this.precogForm.validating;
            }
            return false;
        },

        // Check if form has any errors
        get hasErrors() {
            if (this.precognitionEnabled && this.precogForm) {
                return this.precogForm.hasErrors;
            }
            return Object.keys(this.errors).length > 0;
        },

        async formSubmit() {
            // Double-submit prevention
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            // Dispatch submit event
            this.$refs.form.dispatchEvent(new CustomEvent('form:submit', {
                bubbles: true,
                detail: { submitData: this.submitData, formHandle: this.formHandle }
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
                                formHandle: this.formHandle
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
                    detail: { error: error.message, fatalError: true, formHandle: this.formHandle }
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
                    this.touched = {};
                }

                // Dispatch success event
                this.$refs.form.dispatchEvent(new CustomEvent('form:success', {
                    bubbles: true,
                    detail: { data: data, submitData: this.submitData, formHandle: this.formHandle }
                }))
            }
        },

        async handleErrors(response) {
            try {
                if (response.status === 500) {
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
                    detail: { errors: this.errors, status: response.status, fatalError: this.fatalError, formHandle: this.formHandle }
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
                    detail: { errors: {}, fatalError: true, formHandle: this.formHandle }
                }))
            }
        },

        scrollToFirstError() {
            const errorsToCheck = this.precognitionEnabled && this.precogForm 
                ? this.precogForm.errors 
                : this.errors;
                
            if (Object.keys(errorsToCheck).length === 0) return

            const firstErrorHandle = Object.keys(errorsToCheck)[0]

            // Find field by label or fallback to input element
            const label = this.$refs.form.querySelector(`label[for="${firstErrorHandle}"]`)
            const fieldElement = label ? label.closest('div') : this.$refs.form.querySelector(`#${firstErrorHandle}`)

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