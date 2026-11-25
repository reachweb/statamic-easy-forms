# Statamic Easy Forms

> Frontend forms for Statamic in 2 minutes. Seriously.

One tag, two minutes, done. No configuration, no styling nightmares, no dependency chaos. Just beautiful, WCAG-compliant forms that work using Tailwind and Alpine.js.

**[Documentation](https://easy-forms.dev)** • **[Demo](https://demo.easy-forms.dev)** • **[Get it at the Statamic Marketplace](https://statamic.com/addons/reach/easy-forms)**

## Why Easy Forms?

We were tired of copy-pasting form templates across every project. You know the drill: create a form in Statamic, spend hours on the frontend template, debug validation, fix mobile layouts, make it accessible... rinse and repeat.

So we made it ridiculously simple: just add the `{{ easyform }}` tag and get a fully-functional form with 18 field types, WCAG 2.1 AA accessibility, built-in security, and TailwindCSS styling. Easy Forms gets you 98% of the way there instantly, then gets out of your way for the final 2%.

## Features

- **One Tag Setup** - `{{ easyform handle="your-form" }}` and you're done
- **20 Field Types** - Text, email, textarea, date picker, grid / repeater, phone, select, radio, checkboxes, toggle, file upload, rating, counter, and more
- **WCAG 2.1 AA Compliant** - Full accessibility with ARIA labels and keyboard navigation
- **Wizard Mode** - Leverage Statamic's form sections to create a wizard-like form. It works like magic!
- **Lightweight** - Only 9KB JavaScript (4KB gzipped), requires just Alpine.js
- **Conditional Fields** - Show/hide fields based on conditions set in Statamic
- **Built-in Security** - CSRF protection, honeypot spam detection, optional reCAPTCHA v3
- **Server-side Validation** - Full support for Statamic's (Laravel's) validation & Laravel Precognition
- **Email Template Included** - Simple, effective template to send form submissions
- **300+ Tests** - Built with Pest for peace of mind
- **Tailwind Styled** - Mobile-first, 12-column grid, easy to customize with themes

## Email Template

Easy Forms includes an HTML email template for form submissions. It lists the answers by section, shows the email's **Message** above them, and links stored uploads to the submission in the control panel.

1. Publish the template with `php artisan easy-forms:install` (or `php artisan vendor:publish --tag=easy-forms-emails`).
2. In the control panel, open your form, go to **Connect → Email**, and add or edit an email.
3. Set **HTML view** to `vendor/statamic-easy-forms/emails/form-submission`. Leave **Text view** empty and keep **Markdown** off.
4. Optionally, write a **Message**. It's shown above the answers, so the same template also works for a confirmation email to the visitor. Emails with a Message leave out the control panel link. With **Attachments** on, the uploaded files are attached and listed at the end.

If your site sets `email_view_folder` in `config/statamic/forms.php`, the control panel only offers views from that folder, so the template is published there as `easy-forms-submission` (for example `emails/easy-forms-submission`). Pick that as the **HTML view** instead.

Don't use the namespaced view `statamic-easy-forms::emails.form-submission`. Emails still send with it, but the control panel can't save or preview the email connection.

Upgrading? Publish the template again with `php artisan vendor:publish --tag=easy-forms-emails --force`. This overwrites any changes you made to your copy. The Easy Forms 2 template doesn't escape the submitted answers, so don't keep using it. With `email_view_folder` set, the new template is published to that folder, so also set your forms' **HTML view** to it.

## Upgrading from 2.x

If you published the form views, publish them again, then bring back your changes:

```bash
php artisan vendor:publish --tag=easy-forms-views --force
```

Views published by Easy Forms 2 render nothing for the new fieldtypes, such as uploads, date pickers and ratings. Until you publish them again, Easy Forms logs a warning.

## License

⚠️ **Commercial addon** - License required for production use. Test freely in local development. Purchase at the [Statamic Marketplace](https://statamic.com/addons/reach/easy-forms)
