/**
 * Statamic Easy Forms - Page Handler
 *
 * Alpine.js component for Statamic Forms Pro multi-page forms.
 * Statamic processes one page per request: the page is posted with `_page`,
 * saved as a partial submission, and answered with the `next_page` to show.
 * Page logic runs on the server and can skip pages, so the next page always
 * comes from the response. The last page finalizes the submission.
 *
 * Spread after formHandler(), whose formSubmit() and scrollToFirstError() it replaces.
 */
export default function pageHandler(pages) {
    return {
        pages: pages,
        currentPage: pages[0]?.id,
        currentStep: 1,
        totalSteps: pages.length,
        pageHistory: [],
        pagesIncomplete: false,

        /**
         * Submit the current page and show the page the server returns next.
         */
        async formSubmit() {
            // Double-submit prevention
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            const page = this.getPage(this.currentPage);

            // Only the last page can complete the submission
            if (page.is_last) {
                this.$refs.form.dispatchEvent(new CustomEvent('form:submit', {
                    bubbles: true,
                    detail: { submitData: this.submitData, formHandle: this.formHandle, formId: this.formId }
                }));
            }

            try {
                this.clearErrors();
                this.toggleSubmit();
                this.pagesIncomplete = false;

                // reCAPTCHA is verified when the submission is finalized, which only happens on the last page
                let token = null;
                if (this.recaptchaSiteKey && page.is_last) {
                    try {
                        token = await this.getReCaptchaToken();
                    } catch (recaptchaError) {
                        this.fatalError = true;
                        this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                            bubbles: true,
                            detail: {
                                error: 'ReCAPTCHA verification failed. Please refresh the page.',
                                fatalError: true,
                                formHandle: this.formHandle,
                                formId: this.formId
                            }
                        }));
                        return;
                    }
                }

                const formData = this.buildFormData(token, this.getPageSubmitData(page));
                formData.append('_page', page.id);

                const response = await fetch(this.$refs.form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });

                if (!response.ok) {
                    await this.handleErrors(response);
                    return;
                }

                const data = await response.json();

                if (data.next_page) {
                    this.goToPage(data.next_page);
                } else {
                    this.handleSuccess(data);
                }
            } catch (error) {
                this.fatalError = true;

                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { error: error.message, fatalError: true, formHandle: this.formHandle, formId: this.formId }
                }));
            } finally {
                this.toggleSubmit();
                this.isSubmitting = false;
            }
        },

        /**
         * Get the values to post for a page.
         * The server only keeps the posted page's fields, so other pages are left out.
         */
        getPageSubmitData(page) {
            const pageData = Object.fromEntries(
                Object.entries(this.submitData).filter(([key]) => page.fields.includes(key.split('.')[0]))
            );

            // Fields hidden by their conditions are left out, as in formHandler
            return this.getSubmittableData(pageData);
        },

        /**
         * Show the page the server returned after a page was submitted.
         */
        goToPage(id) {
            const index = this.pages.findIndex(page => page.id === id);

            if (index === -1) {
                this.fatalError = true;
                // window.console, so the production build (which drops console calls) keeps this message
                window.console.error(`Easy Forms: the server returned the page "${id}", which the "${this.formHandle}" form does not render. If the form's pages have changed, reload the page (and clear any cached copies of it).`);

                this.$refs.form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { error: 'The server returned a page that the form does not render.', fatalError: true, formHandle: this.formHandle, formId: this.formId }
                }));
                return;
            }

            const currentIndex = this.currentStep - 1;

            if (index < currentIndex) {
                // Statamic sends the visitor back to an earlier page when answers from it are missing,
                // e.g. when the session holding the partial submission expired
                this.pageHistory = this.pageHistory.slice(0, Math.max(this.pageHistory.indexOf(id), 0));
                this.pagesIncomplete = true;
            } else if (index > currentIndex) {
                this.pageHistory.push(this.currentPage);
            }
            // A page rule that leads to its own page shows the same page again, keeping the history

            this.showPage(id);
        },

        /**
         * Navigate to the previously visited page.
         * Its answers are already saved on the server, so no request is needed.
         */
        goBack() {
            if (!this.canGoBack() || this.isSubmitting) return;

            this.clearErrors();
            this.pagesIncomplete = false;
            this.showPage(this.pageHistory.pop());
        },

        /**
         * Check if the current page can go back (the previous button is enabled per page in the form builder).
         */
        canGoBack() {
            return this.pageHistory.length > 0 && !!this.getPage(this.currentPage)?.show_previous;
        },

        showPage(id) {
            this.currentPage = id;
            this.currentStep = this.pages.findIndex(page => page.id === id) + 1;
            this.scrollToProgress();
            this.focusCurrentPage();
            this.dispatchStepChange();
        },

        getPage(id) {
            return this.pages.find(page => page.id === id);
        },

        getPageElement(id) {
            return this.$refs.form?.querySelector(`[data-page="${CSS.escape(id)}"]`);
        },

        /**
         * Get the progress percentage.
         * Based on the page's position in the form, so skipping pages moves the progress ahead.
         */
        getProgressPercent() {
            return Math.round((this.currentStep / this.totalSteps) * 100);
        },

        /**
         * Move focus to the new page's heading (or to the page itself when it has none),
         * so keyboard and screen reader users continue from the top of the page.
         */
        focusCurrentPage() {
            this.$nextTick(() => {
                const page = this.getPageElement(this.currentPage);
                const target = page?.querySelector('[data-page-heading]') ?? page;
                target?.focus({ preventScroll: true });
            });
        },

        /**
         * Focus the first field with an error on the current page.
         * Form-level errors (e.g. reCAPTCHA) are announced by their alert instead.
         */
        scrollToFirstError() {
            const page = this.getPageElement(this.currentPage);
            if (!page) return;

            for (const key of Object.keys(this.errors)) {
                const id = `${this.formId}_${key.replace(/\./g, '_')}`;
                const name = key.replace(/\.([^.]+)/g, '[$1]');
                const field = page.querySelector(`#${CSS.escape(id)}, [name="${name}"], [name="${name}[]"]`);

                if (field) {
                    field.focus({ preventScroll: true });
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
            }
        },

        /**
         * Scroll to the progress indicator.
         */
        scrollToProgress() {
            this.$nextTick(() => {
                const progress = this.$refs.progress;
                if (progress) {
                    const rect = progress.getBoundingClientRect();
                    if (rect.top < 0 || rect.top > window.innerHeight * 0.3) {
                        progress.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        },

        /**
         * Dispatch step change event (the same event as wizard mode, plus the page id).
         */
        dispatchStepChange() {
            this.$refs.form?.dispatchEvent(new CustomEvent('wizard:step-change', {
                bubbles: true,
                detail: {
                    currentStep: this.currentStep,
                    totalSteps: this.totalSteps,
                    page: this.currentPage,
                    formHandle: this.formHandle,
                    formId: this.formId
                }
            }));
        }
    }
}
