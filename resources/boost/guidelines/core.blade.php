## Easy Forms

Statamic Easy Forms renders WCAG 2.1 AA compliant frontend forms using a single Antlers tag. It uses Alpine.js 3 (with Focus plugin) and Tailwind CSS v4.

Full documentation: https://easy-forms.dev/llms-full.txt

### Basic Usage

Render a form in any Antlers template:

@verbatim
<code-snippet name="Basic form" lang="antlers">
{{ easyform handle="contact" }}
</code-snippet>
@endverbatim

This outputs a complete `<form>` element with all fields defined in the Statamic blueprint, including labels, validation, error messages, and CSRF protection.

### Tag Parameters

- `handle` (required): The Statamic form handle.
- `instance`: Unique ID for multiple instances of the same form on a page.
- `view`: Custom view template (default: `form/_form_component`).
- `hide_fields`: Pipe-separated field handles to hide, e.g. `hide_fields="field1|field2"`.
- `prepopulated_data`: Array of values to prepopulate fields.
- `submit_text`: Custom submit button text.
- `success_message`: Custom success message after submission.
- `precognition`: Enable Laravel Precognition for real-time validation (`true`/`false`).
- `wizard`: Enable multi-step mode using form blueprint sections (`true`/`false`).

### Supported Field Types

text, textarea, select, radio, checkboxes, date, time, telephone (240+ countries), toggle, integer, integer (rating), integer (counter), assets, files, spacer, dictionary, grid, group.

### Features

- **Wizard Mode**: Split forms into steps using blueprint sections. Enable with `wizard="true"`. Each section becomes a step with automatic navigation.
- **Real-time Validation**: Enable with `precognition="true"`. Requires the precognition JS bundle.
- **Conditional Fields**: Uses Statamic's built-in conditional logic to show/hide fields dynamically.
- **Honeypot Spam Protection**: Built-in honeypot field for spam prevention.
- **reCAPTCHA v3**: Optional integration via the `ValidateRecaptcha` listener on Statamic's `FormSubmitted` event.
- **Multiple Instances**: Use the `instance` parameter when placing the same form multiple times on a page.

### Examples

@verbatim
<code-snippet name="Wizard form with custom submit text" lang="antlers">
{{ easyform handle="registration" wizard="true" submit_text="Complete Registration" }}
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Form with real-time validation and hidden fields" lang="antlers">
{{ easyform handle="survey" precognition="true" hide_fields="internal_ref|tracking_id" }}
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Multiple instances on same page" lang="antlers">
{{ easyform handle="newsletter" instance="sidebar" }}
{{ easyform handle="newsletter" instance="footer" }}
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Prepopulated data" lang="antlers">
{{ easyform handle="profile" :prepopulated_data="user_data" }}
</code-snippet>
@endverbatim

### File Structure

- `src/Tags/EasyForm.php` — Main `@{{ easyform }}` tag handler.
- `src/Tags/Concerns/` — Trait composition: `HandlesForms`, `HandlesFields`, `HandlesDictionaries`, `HasVersion`.
- `src/Listeners/ValidateRecaptcha.php` — Optional reCAPTCHA v3 listener.
- `resources/views/form/fieldtypes/` — 18 field type Antlers templates.
- `resources/views/form/wizard/` — Wizard/multi-step templates.
- `resources/js/` — Alpine components: `formHandler.js`, `formFields.js`, `wizardHandler.js`, `precognition.js`.

### Namespace

PHP namespace: `Reach\StatamicEasyForms`
