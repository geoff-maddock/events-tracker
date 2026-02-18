# Event Filter URL Feature - UI Mockup

## Filter Panel Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  📋 Events Listing                                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  [🔽 Hide Filters ▼]                                                     │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │  Filter Panel                                                       │ │
│  │                                                                      │ │
│  │  ┌──────────────┬──────────────┬──────────────┬─────────────────┐  │ │
│  │  │ Name         │ Venue        │ Tags         │ Related Entity  │  │ │
│  │  │ [Concert   ] │ [Fillmore  ▼]│ [music    ▼] │ [           ▼]  │  │ │
│  │  └──────────────┴──────────────┴──────────────┴─────────────────┘  │ │
│  │                                                                      │ │
│  │  ┌──────────────┬──────────────────────────────────────────────┐   │ │
│  │  │ Type         │ Start Date                                    │   │ │
│  │  │ [         ▼] │ From: [2024-01-01] To: [2024-12-31]          │   │ │
│  │  └──────────────┴──────────────────────────────────────────────┘   │ │
│  │                                                                      │ │
│  │  ┌─────────┐ ┌─────────┐ ┌──────────────────┐                      │ │
│  │  │  Apply  │ │  Reset  │ │ 🔗 Copy Filter URL│  ◄─── NEW BUTTON   │ │
│  │  └─────────┘ └─────────┘ └──────────────────┘                      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│  [Events are displayed below...]                                         │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

## Button States

### Normal State (When Filters Are Active)
```
┌──────────────────┐
│ 🔗 Copy Filter URL│
└──────────────────┘
```

### After Click - Success State (2 seconds)
```
┌──────────────────┐
│ ✓ Copied!        │  ◄─── Green background
└──────────────────┘
```

### Hidden State (When No Filters Applied)
```
Button is not visible - only appears when hasFilter is true
```

## Button Behavior

### Trigger Conditions
- Button only appears when `$hasFilter` is true
- This means at least one filter has been applied
- Button is positioned after the Apply and Reset buttons

### Click Action
1. **Instant feedback**: Button text changes to "✓ Copied!"
2. **Visual change**: Background changes to green
3. **Clipboard**: URL is copied to system clipboard
4. **Duration**: After 2 seconds, button returns to normal state

### Generated URL Format
```
https://example.com/events/apply-filter?
  filters[name]=Concert&
  filters[venue]=Fillmore&
  filters[tag][]=music&
  filters[tag][]=live&
  filters[start_at][start]=2024-01-01&
  filters[start_at][end]=2024-12-31&
  sort=start_at&
  direction=desc&
  limit=25
```

## User Journey Examples

### Example 1: Finding Music Events

```
Step 1: User Opens Events Page
┌─────────────────────────────────┐
│ [🔽 Show Filters ▼]             │
│                                 │
│ [ No filters applied ]          │
│                                 │
│ Showing all 150 events          │
└─────────────────────────────────┘

Step 2: User Applies Filters
┌─────────────────────────────────┐
│ [🔽 Hide Filters ▼]             │
│ ┌──────────────────────────────┐│
│ │ Tags: [music ▼]              ││
│ │                              ││
│ │ [Apply] [Reset]              ││
│ └──────────────────────────────┘│
│                                 │
│ Showing 45 events               │
└─────────────────────────────────┘

Step 3: Filters Applied - Button Appears
┌─────────────────────────────────────┐
│ [🔽 Hide Filters ▼]                 │
│ ┌──────────────────────────────────┐│
│ │ Tags: [music ▼]                 ││
│ │                                  ││
│ │ [Apply] [Reset] [🔗 Copy Filter URL]│ ◄── NEW!
│ └──────────────────────────────────┘│
│                                     │
│ Showing 45 filtered music events    │
└─────────────────────────────────────┘

Step 4: User Clicks Copy Button
┌─────────────────────────────────────┐
│ [🔽 Hide Filters ▼]                 │
│ ┌──────────────────────────────────┐│
│ │ Tags: [music ▼]                 ││
│ │                                  ││
│ │ [Apply] [Reset] [✓ Copied!]     ││ ◄── SUCCESS!
│ └──────────────────────────────────┘│
│                                     │
│ URL copied to clipboard!            │
└─────────────────────────────────────┘
```

### Example 2: Sharing Event List

```
User A (sends link)                 User B (receives link)
─────────────────                   ──────────────────────

1. Apply filters                    
   Tags: music, live
   Venue: The Fillmore
   
2. Click "Copy Filter URL"
   URL copied to clipboard
   
3. Paste in message:                4. Clicks link
   "Check out these events!"           Opens in browser
   https://example.com/...
                                    5. Page loads with filters
                                       applied automatically
                                       
                                    6. Sees same 12 events
                                       that User A saw
```

## Responsive Design

### Desktop View (>768px)
```
[Apply] [Reset] [🔗 Copy Filter URL]
  All buttons in horizontal row
```

### Mobile View (<768px)
```
[Apply]

[Reset]

[🔗 Copy Filter URL]
  
  Buttons stack vertically
```

## Accessibility

### Keyboard Navigation
- Button is keyboard accessible (Tab to focus)
- Enter or Space to activate
- Focus indicator visible

### Screen Readers
- Button text: "Copy Filter URL"
- After click: Announces "Copied!"
- Alt text for icon available

### Color Contrast
- Meets WCAG AA standards
- Green success state is distinguishable
- Border provides additional visual cue

## Technical Details

### CSS Classes Used
```css
/* Button container */
.flex.gap-2.mt-4

/* Button */
.px-4.py-2                    /* Padding */
.bg-card                      /* Background color */
.border.border-border         /* Border */
.text-foreground              /* Text color */
.rounded-lg                   /* Rounded corners */
.hover:bg-accent              /* Hover state */
.transition-colors            /* Smooth transitions */
.flex.items-center.gap-2      /* Icon + text layout */

/* Success state (added via JS) */
.bg-green-100                 /* Light green background */
.border-green-500             /* Green border */
.text-green-700               /* Dark green text */
```

### Icons
- Link icon: Bootstrap Icon `bi-link-45deg`
- Success icon: Bootstrap Icon `bi-check2`

## Browser Support

✅ Chrome 63+
✅ Firefox 53+
✅ Safari 13.1+
✅ Edge 79+
✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Error Handling

### Clipboard API Not Available
```javascript
// Fallback alert shown:
alert('Failed to copy URL. Please copy manually: ' + fullUrl);
```

### No Filters Applied
```javascript
// Button simply doesn't render
@if($hasFilter)
  // Button code
@endif
```

### Invalid URL Parameters
```php
// Controller handles empty filters gracefully
if (!empty($filters)) {
    // Apply filters
}
// Always redirects to events page
```
