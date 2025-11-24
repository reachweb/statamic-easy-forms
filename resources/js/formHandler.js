const RECAPTCHA_SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY

export default function formHandler(formHandle = 'formSubmitted') {
    return {
        formHandle: formHandle,
        submitData: {},
        errors: {},
        fatalError: false,
        disableSubmit: false,
        successMessage: false,
        hasReCaptcha: !!RECAPTCHA_SITE_KEY,

        init() {
            if (RECAPTCHA_SITE_KEY) {
                this.loadReCaptcha();
            }
        },

        updateSubmitData(data) {
            this.submitData = data;
        },

        async formSubmit() {
            // Dispatch submit event
            this.$refs.form.dispatchEvent(new CustomEvent('form:submit', {
                bubbles: true,
                detail: { submitData: this.submitData, formHandle: this.formHandle }
            }))

            try {
                this.clearErrors()
                this.toggleSubmit()

                let token = null
                if (RECAPTCHA_SITE_KEY) {
                    try {
                        token = await this.getReCaptchaToken()
                    } catch (recaptchaError) {
                        // Handle ReCAPTCHA-specific errors
                        this.fatalError = true
                        this.toggleSubmit()
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
                    this.toggleSubmit()
                    return
                }

                const data = await response.json()
                this.handleSuccess(data)
            } catch (error) {
                this.fatalError = true
                this.toggleSubmit()

                // Dispatch error event
                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { error: error.message, fatalError: true, formHandle: this.formHandle }
                }))
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
            if (Object.keys(this.errors).length === 0) return

            const firstErrorHandle = Object.keys(this.errors)[0]

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
        },

        loadReCaptcha() {
            if (!RECAPTCHA_SITE_KEY) {
                return;
            }
            
            if (!document.querySelector('script[src*="recaptcha"]')) {
                const script = document.createElement('script')
                script.src = `https://www.google.com/recaptcha/api.js?render=${RECAPTCHA_SITE_KEY}`                
                script.async = true
                script.defer = true
                document.head.appendChild(script)
            }
        },

        getReCaptchaToken() {
            return new Promise((resolve, reject) => {
                if (!RECAPTCHA_SITE_KEY) {
                    resolve(null);
                    return;
                }
                
                if (typeof grecaptcha === 'undefined') {
                    reject(new Error('ReCaptcha not loaded'));
                    return;
                }
                grecaptcha.ready(() => {
                    grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'submit' })
                        .then(resolve)
                        .catch(reject)
                })
            })
        },
    }
}