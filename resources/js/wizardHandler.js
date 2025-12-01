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
        stepFields: {}, // { stepNumber: ['field1', 'field2'] }

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
         * Validate current step's fields using Precognition.
         */
        async validateCurrentStep() {
            const fields = this.stepFields[this.currentStep] || [];
            
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

                try {
                    await this.precogForm.validate({ only: fields });
                } catch {
                    // Validation request failed/rejected - errors should now be in precogForm.errors
                }

                // Check for errors after validation (whether resolved or rejected)
                const hasErrors = fields.some(field => {
                    const fieldErrors = this.precogForm.errors?.[field];
                    return fieldErrors && (Array.isArray(fieldErrors) ? fieldErrors.length > 0 : true);
                });

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
         * Scroll to the first error field in the current step.
         */
        scrollToFirstError(fields) {
            const errors = this.precogForm?.errors || {};
            
            for (const field of fields) {
                if (errors[field]) {
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
