/**
 * Statamic Easy Forms - Wizard Handler
 * 
 * Alpine.js component for multi-step wizard form functionality.
 * Manages step navigation, validation, and progress tracking.
 * Requires Precognition to be enabled for step validation.
 */
export default function wizardHandler(totalSteps) {
    return {
        currentStep: 1,
        totalSteps: totalSteps,
        stepValidating: false,
        stepFields: {},

        /**
         * Navigate to the next step after validation.
         */
        async goNext() {
            if (this.currentStep >= this.totalSteps || this.stepValidating) return;

            if (await this.validateCurrentStep()) {
                this.currentStep++;
                this.scrollToWizard();
                this.dispatchStepChange();
            }
        },

        /**
         * Navigate to the previous step.
         */
        goPrev() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.scrollToWizard();
                this.dispatchStepChange();
            }
        },

        /**
         * Get the progress percentage.
         */
        getProgressPercent() {
            return Math.round((this.currentStep / this.totalSteps) * 100);
        },

        /**
         * Check if a specific field has errors.
         */
        hasFieldError(field) {
            if (this.precogForm && typeof this.precogForm.invalid === 'function') {
                return this.precogForm.invalid(field);
            }
            const fieldErrors = this.precogForm?.errors?.[field];
            return fieldErrors && (Array.isArray(fieldErrors) ? fieldErrors.length > 0 : true);
        },

        /**
         * Validate current step's fields using Precognition.
         */
        async validateCurrentStep() {
            const fields = Array.from(this.stepFields[this.currentStep] || []);

            if (fields.length === 0) return true;

            if (!this.precogForm) {
                console.warn('Easy Forms Wizard: Precognition is required for wizard validation.');
                return true;
            }

            this.stepValidating = true;

            try {
                // Sync current field values to precognition form
                fields.forEach(field => {
                    if (this.submitData?.[field] !== undefined) {
                        this.precogForm[field] = this.submitData[field];
                    }
                });

                // Trigger validation
                this.precogForm.validate({ only: fields });

                // Wait for validation to start (handling potential debounce)
                let attempts = 0;
                while (!this.precogForm.validating && attempts < 20) {
                    await new Promise(resolve => setTimeout(resolve, 50));
                    attempts++;
                }

                // Wait for validation to complete
                while (this.precogForm.validating) {
                    await new Promise(resolve => setTimeout(resolve, 50));
                }

                // Check for errors after validation
                const hasErrors = fields.some(field => this.hasFieldError(field));
                
                if (hasErrors) {
                    this.scrollToFirstError(fields);
                    return false;
                }
                
                return true;
            } finally {
                this.stepValidating = false;
            }
        },

        /**
         * Scroll to the first error field.
         * If fields param is provided, checks only those fields (step validation).
         * If no param, checks all errors and switches step if needed (form submit).
         */
        scrollToFirstError(fields) {
            // Handle global form submission errors (called from formHandler)
            if (!fields) {
                const errors = this.precogForm?.errors || this.errors || {};
                const errorFields = Object.keys(errors);
                
                if (errorFields.length === 0) return;

                // Find the first step that contains an error
                for (let step = 1; step <= this.totalSteps; step++) {
                    const stepFields = this.stepFields[step] || [];
                    // Check if any of the error fields belong to this step
                    const hasErrorInStep = stepFields.some(field => errorFields.includes(field));
                    if (hasErrorInStep) {
                        this.currentStep = step;
                        this.dispatchStepChange();
                        
                        // Wait for step to become visible then scroll
                        this.$nextTick(() => {
                            this.scrollToFirstError(stepFields);
                        });
                        return;
                    }
                }
                return;
            }

            // Handle specific fields (step validation)
            for (const field of fields) {
                if (this.hasFieldError(field)) {
                    const el = this.$refs.form?.querySelector(`[name="${field}"], #${field}`);
                    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    break;
                }
            }
        },

        /**
         * Scroll to the wizard progress bar.
         */
        scrollToWizard() {
            this.$nextTick(() => {
                const wizard = this.$refs.wizard;
                if (wizard) {
                    const rect = wizard.getBoundingClientRect();
                    if (rect.top < 0 || rect.top > window.innerHeight * 0.3) {
                        wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        },

        /**
         * Dispatch step change event.
         */
        dispatchStepChange() {
            this.$refs.form?.dispatchEvent(new CustomEvent('wizard:step-change', {
                bubbles: true,
                detail: {
                    currentStep: this.currentStep,
                    totalSteps: this.totalSteps,
                    formHandle: this.formHandle
                }
            }));
        }
    }
}
