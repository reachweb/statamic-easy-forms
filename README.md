# Statamic Easy Forms

> Statamic Easy Forms is an addon for the Statamic CMS that simplifies form creation and rendering with automatically styled, interactive form templates. Create beautiful, accessible forms with minimal code using the `{{ easyform }}` tag.

## Documentation & Examples

You can find the documentation and **examples** here: https://easy-forms.dev

## Commercial addon

⚠️ **License Required**: This is commercial software available for purchase. While you're welcome to test it freely in your local development environment, a valid license from the [Statamic Marketplace](https://statamic.com/addons/reachweb/statamic-easy-forms) is required for production use.

## Features

### Core Functionality

- **Simple Tag-Based Forms** - Render complete forms with a single `{{ easyform handle="your-form" }}` tag
- **17 Field Types** - Comprehensive support for all of Statamic's frontend form inputs plus some more
- **Section Support** - Respects Statamic sections and displays sections' titles and descriptions
- **No Dependencies** - All that is required is Alpine.js and its focus plugin. Everything else works with a 3KB (1KB gzipped) JavaScript file
- **Full Accessibility** - WCAG 2.1 Level AA compliant with ARIA labels, keyboard navigation, and screen reader support
- **Simple Design** - Mobile-first 12-column grid layout with Tailwind CSS, easy to override using themes
- **Ready to Send Emails** - Contains a minimal email template to send notifications with the data received
- **Built-in Security** - CSRF protection, honeypot spam detection, and optional reCAPTCHA v3

#### Basic Input Fields
- **Text Input** - Standard text field supporting multiple input types:
  - Plain text
  - Email with validation
  - Basic telephone input
  - URL input
- **Textarea** - Multi-line text input with adjustable height
- **Time Input** - Time picker with HH:MM format mask

#### Enhanced Interactive Fields
- **Advanced Date Picker** - Full-featured calendar with:
  - Single date or date range selection
  - Min/max date constraints (static or relative to today)
  - Maximum range limits
  - Month/year navigation and year picker

- **International Phone Number Field** - Smart telephone input with:
  - 240+ country codes with dial codes
  - Searchable country selector combobox
  - Prefix protection and validation

#### Numeric Input Fields
- **Integer Fields** - Three presentation styles:
  - Simple numeric input with min/max validation
  - Counter with increment/decrement buttons and configurable step amounts
  - Star rating system with customizable range (1-5 stars or 0-10 points)

#### Selection & Choice Fields
- **Select Dropdowns** - Two variants available:
  - Standard native `<select>` element for simple use cases
  - Improved combobox with searchable/filterable options, custom styling, and enhanced keyboard navigation
- **Radio Button Groups** - Two presentation styles:
  - Standard grid layout with traditional radio buttons
  - Improved button-style variant with visual selection states and smooth transitions
- **Checkbox Groups** - Multi-select with responsive grid layout and clear visual states
- **Toggle Switches** - Accessible on/off switches with proper `role="switch"` semantics
- **Dictionary Fields** - Searchable dropdown with:
  - Filterable options
  - Keyboard navigation
  - Custom combobox pattern for enhanced accessibility

#### File Upload
- **Advanced File Upload Component** - Modern drag-and-drop interface with:
  - Multiple file support (configurable max)
  - Individual file removal
  - File size display and formatting
  - Visual upload zone with hover states

#### Utility Fields
- **Spacer** - Visual separator field for organizing form sections with customizable content and styling
