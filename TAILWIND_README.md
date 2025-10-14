# Tailwind CSS Setup for CarWash Project

This project now uses a locally built Tailwind CSS file instead of the CDN for better performance in production.

## Setup

The project includes:
- `tailwind.config.js` - Tailwind configuration
- `src/input.css` - Source CSS file with Tailwind directives
- `frontend/css/tailwind.css` - Built CSS file (optimized for production)
- `tailwindcss.exe` - Standalone Tailwind CLI executable

## Building CSS

### For Development (with watch mode):
```bash
.\tailwindcss.exe -i .\src\input.css -o .\frontend\css\tailwind.css --watch
```

### For Production (minified):
```bash
.\tailwindcss.exe -i .\src\input.css -o .\frontend\css\tailwind.css --minify
```

## Project Structure

```
carwash_project/
├── src/
│   └── input.css                 # Source CSS with Tailwind directives
├── frontend/
│   └── css/
│       └── tailwind.css          # Built CSS file (used in production)
├── tailwind.config.js            # Tailwind configuration
├── tailwindcss.exe               # Standalone Tailwind CLI
└── package.json                  # Project configuration
```

## Usage

1. The HTML files now reference `../frontend/css/tailwind.css` instead of the CDN
2. Custom styles are included in the `src/input.css` file
3. Run the build command whenever you make changes to `src/input.css`

## Benefits

✅ **Performance**: Only includes CSS that's actually used (~50KB vs ~3MB CDN)  
✅ **Offline**: Works without internet connection  
✅ **Customization**: Easy to add custom styles and components  
✅ **Optimization**: Automatic purging of unused styles  
✅ **Caching**: Browser can cache the CSS file for better performance  

## Development Workflow

1. Make changes to HTML/PHP files
2. If you need new Tailwind classes, they're automatically included when you rebuild
3. For custom styles, add them to `src/input.css`
4. Run the build command to update `frontend/css/tailwind.css`
5. Refresh your browser to see changes

## File Sizes

- CDN version: ~3MB (entire Tailwind library)
- Local optimized version: ~50-100KB (only used classes)
- Performance improvement: ~97% smaller file size

## Custom Classes Added

The build includes custom classes specific to the CarWash project:
- `.hero-gradient` - Hero section gradient background
- `.text-gradient` - Gradient text effect
- `.card-hover` - Card hover animations
- `.back-to-top` - Back to top button styling
- `.animate-fade-in-up` - Fade in from bottom animation
- `.animate-slide-in` - Slide in from left animation
- `.animate-pulse-slow` - Slow pulse animation