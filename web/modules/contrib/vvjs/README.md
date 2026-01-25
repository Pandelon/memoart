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

## Deep Linking

VVJS supports deep linking, allowing you to create shareable URLs that link directly to specific slides. This is perfect for:
- Sharing specific slides on social media
- Linking to featured content in email campaigns
- Bookmarking favorite slides
- Creating thumbnail navigation that controls the slideshow

### Enabling Deep Linking

1. Edit your view and select **Views Vanilla JavaScript Slideshow** as the format
2. In the **Format Settings**, expand the **Deep Linking Settings** section
3. Check **Enable Deep Linking**
4. Enter a **URL Identifier** (e.g., "gallery", "products", "team")
   - Must be lowercase letters, numbers, and hyphens only
   - Must start with a letter
   - Keep it short and descriptive (max 20 characters)

When enabled, navigation dots/numbers become clickable links that update the browser URL:
- Example: `https://example.com/page#gallery-3` (links to slide 3)

### Multiple Slideshows on One Page

Each slideshow needs a unique identifier:
- First slideshow: identifier = "gallery" → `#gallery-3`
- Second slideshow: identifier = "products" → `#products-5`
- Third slideshow: identifier = "team" → `#team-2`

The browser URL can contain multiple slide positions:
```
https://example.com/page#gallery-3
```

## JavaScript API

VVJS provides a comprehensive JavaScript API for external control of slideshows. This allows you to build custom controls, thumbnail navigation, or integrate slideshows with other page elements.

### Basic Navigation
```javascript
// Navigate to slide 3 in the 'gallery' slideshow
Drupal.vvjs.goToSlide('gallery', 3);

// Navigate to next slide
Drupal.vvjs.nextSlide('gallery');

// Navigate to previous slide
Drupal.vvjs.prevSlide('gallery');
```

### Playback Control
```javascript
// Pause slideshow
Drupal.vvjs.pause('gallery');

// Resume slideshow
Drupal.vvjs.resume('gallery');

// Pause all slideshows on the page
Drupal.vvjs.pauseAll();

// Resume all slideshows on the page
Drupal.vvjs.resumeAll();
```

### Getting Information
```javascript
// Get current slide index (1-based)
const currentSlide = Drupal.vvjs.getCurrentSlide('gallery');
console.log('Currently on slide:', currentSlide);

// Get total number of slides
const totalSlides = Drupal.vvjs.getTotalSlides('gallery');
console.log('Total slides:', totalSlides);
```

### Advanced Usage
```javascript
// Get slideshow instance for full control
const slideshow = Drupal.vvjs.getInstance('gallery');

// Access core module
const core = slideshow.getModule('core');
console.log('Slideshow state:', {
  currentSlide: core.slideIndex,
  totalSlides: core.totalSlides,
  isPaused: core.isPaused,
  isVisible: core.isVisible
});

// Get all modules
const modules = slideshow.getAllModules();
console.log('Available modules:', Object.keys(modules));
```

### Custom Thumbnail Navigation Example

Here's a complete example of building custom thumbnail navigation that controls a VVJS slideshow:

**HTML Structure:**
```html
<!-- VVJS Slideshow with identifier "portfolio" -->
<div class="vvjs-slideshow-wrapper">
  <!-- The slideshow (managed by Views) -->
  <div id="portfolio-slideshow">
    <!-- VVJS slideshow renders here -->
  </div>
  
  <!-- Custom thumbnail navigation -->
  <div class="custom-thumbnails">
    <button class="thumb-btn" data-slide="1">
      <img src="/images/thumb-1.jpg" alt="Slide 1">
    </button>
    <button class="thumb-btn" data-slide="2">
      <img src="/images/thumb-2.jpg" alt="Slide 2">
    </button>
    <button class="thumb-btn" data-slide="3">
      <img src="/images/thumb-3.jpg" alt="Slide 3">
    </button>
    <button class="thumb-btn" data-slide="4">
      <img src="/images/thumb-4.jpg" alt="Slide 4">
    </button>
  </div>
</div>
```

**JavaScript:**
```javascript
(function (Drupal) {
  'use strict';

  Drupal.behaviors.customThumbnailNav = {
    attach: function (context, settings) {
      // Get all thumbnail buttons
      const thumbButtons = context.querySelectorAll('.thumb-btn');
      
      thumbButtons.forEach(function(button) {
        // Add click handler
        button.addEventListener('click', function() {
          const slideIndex = parseInt(this.getAttribute('data-slide'), 10);
          
          // Navigate to slide using VVJS API
          const success = Drupal.vvjs.goToSlide('portfolio', slideIndex);
          
          if (success) {
            // Update active state on thumbnails
            thumbButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
          }
        });
      });

      // Listen for slideshow changes to update thumbnail active state
      const slideshowContainer = context.querySelector('[data-deeplink-id="portfolio"]');
      
      if (slideshowContainer) {
        slideshowContainer.addEventListener('vvjs:slideChanged', function(e) {
          const currentSlide = e.detail.slideIndex;
          
          // Update thumbnail active states
          thumbButtons.forEach(function(button) {
            const btnSlide = parseInt(button.getAttribute('data-slide'), 10);
            button.classList.toggle('active', btnSlide === currentSlide);
          });
        });
      }
    }
  };

})(Drupal);
```

**CSS:**
```css
.custom-thumbnails {
  display: flex;
  gap: 10px;
  margin-top: 20px;
  justify-content: center;
}

.thumb-btn {
  border: 3px solid transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
  background: none;
}

.thumb-btn img {
  display: block;
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 2px;
}

.thumb-btn:hover {
  border-color: #007bff;
  transform: scale(1.05);
}

.thumb-btn.active {
  border-color: #007bff;
  box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
}
```

### URL Parameter Navigation Example

Automatically navigate to a slide based on URL parameters:
```javascript
(function (Drupal) {
  'use strict';

  Drupal.behaviors.urlSlideNavigation = {
    attach: function (context, settings) {
      // Only run once on page load
      if (context !== document) {
        return;
      }

      // Parse URL hash for slide navigation
      // Example: #gallery-5 means go to slide 5 of 'gallery' slideshow
      const hash = window.location.hash;
      
      if (!hash) {
        return;
      }

      // Remove the # symbol
      const hashValue = hash.substring(1);
      
      // Parse format: identifier-slideNumber
      const parts = hashValue.split('-');
      
      if (parts.length >= 2) {
        const identifier = parts.slice(0, -1).join('-'); // Handle identifiers with hyphens
        const slideNumber = parseInt(parts[parts.length - 1], 10);
        
        if (!isNaN(slideNumber)) {
          // Wait for slideshow to initialize
          setTimeout(function() {
            const success = Drupal.vvjs.goToSlide(identifier, slideNumber);
            
            if (success) {
              console.log('Navigated to slide', slideNumber, 'of', identifier);
            }
          }, 500);
        }
      }
    }
  };

})(Drupal);
```

### Creating a Share Button

Add a button that copies the current slide URL to clipboard:

**HTML:**
```html
<button id="share-slide-btn" class="share-button">
  Share This Slide
</button>
```

**JavaScript:**
```javascript
(function (Drupal) {
  'use strict';

  Drupal.behaviors.shareSlideButton = {
    attach: function (context, settings) {
      const shareBtn = context.querySelector('#share-slide-btn');
      
      if (!shareBtn) {
        return;
      }

      shareBtn.addEventListener('click', function() {
        // Get current slide
        const currentSlide = Drupal.vvjs.getCurrentSlide('gallery');
        
        if (!currentSlide) {
          alert('Slideshow not found');
          return;
        }

        // Build URL
        const url = window.location.origin + window.location.pathname + '#gallery-' + currentSlide;
        
        // Copy to clipboard
        navigator.clipboard.writeText(url).then(function() {
          // Show success message
          shareBtn.textContent = 'Link Copied!';
          setTimeout(function() {
            shareBtn.textContent = 'Share This Slide';
          }, 2000);
        }).catch(function(err) {
          console.error('Failed to copy URL:', err);
          alert('Failed to copy link');
        });
      });
    }
  };

})(Drupal);
```

### Event Listeners

VVJS dispatches custom events that you can listen to:
```javascript
// Listen for slide changes
const slideshow = document.querySelector('[data-deeplink-id="gallery"]');

slideshow.addEventListener('vvjs:slideChanged', function(e) {
  console.log('Slide changed to:', e.detail.slideIndex);
  console.log('Total slides:', e.detail.totalSlides);
});

// Listen for pause/play events
slideshow.addEventListener('vvjs:pauseToggled', function(e) {
  console.log('Slideshow paused:', e.detail.isPaused);
});

// Listen for initialization
slideshow.addEventListener('vvjs:initialized', function(e) {
  console.log('Slideshow initialized');
});
```

### Using CSS Selectors

If deep linking is not enabled, you can still control slideshows using CSS selectors, but be aware that auto-generated IDs change on each page load.

**Recommended approach - Use a wrapper class or ID:**
```html
<!-- Add a custom wrapper with stable ID/class -->
<div id="my-gallery" class="my-gallery-wrapper">
  <!-- VVJS slideshow renders here with random ID -->
</div>
```
```javascript
// Find the slideshow within your stable wrapper
const wrapper = document.getElementById('my-gallery');
const slideshowContainer = wrapper.querySelector('.vvjs-inner');

// Use the found element directly
const slideshow = Drupal.vvjs.getInstance(slideshowContainer);
slideshow.getModule('core').goToSlide(3);

// Or use your stable wrapper selector
Drupal.vvjs.goToSlide('#my-gallery .vvjs-inner', 3);
```

**Why this is better than using the auto-generated ID:**
```javascript
// ❌ BAD - This ID changes every page load
Drupal.vvjs.goToSlide('#vvjs-inner-12345678', 3);

// ✅ GOOD - Use deep link identifier (best option)
Drupal.vvjs.goToSlide('gallery', 3);

// ✅ GOOD - Use stable wrapper class
Drupal.vvjs.goToSlide('.my-gallery-wrapper .vvjs-inner', 3);

// ✅ GOOD - Use view display class (if you know it)
Drupal.vvjs.goToSlide('.view-my-gallery .vvjs-inner', 3);
```

### Best Practices

1. **Always check return values** - API methods return `true`/`false` to indicate success
2. **Wait for initialization** - Use `vvjs:initialized` event or `setTimeout()` if calling on page load
3. **Use meaningful identifiers** - Choose descriptive deep link identifiers like "gallery", "products", "testimonials"
4. **Handle errors gracefully** - Check for `null` returns when getting information
5. **Respect user preferences** - The slideshow may pause automatically for users with reduced motion preferences

### Browser Support

The VVJS module and its API work in all modern browsers that support:
- ES6 JavaScript
- Custom Events
- Intersection Observer (with fallback for older browsers)
- CSS Custom Properties

For optimal compatibility, ensure your Drupal site includes appropriate polyfills if supporting older browsers.


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
