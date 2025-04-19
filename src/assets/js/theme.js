// src/assets/js/theme.js

// --- Utility: Convert Hex to HSL ---
function hexToHsl(hex) {
    // Remove the hash if it exists
    hex = hex.replace(/^#/, '');

    // Convert hex to RGB
    let r = parseInt(hex.substring(0, 2), 16) / 255;
    let g = parseInt(hex.substring(2, 4), 16) / 255;
    let b = parseInt(hex.substring(4, 6), 16) / 255;

    // Find min and max values
    let max = Math.max(r, g, b);
    let min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;

    if (max === min) {
        h = s = 0; // achromatic
    } else {
        let diff = max - min;
        s = l > 0.5 ? diff / (2 - max - min) : diff / (max + min);
        switch (max) {
            case r: h = (g - b) / diff + (g < b ? 6 : 0); break;
            case g: h = (b - r) / diff + 2; break;
            case b: h = (r - g) / diff + 4; break;
        }
        h /= 6;
    }

    // Return as degrees, percentage, percentage
    return [Math.round(h * 360), Math.round(s * 100), Math.round(l * 100)];
}

// --- Utility: Convert HSL to Hex ---
function hslToHex(h, s, l) {
    h /= 360;
    s /= 100;
    l /= 100;
    let r, g, b;
    if (s === 0) {
        r = g = b = l; // achromatic
    } else {
        const hue2rgb = (p, q, t) => {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1 / 6) return p + (q - p) * 6 * t;
            if (t < 1 / 2) return q;
            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        };
        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        r = hue2rgb(p, q, h + 1 / 3);
        g = hue2rgb(p, q, h);
        b = hue2rgb(p, q, h - 1 / 3);
    }
    const toHex = x => {
        const hex = Math.round(x * 255).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    };
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
}


// --- Calculate Color Variants using HSL ---
function calculateColorVariants(hexColor) {
    // Basic check for valid hex format before proceeding
    if (!hexColor || !/^#[a-fA-F0-9]{6}$/.test(hexColor)) {
        console.warn("Invalid hex color format for variant calculation:", hexColor);
        hexColor = '#8b5cf6'; // Fallback to default
    }

    const [h, s, l] = hexToHsl(hexColor);

    // Adjust lightness for light/dark variants, clamp between 0-100
    const lightL = Math.min(100, l + 15);
    const darkL = Math.max(0, l - 15);

    // Accent could be slightly more saturated or a hue shift (simple: same hue, lighter)
    const accentL = Math.min(100, l + 8);
    const accentS = Math.min(100, s + 5); // Slightly more saturation for accent

    return {
        primary: hexColor,
        light: hslToHex(h, s, lightL),
        dark: hslToHex(h, s, darkL),
        accent: hslToHex(h, accentS, accentL) // Using slightly lighter/saturated
    };
}

// --- Apply Theme Colors to CSS Variables ---
function applyThemeColor(hexColor) {
    const variants = calculateColorVariants(hexColor); // Will use default if hexColor is invalid
    const root = document.documentElement;

    root.style.setProperty('--color-primary', variants.primary);
    root.style.setProperty('--color-primary-light', variants.light);
    root.style.setProperty('--color-primary-dark', variants.dark);
    root.style.setProperty('--color-accent', variants.accent);

     // Update the theme picker indicator background on settings page if present
    const indicator = document.querySelector('.settings-color-indicator'); // Use specific class from settings
    if (indicator) {
        indicator.style.backgroundColor = variants.primary;
    }
    // Also update the value of the hidden color input if it exists (for the color picker)
    const hiddenColorInput = document.getElementById('accentColorPicker'); // Use ID from settings
    if (hiddenColorInput) {
        hiddenColorInput.value = variants.primary; // Set its value to the base color
    }
}


// --- Apply Initial Accent Color from Injected Settings ---
(function() { // IIFE to execute immediately
    // Attempt to get color from injected settings, fallback to default
    // Ensure window.userSettings exists before accessing its properties
    const initialAccentColor = (typeof window.userSettings !== 'undefined' && window.userSettings.accent_color)
                               ? window.userSettings.accent_color
                               : '#8b5cf6'; // Default purple
    applyThemeColor(initialAccentColor);
})();


// --- Add DOMContentLoaded Listeners for Picker Interaction (Visual Feedback Only) ---
document.addEventListener('DOMContentLoaded', () => {
    // Setup theme picker interactions (if elements exist on the page)
    const colorPickerInput = document.getElementById('accentColorPicker'); // Matches ID in settings.php
    const colorIndicator = document.querySelector('.settings-color-indicator'); // Matches class in settings.php

    // Sync indicator click with hidden input click
    if (colorIndicator && colorPickerInput) {
        colorIndicator.addEventListener('click', () => colorPickerInput.click());
    }

    // Update CSS variables and indicator immediately on picker change
    // This provides instant feedback. The actual saving is handled by settings.php's script.
    if (colorPickerInput) {
        colorPickerInput.addEventListener('input', function() {
            applyThemeColor(this.value); // Use the main apply function
        });
         // Ensure picker's initial value matches applied theme
         // This might be slightly redundant if applyThemeColor already sets it, but safe to keep.
         const currentPrimary = document.documentElement.style.getPropertyValue('--color-primary');
         if (currentPrimary) {
            colorPickerInput.value = currentPrimary;
         }
    }
});
