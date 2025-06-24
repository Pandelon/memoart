# Views Vanilla JavaScript Slideshow (VVJS)

## Description

The **Views Vanilla JavaScript Slideshow** (VVJS) module allows you to create 
dynamic and visually appealing slideshows for displaying various content items 
on your Drupal site. This module integrates seamlessly with the Views module, 
providing a new display style that you can choose when creating or editing 
views. VVJS is lightweight and does not rely on jQuery, making it an efficient 
choice for modern web development.

## Features

- Utilizes vanilla JavaScript for improved performance and reduced dependencies.
- Customizable options for time interval between slides, navigation type, 
  animation type, and arrows.
- Unique ID generation to prevent conflicts.
- Includes accessibility features such as ARIA roles and properties for better 
  screen reader support.

## Token Support in Views Text Areas

In Views headers, footers, or empty text areas—when using *Global: Text area* or
*Global: Unfiltered text*—there is an option called **"Use replacement tokens
from the first row."**

The default Twig-style tokens (e.g., `{{ title }}` or `{{ field_image }}`)
**will not work** with the VVJS style. Instead, use the custom tokens provided
by VVJS:

**Examples:**

- `{{ title }}` → `[vvjs:title]`  
- `{{ field_image }}` → `[vvjs:field_image]`

To strip any HTML from the output, you can append `:plain` to the token:

- `[vvjs:title:plain]`

These tokens pull data from the **first row** of the View result and are
designed to work seamlessly with the VVJS rendering system.

## Requirements

- Drupal 10
- Views module

## Installation

1. Download and enable the **VVJS** module.
2. Clear the cache: `drush cr` or via the Drupal admin interface.

## Usage

1. Create or edit a view in the Views module.
2. In the **Format** section, select **Views Vanilla JavaScript Slideshow** 
   from the available display styles.
3. Configure the various options available under the **Format Settings** to 
   customize the slideshow according to your needs.
4. Set the number of items to display in the **Pager** settings. While 
   pagination does not work with this display style, you can set a fixed number 
   of items to display. It is recommended to limit the number of items to a 
   maximum of 30 for optimal performance.

## Configuration Options

- **Time in Seconds:** Set the interval for automatic slide transitions. 
  Options include:
  - None (0 seconds)
  - 3 to 15 seconds
- **Navigation:** Choose the type of bottom navigation:
  - None
  - Dots
  - Numbers
- **Animation Type:** Select the type of animation for slide transitions:
  - Top
  - Bottom
  - Left
  - Right
  - Zoom
  - Opacity
- **Top Arrows:** Enable or disable the display of navigation arrows.

## Important Note on Pagination

**Pagination does not work for the VVJS display style.** To ensure the best 
performance and user experience, it is recommended to set a fixed number of 
items to display. The ideal number is up to 30 items. Exceeding this number may 
affect the slideshow's performance and load times.

## Accessibility

The VVJS module includes several accessibility features to ensure that your 
slideshows are usable by all visitors, including those using screen readers. 
Features include:

- **ARIA Roles and Properties:** Proper ARIA roles and properties are used to 
  provide context and state information to screen readers.
- **Keyboard Navigation:** Users can navigate through slides using keyboard 
  shortcuts.
- **Focus Management:** Ensures that the currently displayed slide is focused, 
  providing a better experience for keyboard and screen reader users.

## Troubleshooting

If you encounter any issues or have suggestions for improvements, please open 
an issue in the module's issue queue on Drupal.org.

## Maintainers

- [Alaa Haddad](https://www.drupal.org/u/flashwebcenter)

## License

This project is licensed under the [GNU General Public License, version 2 or
later](http://www.gnu.org/licenses/gpl-2.0.html).

---

This file follows the Drupal best practices for module documentation, ensuring 
that users have a clear understanding of the module's purpose, features, and 
usage. It also includes important notes on pagination to guide users in setting 
up the module correctly.
