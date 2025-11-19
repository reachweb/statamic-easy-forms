const RECAPTCHA_SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY

export default function formHandler(eventName = 'formSubmitted') {
    return {
        eventName: eventName,
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
            try {
                this.clearErrors()
                this.toggleSubmit()

                const token = RECAPTCHA_SITE_KEY ? await this.getReCaptchaToken() : null
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
                this.trackAnalytics()
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
            } catch (parseError) {
                // If we can't parse the response, treat it as a fatal error
                this.fatalError = true
            }
        },

        clearErrors() {
            this.errors = {}
            this.fatalError = false
        },

        trackAnalytics() {
            window.dataLayer = window.dataLayer || []
            window.dataLayer.push({
                'custEmail': this.submitData.email,
                'custPhone': this.submitData.phone,
                'FB_Event_ID': window.Cookies.get('XSRF-TOKEN'),
                'event': this.eventName,
            })
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