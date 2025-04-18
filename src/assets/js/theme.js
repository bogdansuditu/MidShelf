// --- Helper: Convert Hex to HSL ---
function hexToHsl(hex) {
    let r = 0, g = 0, b = 0;
    if (hex.length == 4) {
        r = parseInt(hex[1] + hex[1], 16);
        g = parseInt(hex[2] + hex[2], 16);
        b = parseInt(hex[3] + hex[3], 16);
    } else if (hex.length == 7) {
        r = parseInt(hex[1] + hex[2], 16);
        g = parseInt(hex[3] + hex[4], 16);
        b = parseInt(hex[5] + hex[6], 16);
    }
    r /= 255; g /= 255; b /= 255;
    let max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;

    if (max == min) {
        h = s = 0; // achromatic
    } else {
        let d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
    }
    return [Math.round(h * 360), Math.round(s * 100), Math.round(l * 100)];
}

// --- Helper: Convert HSL to Hex ---
function hslToHex(h, s, l) {
    s /= 100;
    l /= 100;
    let c = (1 - Math.abs(2 * l - 1)) * s,
        x = c * (1 - Math.abs((h / 60) % 2 - 1)),
        m = l - c / 2,
        r = 0, g = 0, b = 0;

    if (0 <= h && h < 60) { r = c; g = x; b = 0; }
    else if (60 <= h && h < 120) { r = x; g = c; b = 0; }
    else if (120 <= h && h < 180) { r = 0; g = c; b = x; }
    else if (180 <= h && h < 240) { r = 0; g = x; b = c; }
    else if (240 <= h && h < 300) { r = x; g = 0; b = c; }
    else if (300 <= h && h < 360) { r = c; g = 0; b = x; }

    r = Math.round((r + m) * 255).toString(16);
    g = Math.round((g + m) * 255).toString(16);
    b = Math.round((b + m) * 255).toString(16);

    if (r.length == 1) r = "0" + r;
    if (g.length == 1) g = "0" + g;
    if (b.length == 1) b = "0" + b;

    return "#" + r + g + b;
}

// --- Calculate Color Variants using HSL ---
function calculateColorVariants(hexColor) {
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
    const variants = calculateColorVariants(hexColor);
    const root = document.documentElement;

    if (!variants) return; // Should not happen with calculation function

    root.style.setProperty('--color-primary', variants.primary);
    root.style.setProperty('--color-primary-light', variants.light);
    root.style.setProperty('--color-primary-dark', variants.dark);
    root.style.setProperty('--color-accent', variants.accent);

     // Update the theme picker indicator background
    const indicator = document.querySelector('.theme-color-indicator');
    if (indicator) {
        indicator.style.backgroundColor = variants.primary;
    }
    // Also update the value of the hidden color input if it exists (for the color picker)
    const hiddenColorInput = document.querySelector('input.color-input');
    if (hiddenColorInput) {
        hiddenColorInput.value = variants.primary; // Set its value to the base color
    }
}


// --- Save Theme Color to Local Storage ---
function saveThemeColor(hexColor) {
    localStorage.setItem('userThemeColor', hexColor);
}

// --- Load Theme Color from Local Storage ---
function loadThemeColor() {
    const savedColor = localStorage.getItem('userThemeColor');
    // Set default if no color saved yet
    const initialColor = savedColor || '#8b5cf6'; // Default primary color
    applyThemeColor(initialColor);
}

// --- Initialize Theme and Event Listeners (will be added later) ---
document.addEventListener('DOMContentLoaded', () => {
    loadThemeColor();

    const themePicker = document.querySelector('.theme-picker');
    const colorInput = document.querySelector('.color-input'); // Assuming these IDs/classes exist

    if (themePicker && colorInput) {
         // When the visual picker element is clicked, trigger the hidden color input
        themePicker.addEventListener('click', () => {
            colorInput.click();
        });

        // When the hidden color input value changes (user selects color)
        colorInput.addEventListener('input', (event) => { // 'input' for live preview
            const newColor = event.target.value;
            applyThemeColor(newColor);
        });
        colorInput.addEventListener('change', (event) => { // 'change' for final selection
             const finalColor = event.target.value;
            saveThemeColor(finalColor); // Save only when selection is confirmed
        });

         // Update indicator initially
        const initialColor = localStorage.getItem('userThemeColor') || '#8b5cf6';
        const indicator = document.querySelector('.theme-color-indicator');
        if (indicator) {
            indicator.style.backgroundColor = initialColor;
        }
    } else {
        console.warn("Theme picker elements not found.");
    }
});
