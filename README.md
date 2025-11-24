# Statamic Easy Forms

> Frontend forms for Statamic in 2 minutes. Seriously.

One tag, two minutes, done. No configuration, no styling nightmares, no dependency chaos. Just beautiful, WCAG-compliant forms that work using Tailwind and Alpine.js.

**[Documentation](https://easy-forms.dev)** • **[Demo](https://demo.easy-forms.dev)** • **[Get it at the Statamic Marketplace](https://statamic.com/addons/reach/easy-forms)**

## Why Easy Forms?

We were tired of copy-pasting form templates across every project. You know the drill: create a form in Statamic, spend hours on the frontend template, debug validation, fix mobile layouts, make it accessible... rinse and repeat.

So we made it ridiculously simple: just add the `{{ easyform }}` tag and get a fully-functional form with 18 field types, WCAG 2.1 AA accessibility, built-in security, and TailwindCSS styling. Easy Forms gets you 98% of the way there instantly, then gets out of your way for the final 2%.

## Features

- **One Tag Setup** - `{{ easyform handle="your-form" }}` and you're done
- **18 Field Types** - Text, email, textarea, date picker, phone, select, radio, checkboxes, toggle, file upload, rating, counter, and more
- **WCAG 2.1 AA Compliant** - Full accessibility with ARIA labels and keyboard navigation
- **Lightweight** - Only 5KB JavaScript (2KB gzipped), requires just Alpine.js
- **Conditional Fields** - Show/hide fields based on conditions set in Statamic
- **Built-in Security** - CSRF protection, honeypot spam detection, optional reCAPTCHA v3
- **Server-side Validation** - Full support for Statamic's (Laravel's) validation
- **Email Template Included** - Simple, effective template to send form submissions
- **200+ Tests** - Built with Pest for peace of mind
- **Tailwind Styled** - Mobile-first, 12-column grid, easy to customize with themes

## License

⚠️ **Commercial addon** - License required for production use. Test freely in local development. Purchase at the [Statamic Marketplace](https://statamic.com/addons/reachweb/statamic-easy-forms)
