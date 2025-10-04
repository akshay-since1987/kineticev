/**
 * =======================
 * Base URL Utility Function
 * =======================
 * Gets the current base URL for absolute form submissions
 * Ensures HTTPS is used when the current page is loaded over HTTPS
 */
function getBaseUrl() {
    // Use the same protocol as the current page to avoid mixed content issues
    const protocol = window.location.protocol;
    const host = window.location.host;
    
    // For production environments, always use HTTPS if available
    if (window.location.protocol === 'https:' || host === 'kineticev.in' || host === 'www.kineticev.in') {
        return `https://${host}`;
    }
    
    return `${protocol}//${host}`;
}

// Import booking form handler
import { BookingFormHandler } from './booking-form.js';
// Import EMI calculator
import { EmiCalculator } from './emi-calculator.js';
// Import EMI calculator page
import { EmiCalculatorPage } from './emi-calculator-page.js';

/**
 * =======================
 * Choose Variant 360 Color Picker Module
 * =======================
 * Handles color picker functionality for 360 viewer on choose-variant.php
 */
const ChooseVariant360ColorPicker = {
    init: function () {
        // Only run on choose-variant page (detect by .legend-form .color-options and .model-viewer .model)
        const colorOptions = document.querySelector('.legend-form .color-options');
        const modelImg = document.querySelector('.legend-form .model-viewer .model');
        const backgroundModel = document.querySelector('.background-model-image');
        if (!colorOptions || !modelImg || !backgroundModel) return;

        this.modelImg = modelImg;
        this.colorOptions = colorOptions;
        this.backgroundModel = backgroundModel;
        this.currentViewer = null;
        this.currentColor = this.getSelectedColor() || 'red';

        // Initialize viewer with current color
        this.createViewer(this.currentColor);

        // Listen for color changes
        colorOptions.addEventListener('change', (e) => {
            const checkedRadio = colorOptions.querySelector('input[type="radio"]:checked');
            if (!checkedRadio) return;
            let color = checkedRadio.value;
            this.changeViewerColor(color);
            if (color === 'grey') {
                color = 'gray';
            }
            backgroundModel.setAttribute('href', `/-/images/new/${color}/000145.png`);
        });
    },

    getSelectedColor: function () {
        const checkedRadio = this.colorOptions?.querySelector('input[type="radio"]:checked');
        return checkedRadio ? checkedRadio.value : null;
    },

    createViewer: function (color) {
        // Map 'grey' to 'gray' for folder path
        const folderColor = color === 'grey' ? 'gray' : color;

        // Detect device capabilities to optimize loading
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isLowEndDevice = isMobile && !(/iPad|iPhone X|iPhone 11|iPhone 12|iPhone 13|iPad Pro/i.test(navigator.userAgent));

        // Show preloader if available
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            preloader.classList.add('is-loading');
        }

        // Destroy previous viewer if exists
        if (this.currentViewer && typeof this.currentViewer.destroy === 'function') {
            this.currentViewer.destroy();
        }

        // Set image src to correct color and frame
        const frame = 179;
        this.modelImg.src = `/-/images/new/${folderColor}/${frame.toString().padStart(6, '0')}.png`;

        // Create new viewer
        this.currentViewer = new Object360Viewer({
            viewerElement: this.modelImg.closest('.model-viewer'),
            imageElement: this.modelImg,
            totalFrames: 179,
            initialAngle: 75, // Set to load at 75 degree angle
            sensitivity: 3,
            imagePathTemplate: `/-/images/new/${folderColor}/{frame}.png`,
            preloadStrategy: 'progressive', // Use progressive loading for faster initial load
            preloadConcurrency: isLowEndDevice ? 4 : 6,
            memoryManagement: isLowEndDevice ? 'moderate' : 'none',
            // Progressive loading configuration
            immediateRadius: 3,   // Load 3 frames around current immediately
            priorityRadius: 10,   // Load 10 frames around current with priority
            idleLoadDelay: 500,   // Wait 500ms before starting idle loading
            maxIdleConcurrency: 2 // Max 2 concurrent idle loads
        });

        this.modelImg._object360Viewer = this.currentViewer;
        this.currentColor = color;

        // Setup event listener for when first frame is ready
        const viewerElement = this.modelImg.closest('.model-viewer');
        if (viewerElement) {
            const onReady = () => {
                // Hide preloader when first frame is ready
                if (preloader) {
                    preloader.classList.remove('is-loading');
                }
                viewerElement.removeEventListener('o360:ready', onReady);
            };
            viewerElement.addEventListener('o360:ready', onReady);
        }
    },

    changeViewerColor: function (color) {
        if (color === this.currentColor) return;
        this.createViewer(color);
    }
};

/**
 * =======================
 * Legend Form Module
 * =======================
 * Handles form interactions on choose-variant page
 */
const LegendFormManager = {
    init: function () {
        // Only run on choose-variant page
        const legendForm = document.querySelector('.legend-form');
        if (!legendForm) return;

        const variantSelect = legendForm.querySelector('#variant');
        const shadeLabel = legendForm.querySelector('.shade-label');
        const colorOptions = legendForm.querySelector('.color-options');

        if (!variantSelect || !shadeLabel || !colorOptions) return;

        // Handle variant selection change
        variantSelect.addEventListener('change', (e) => {
            const selectedValue = e.target.value;

            if (selectedValue && selectedValue !== '') {
                // Show color selection elements
                shadeLabel.classList.add('show');
                colorOptions.classList.add('show');
            } else {
                // Hide color selection elements and reset color selection
                shadeLabel.classList.remove('show');
                colorOptions.classList.remove('show');

                // Clear any selected color
                const colorRadios = colorOptions.querySelectorAll('input[type="radio"]');
                colorRadios.forEach(radio => {
                    radio.checked = false;
                });
            }
        });

        // Initially hide color selection if no variant is selected
        const initialValue = variantSelect.value;
        if (!initialValue || initialValue === '') {
            shadeLabel.classList.remove('show');
            colorOptions.classList.remove('show');
        }
    }
};

// Object360Viewer is now imported in main.js and available globally
import $ from 'jquery';

// Ensure jQuery is globally available for slick
window.$ = window.jQuery = $;

// Import slick carousel after jQuery is available
import 'slick-carousel';

/**
 * =======================
 * Slider Module
 * =======================
 * Handles slider initialization and management
 */
const SliderManager = {
    /**
     * Initialize slider with image preloading support
     */
    initializeSlider: function () {
        const sliderContainer = $('.slider');
        if (sliderContainer.length === 0) {
            return;
        }

        // Wait for all images in .slider to load before initializing Slick
        const $images = sliderContainer.find('img');
        let loadedCount = 0;

        function initSlick() {
            // Initialize main slider with simple configuration
            sliderContainer.slick({
                infinite: false,
                slidesToShow: 1,
                slidesToScroll: 1,
                draggable: false,
                swipe: false,
                touchMove: false,
                arrows: false,
                dots: true,
                centerMode: false,
                variableWidth: false,
                adaptiveHeight: false,
                appendDots: $('.custom-dots'),
                asNavFor: '.specs-slider',
                onInit: function (slick) {
                    // Force recalculation after init
                    setTimeout(() => {
                        sliderContainer.slick('setPosition');
                    }, 100);
                },
                onAfterChange: function (slick, currentSlide) {
                }
            });

            const navSlider = $('.specs-slider');
            // Keep specs-slider as is for navigation
            navSlider.slick({
                infinite: false,
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: false,
                dots: false,
                draggable: false,
                swipe: false,
                touchMove: false,
                centerMode: false,
                asNavFor: '.slider'
            });
        }

        if ($images.length === 0) {
            initSlick();
            return;
        }

        $images.each(function () {
            if (this.complete) {
                loadedCount++;
            } else {
                $(this).one('load error', function () {
                    loadedCount++;
                    if (loadedCount === $images.length) {
                        initSlick();
                    }
                });
            }
        });

        // If all images were already loaded
        if (loadedCount === $images.length) {
            initSlick();
        }
    },

    /**
     * Refresh slider on screen resize
     */
    refreshSlider: function () {
        const sliderContainer = $('.slider');
        const navSlider = $('.specs-slider');

        if (sliderContainer.hasClass('slick-initialized')) {
            sliderContainer.slick('refresh');
        }

        if (navSlider.hasClass('slick-initialized')) {
            navSlider.slick('refresh');
        }
    },

    /**
     * Initialize resize listener
     */
    initializeResizeListener: function () {
        let resizeTimer;

        $(window).on('resize', () => {
            // Debounce the resize event to avoid excessive calls
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.refreshSlider();
            }, 250);
        });
    },

    /**
     * Setup specs slider color picker functionality
     */
    setupSpecsSliderColorPicker: function () {
        // DISABLED: Color picker functionality is temporarily disabled
        // return;

        // Wait for slider to be initialized
        if (typeof $ === 'undefined') {
            return;
        }

        const $specsSlider = $('.specs-slider');
        const $mainSlider = $('.slider');

        if ($specsSlider.length === 0) {
            return;
        }

        // Check if specs slider is initialized
        if (!$specsSlider.hasClass('slick-initialized')) {
            setTimeout(() => this.setupSpecsSliderColorPicker(), 500);
            return;
        }

        const specsSlider = document.querySelector('.specs-slider');
        if (!specsSlider) {
            console.warn('Specs slider DOM element not found');
            return;
        }

        // Store the current color for each slide
        this.slideColors = new Map();

        // Store reference to this for event handlers
        const self = this;

        // Handle color selection for the current active slide - using click event instead of change
        specsSlider.addEventListener('click', (e) => {
            // Check if the clicked element is a radio button inside a color picker
            const radio = e.target.closest('input[type="radio"]');
            if (radio && radio.closest('.color-picker') && specsSlider.contains(radio)) {
                // Ensure the clicked radio is checked
                radio.checked = true;

                const activeImg = document.querySelector('.slider .slick-active img');
                const currentSlideIndex = $mainSlider.slick('slickCurrentSlide');

                if (!activeImg) {
                    console.warn('No active slider image found');
                    return;
                }

                // Extract the filename from the current image source
                const src = activeImg.getAttribute('src');

                // Handle two different image path patterns:
                // 1. /-/images/new/[color]/[filename].png
                // 2. /-/images/slider/[color]-[filename].png
                let match = src.match(/\/images\/new\/[a-z]+\/(.+)$/i);
                let newSrc;

                if (!match) {
                    // Try alternative pattern for slider images
                    match = src.match(/\/images\/slider\/[a-z]+-(.+)$/i);

                    if (!match) {
                        console.warn('Could not parse image filename from:', src);
                        return;
                    }

                    // For slider image pattern
                    const filename = match[1];
                    const selectedColor = radio.value || radio.name;

                    // Store the selected color for this slide
                    self.slideColors.set(currentSlideIndex, selectedColor);

                    // Build new source with slider path pattern
                    newSrc = `/-/images/slider/${selectedColor}-${filename}`;
                } else {
                    // For standard image pattern
                    const filename = match[1];
                    const selectedColor = radio.value || radio.name;

                    // Store the selected color for this slide
                    self.slideColors.set(currentSlideIndex, selectedColor);

                    // Build new source with standard path pattern
                    newSrc = `/-/images/new/${selectedColor}/${filename}`;
                }

                activeImg.setAttribute('src', newSrc);

                activeImg.setAttribute('src', newSrc);

                // Add visual feedback for the selected color
                const colorPicker = radio.closest('.color-picker');
                if (colorPicker) {
                    // Remove selected visual styling from all labels
                    colorPicker.querySelectorAll('label').forEach(label => {
                        label.style.boxShadow = '0 0 0 1px #999';
                    });

                    // Add selected visual styling to the clicked radio's label
                    const selectedLabel = radio.closest('label');
                    if (selectedLabel) {
                        selectedLabel.style.boxShadow = '0 0 0 2px #333, 0 0 0 3px #fff';
                    }
                }
            }
        });

        // Update color selection when changing slides
        $mainSlider.on('afterChange', function (event, slick, currentSlide) {

            // If we have a saved color for this slide, update the radio button
            if (self.slideColors.has(currentSlide)) {
                const savedColor = self.slideColors.get(currentSlide);
                const colorPicker = specsSlider.querySelector('.color-picker');

                if (colorPicker) {
                    // Reset all labels styling
                    colorPicker.querySelectorAll('label').forEach(label => {
                        label.style.boxShadow = '0 0 0 1px #999';
                    });

                    // Find and check the radio button for the saved color
                    const radioToCheck = colorPicker.querySelector(`input[type="radio"][value="${savedColor}"]`);
                    if (radioToCheck) {
                        radioToCheck.checked = true;

                        // Add visual styling to the selected radio's label
                        const selectedLabel = radioToCheck.closest('label');
                        if (selectedLabel) {
                            selectedLabel.style.boxShadow = '0 0 0 2px #333, 0 0 0 3px #fff';
                        }
                    }
                }
            }
        });

        // Initialize color mapping from initial slides
        this.initializeColorMapping($mainSlider, self);
    },

    /**
     * Initialize color mapping from initial slides
     * This detects the colors from the slides on first load
     */
    initializeColorMapping: function ($mainSlider, self) {
        // Get all slides
        const slides = document.querySelectorAll('.slider .slide');

        slides.forEach((slide, index) => {
            const img = slide.querySelector('img');
            if (img) {
                // Extract the color from the image source
                const src = img.getAttribute('src');

                // Handle both path patterns:
                // 1. /images/new/[color]/filename.png
                // 2. /images/slider/[color]-filename.png
                let colorMatch = src.match(/\/images\/new\/([a-z]+)\//i); // Pattern 1

                if (!colorMatch) {
                    colorMatch = src.match(/\/images\/slider\/([a-z]+)-/i); // Pattern 2
                }

                if (colorMatch && colorMatch[1]) {
                    const detectedColor = colorMatch[1];
                    // Store the detected color
                    self.slideColors.set(index, detectedColor);

                    // If it's the first slide, select the corresponding radio
                    if (index === 0) {
                        const specsSlider = document.querySelector('.specs-slider');
                        if (specsSlider) {
                            const colorPicker = specsSlider.querySelector('.color-picker');
                            if (colorPicker) {
                                const radioToCheck = colorPicker.querySelector(`input[type="radio"][value="${detectedColor}"]`);
                                if (radioToCheck) {
                                    radioToCheck.checked = true;

                                    // Add visual styling
                                    const selectedLabel = radioToCheck.closest('label');
                                    if (selectedLabel) {
                                        selectedLabel.style.boxShadow = '0 0 0 2px #333, 0 0 0 3px #fff';
                                    }
                                }
                            }
                        }
                    }
                } else {
                    console.warn('Could not detect color from path:', src);
                }
            }
        });
    }
};

/**
 * =======================
 * 360 Viewer Color Picker Module
 * =======================
 * Handles color picker functionality for 360 viewer
 */
const Viewer360ColorPicker = {
    /**
     * Initialize the 360 viewer with the current color and default frame
     */
    initializeViewer: function () {
        // Default to last frame (179) if not specified
        const initialFrame = 179;
        const folderColor = this.currentColor === 'grey' ? 'gray' : this.currentColor;

        // Show preloader (centered on screen for initial load, not anchored)
        this.togglePreloader(true, false);  // false = don't anchor to viewer for initial load
        // Reset progress bar
        this.updateProgressBar(0);

        // Setup loading progress handler
        this.onLoadingProgress = this.onLoadingProgress.bind(this);
        this.viewerElement.addEventListener('o360:loadingProgress', this.onLoadingProgress);

        // Setup all loaded handler
        this.onAllLoaded = this.onAllLoaded.bind(this);
        this.viewerElement.addEventListener('o360:allLoaded', this.onAllLoaded);

        // Detect device capabilities to optimize loading
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isLowEndDevice = isMobile && !(/iPad|iPhone X|iPhone 11|iPhone 12|iPhone 13|iPad Pro/i.test(navigator.userAgent));

        this.currentViewer = new Object360Viewer({
            viewerElement: this.viewerElement,
            imageElement: this.imageElement,
            totalFrames: 179,
            initialAngle: 75, // Set to load at 75 degree angle
            sensitivity: 3,
            imagePathTemplate: `/-/images/new/${folderColor}/{frame}.png`,
            preloadStrategy: 'progressive',  // Use progressive for faster initial load
            preloadConcurrency: isLowEndDevice ? 3 : 5,
            // Use memory management on mobile devices
            memoryManagement: isLowEndDevice ? 'moderate' : 'none',
            // Progressive loading configuration
            immediateRadius: 4,   // Load 4 frames around current immediately
            priorityRadius: 12,   // Load 12 frames around current with priority
            idleLoadDelay: 300,   // Wait 300ms before starting idle loading
            maxIdleConcurrency: isLowEndDevice ? 1 : 3 // Adaptive idle concurrency
        });
        this.imageElement._object360Viewer = this.currentViewer;

        // Hide preloader when first frame ready
        const onReady = () => {
            // Let progress bar reach at least 10% before hiding preloader
            setTimeout(() => {
                this.togglePreloader(false, false); // false = don't anchor for initial load
                // Keep progress events while loading in background
            }, 300);
            this.viewerElement.removeEventListener('o360:ready', onReady);

            // Switch to eager loading after first frame is shown
            setTimeout(() => {
                if (this.currentViewer && typeof this.currentViewer.switchToEager === 'function') {
                    this.currentViewer.switchToEager();
                }
            }, 1000); // Increased delay to allow more progressive loading
        };
        this.viewerElement.addEventListener('o360:ready', onReady);
    },

    /**
     * Handle loading progress events from the 360 viewer
     */
    onLoadingProgress: function (event) {
        const { percent } = event.detail;

        // Let the togglePreloader function handle visibility
        const preloader = document.querySelector('.preloader');
        if (!preloader) {
            console.error('No preloader found during loading progress');
        }

        this.updateProgressBar(percent);
    },

    /**
     * Handle all frames loaded event
     */
    onAllLoaded: function (event) {
        // Ensure progress bar shows 100%
        this.updateProgressBar(100);

        // Add loaded class to viewer for potential animations/effects
        if (this.viewerElement) {
            this.viewerElement.classList.add('all-loaded');
        }

        // Hide preloader after a short delay to show 100% progress
        setTimeout(() => {
            // Set anchorToViewer to false to completely remove the viewer-anchored class
            this.togglePreloader(false, false);

            // Double check and explicitly remove the viewer-anchored class if it's still there
            const preloader = document.querySelector('.preloader');
            if (preloader && preloader.classList.contains('viewer-anchored')) {
                preloader.classList.remove('viewer-anchored');
            }
        }, 500);
    },

    /**
     * Update progress bar width and text
     */
    updateProgressBar: function (percent) {
        const progressBar = document.querySelector('.preloader .progress-bar');
        const progressText = document.querySelector('.preloader .progress-text');

        if (progressBar) {
            // Using CSS custom property for progress width
            progressBar.style.setProperty('--progress-width', `${percent}%`);
        }

        if (progressText) {
            progressText.textContent = `${Math.round(percent)}%`;
        }
    },
    /**
     * Initialize color picker for 360 viewer
     */
    init: function () {

        // Try a more precise selector
        this.viewerElement = document.querySelector('.model-viewer');
        // Fallback to generic selector if needed
        if (!this.viewerElement) {
            return;
        }

        this.imageElement = this.viewerElement?.querySelector('.model');

        // Try different selectors for the color picker
        this.colorPicker = document.querySelector('.feature-columns .color-picker');
        if (!this.colorPicker) {
            this.colorPicker = document.querySelector('.stacked-360-wrapper .color-picker');
        }

        this.currentViewer = null;
        this.currentColor = 'red'; // Default starting color

        if (!this.viewerElement || !this.imageElement) {
            return;
        }

        // Initialize with red color and 000179.png as starting point
        this.initializeViewer();

        // Setup color picker events if color picker exists
        if (this.colorPicker) {
            this.setupColorPickerEvents();
            // Set red as the default selected color
            const redRadio = this.colorPicker.querySelector('input[value="red"]');
            // ...existing code...
        }
    },

    /**
     * Setup color picker event listeners
     */
    setupColorPickerEvents: function () {
        this.colorPicker.addEventListener('change', (e) => {
            if (e.target.type === 'radio' && e.target.name === 'color') {
                const selectedColor = e.target.value;
                this.changeViewerColor(selectedColor);
            }
        });
    },

    /**
     * Change 360 viewer color and maintain current frame position
     */
    changeViewerColor: function (color) {

        // Show preloader immediately to give user feedback
        const preloader = document.querySelector('.preloader');
        if (!preloader) {
            console.error('Preloader not found in the DOM - cannot show loading indicator');
        } else {
            // Reset any custom styles that might be lingering
            preloader.style.removeProperty('background-color');
        }

        // If it's the same color, do nothing
        if (color === this.currentColor) {
            return;
        }

        // Get current frame from the existing viewer before destroying it
        let currentFrame = 179; // Default fallback
        if (this.currentViewer && this.currentViewer.currentFrame) {
            currentFrame = this.currentViewer.currentFrame;
        } else {
            // If no viewer exists, try to get from current image
            currentFrame = this.getCurrentImageNumber();
        }

        // Remove previous event listeners
        if (this.onLoadingProgress) {
            this.viewerElement.removeEventListener('o360:loadingProgress', this.onLoadingProgress);
        }
        if (this.onAllLoaded) {
            this.viewerElement.removeEventListener('o360:allLoaded', this.onAllLoaded);
        }

        // Destroy existing viewer if it exists
        if (this.currentViewer && typeof this.currentViewer.destroy === 'function') {
            this.currentViewer.destroy();
        }

        // Update current color
        this.currentColor = color;
        const folderColor = this.currentColor === 'grey' ? 'gray' : this.currentColor;

        // Update the current image source to the new color with current frame
        const frameNumber = currentFrame.toString().padStart(6, '0');
        const newImagePath = `/-/images/new/${folderColor}/${frameNumber}.png`;
        this.imageElement.src = newImagePath;

        // Create new 360 viewer with the selected color and current frame as starting point
        // Use anchored preloader for color changes
        this.togglePreloader(true, true); // true = show preloader, true = anchor to viewer
        // Reset progress bar
        this.updateProgressBar(0);

        // Setup loading progress handler
        this.onLoadingProgress = this.onLoadingProgress.bind(this);
        this.viewerElement.addEventListener('o360:loadingProgress', this.onLoadingProgress);

        // Setup all loaded handler
        this.onAllLoaded = this.onAllLoaded.bind(this);
        this.viewerElement.addEventListener('o360:allLoaded', this.onAllLoaded);

        // Detect device capabilities to optimize loading
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isLowEndDevice = isMobile && !(/iPad|iPhone X|iPhone 11|iPhone 12|iPhone 13|iPad Pro/i.test(navigator.userAgent));

        // Remove all-loaded class from viewer if exists
        if (this.viewerElement) {
            this.viewerElement.classList.remove('all-loaded');
        }

        this.currentViewer = new Object360Viewer({
            viewerElement: this.viewerElement,
            imageElement: this.imageElement,
            totalFrames: 179,
            initialAngle: 75, // Always start at 75 degrees
            sensitivity: 3,
            imagePathTemplate: `/-/images/new/${folderColor}/{frame}.png`,
            preloadStrategy: 'progressive', // Use progressive for color changes too
            preloadConcurrency: isLowEndDevice ? 4 : 8, // Higher concurrency for color changes
            // Use memory management on mobile devices
            memoryManagement: isLowEndDevice ? 'moderate' : 'none',
            // Progressive loading configuration optimized for color changes
            immediateRadius: 5,   // Load more frames immediately for color changes
            priorityRadius: 15,   // Larger priority radius for color changes
            idleLoadDelay: 200,   // Shorter delay for color changes
            maxIdleConcurrency: isLowEndDevice ? 2 : 4 // Higher idle concurrency
        });

        // Store reference on the image element for other scripts
        this.imageElement._object360Viewer = this.currentViewer;


        const onReady = () => {
            // We no longer hide the preloader here
            // Instead, we'll rely on the onAllLoaded event to hide the preloader
            // when all frames are actually loaded

            // We can still make sure the preloader is properly shown/positioned
            this.togglePreloader(true, true);

            // Update progress bar to show at least some progress
            this.updateProgressBar(10);

            this.viewerElement.removeEventListener('o360:ready', onReady);
        };

        this.viewerElement.addEventListener('o360:ready', onReady);
    },

    /**
     * Get current viewer instance
     */
    getCurrentViewer: function () {
        return this.currentViewer;
    },

    /**
     * Set viewer instance (useful when viewer is created elsewhere)
     */
    setCurrentViewer: function (viewer) {
        this.currentViewer = viewer;
    },

    /**
     * Get current color
     */
    getCurrentColor: function () {
        return this.currentColor;
    },

    // Toggle preloader visibility (using global preloader from header.php)
    togglePreloader: function (isLoading, anchorToViewer = false) {
        const loading = !!isLoading;
        console.log(`Preloader ${loading ? 'shown' : 'hidden'}${anchorToViewer ? ' (anchored to viewer)' : ''}`);

        // Debug info
        console.log('togglePreloader called with:', { isLoading, anchorToViewer });

        if (this.viewerElement) {
            this.viewerElement.classList.toggle('is-loading', loading);
            console.log('Added is-loading class to viewerElement:', this.viewerElement);
        } else {
            console.warn('No viewerElement found');
        }

        const preloader = document.querySelector('.preloader');
        if (!preloader) {
            console.error('Preloader element not found in the DOM');
            return;
        }

        // Debug - show preloader state
        console.log('Preloader element before changes:', {
            classes: preloader.className,
            display: window.getComputedStyle(preloader).display,
            opacity: window.getComputedStyle(preloader).opacity,
            visibility: window.getComputedStyle(preloader).visibility
        });

        // Force repaint to avoid CSS transition issues
        void preloader.offsetWidth;

        // Toggle loading state
        if (loading) {
            preloader.classList.add('is-loading');
        } else {
            preloader.classList.remove('is-loading');
        }

        // Handle viewer anchoring
        if (anchorToViewer && this.viewerElement && loading) {
            console.log('Anchoring preloader to viewer');

            // Add the anchoring class
            preloader.classList.add('viewer-anchored');

            // Calculate viewer position in viewport
            // Get the bounding rectangle of the viewer element
            const viewerRect = this.viewerElement.getBoundingClientRect();

            // Position the preloader at the center of the viewer
            const viewportTop = window.scrollY;
            const viewportLeft = window.scrollX;

            // Calculate the center position of the viewer in the viewport
            const centerX = viewportLeft + viewerRect.left + (viewerRect.width / 2);
            const centerY = viewportTop + viewerRect.top + (viewerRect.height / 2);

            console.log(`Positioning preloader at ${centerX}px, ${centerY}px`);

            // Since we're using transform: translate(-50%, -50%) in CSS, 
            // we can set top/left directly to center coordinates
            preloader.style.top = `${centerY}px`;
            preloader.style.left = `${centerX}px`;

            // Make sure the preloader is visible
            preloader.style.display = 'block';
            preloader.style.visibility = 'visible';
        } else if (!loading) {
            // Always remove the viewer-anchored class when hiding the preloader
            preloader.classList.remove('viewer-anchored');

            // Reset all inline styles that might have been set
            preloader.style.top = '';
            preloader.style.left = '';
            preloader.style.display = '';
            preloader.style.visibility = '';
            preloader.style.opacity = '';
            preloader.style.backgroundColor = '';

            console.log('Preloader hidden, all inline styles cleared and viewer-anchored class removed');
        } else {
            // Just make sure viewer-anchored class is not present for non-anchored displays
            preloader.classList.remove('viewer-anchored');
        }
    },

    /**
     * Parse current frame number from the image src as a fallback
     */
    getCurrentImageNumber: function () {
        const src = this.imageElement?.getAttribute('src') || '';
        // Match /images/new/<color>/<000000>.png
        const m = src.match(/\/images\/new\/([a-z]+)\/(\d{6})\.png$/i);
        if (m && m[2]) {
            const n = parseInt(m[2], 10);
            if (!Number.isNaN(n)) return n;
        }
        return 179;
    }
};

/**
 * =======================
 * UI Utilities Module
 * =======================
 * Utilities for UI interactions
 */
const UIUtils = {
    /**
     * Sanitize phone number to 10 digits by removing country code if present
     */
    sanitizePhoneNumber: function(phone) {
        if (!phone) return '';
        
        // Remove all non-digit characters
        const cleaned = phone.replace(/[^0-9]/g, '');
        
        // If it's 12-13 digits with country code (91), remove it
        if (/^91[6-9]\d{9}$/.test(cleaned)) {
            return cleaned.substring(2); // Remove the '91' prefix
        }
        
        // If it's already 10 digits, return as is
        if (/^[6-9]\d{9}$/.test(cleaned)) {
            return cleaned;
        }
        
        // Return original cleaned number if it doesn't match expected patterns
        return cleaned;
    },

    /**
     * Set cookie
     */
    setCookie: function (name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    },

    /**
     * Get cookie
     */
    getCookie: function (name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    },

    /**
     * Show modal popup
     */
    showModal: function (modalSelector) {
        const popup = document.querySelector(modalSelector);
        if (!popup) return;

        const popupId = popup.getAttribute('id');
        if (!this.getCookie(`popupClosed_${popupId}`)) {
            popup.classList.add("active");
        }
    }
};

/**
 * =======================
 * Simple Video Playlist Module
 * =======================
 * Handles basic sequential video playback in modals
 */
const VideoPlaylistManager = {
    /**
     * Initialize the video playlist functionality
     */
    init: function () {
        console.log('Initializing Simple Video Playlist Manager');

        // Set up video playlist in modals
        this.setupVideoPlaylists();

        // Set up modal triggers that might open videos
        this.setupModalTriggers();

        // Set up modal close handlers including ESC key
        this.setupGlobalEscHandler();
    },

    /**
     * Set up video playlists in modals
     */
    setupVideoPlaylists: function () {
        // Store a reference to the current active video for reliable control
        this.currentActiveVideo = null;
        this.nextVideoToPlay = null;

        // Find all playlists on the page
        const playlists = document.querySelectorAll('.play-list-container');

        playlists.forEach(playlist => {
            // Find all videos in this playlist
            const videoWrappers = playlist.querySelectorAll('.video-wrapper');
            if (!videoWrappers.length) return;

            console.log(`Found playlist with ${videoWrappers.length} videos`);

            // Clean up any existing intervals
            videoWrappers.forEach(wrapper => {
                const video = wrapper.querySelector('video');
                if (video && video.dataset.checkIntervalId) {
                    clearInterval(parseInt(video.dataset.checkIntervalId));
                    delete video.dataset.checkIntervalId;
                }
            });

            // Hide all videos except the first one
            for (let i = 0; i < videoWrappers.length; i++) {
                videoWrappers[i].style.display = i === 0 ? 'block' : 'none';
            }

            // Create a direct reference to videos for easier access
            const videos = Array.from(videoWrappers).map(wrapper => wrapper.querySelector('video')).filter(v => v);

            // Add custom property to track video index
            videos.forEach((video, idx) => {
                video.setAttribute('data-video-index', idx);

                // Remove all existing event listeners to avoid duplicates
                const clonedVideo = video.cloneNode(true);
                video.parentNode.replaceChild(clonedVideo, video);
            });

            // Get fresh references after cloning
            const freshVideos = Array.from(videoWrappers).map(wrapper => wrapper.querySelector('video')).filter(v => v);

            // Define function to advance to next video
            const advanceToNextVideo = (currentVideoEl) => {
                // Safety check
                if (!currentVideoEl) return;

                const currentIndex = parseInt(currentVideoEl.getAttribute('data-video-index'));
                if (isNaN(currentIndex)) return;

                // Calculate next index with looping
                const nextIndex = (currentIndex + 1) % freshVideos.length;

                console.log(`Video ${currentIndex + 1} ended. Advancing to video ${nextIndex + 1}`);

                // Hide all videos
                videoWrappers.forEach(w => w.style.display = 'none');

                // Pause current video explicitly
                currentVideoEl.pause();
                currentVideoEl.currentTime = 0;

                // Show and play the next video with multiple attempts
                const nextVideo = freshVideos[nextIndex];
                if (nextVideo) {
                    // Set as current active video
                    this.currentActiveVideo = nextVideo;

                    // Show its container
                    nextVideo.closest('.video-wrapper').style.display = 'block';

                    // Reset and attempt to play
                    nextVideo.currentTime = 0;

                    // Play the next video
                    this.playVideo(nextVideo);
                }
            };

            // Add event listeners to all videos
            freshVideos.forEach(video => {
                // Direct event assignment (works in most browsers)
                video.onended = function () {
                    console.log('Video ended via onended property');
                    advanceToNextVideo(this);
                };

                // Event listener (backup method)
                video.addEventListener('ended', function () {
                    console.log('Video ended via event listener');
                    advanceToNextVideo(this);
                });

                // Create special monitoring for this video
                const checkInterval = setInterval(() => {
                    // Only check if video is playing
                    if (!video.paused && video.duration > 0 && video.currentTime > 0) {
                        // If near end (within 0.3 seconds of end)
                        const nearEnd = video.currentTime >= (video.duration - 0.3);

                        if (nearEnd) {
                            console.log('Video near end detected by time monitor');
                            // Clear the interval to prevent multiple triggers
                            clearInterval(checkInterval);
                            // Advance to next
                            advanceToNextVideo(video);
                        }
                    }
                }, 200); // Check more frequently

                // Store interval ID for cleanup
                video.dataset.checkIntervalId = checkInterval;
            });
        });
    },

    /**
     * Set up modal triggers for videos
     */
    setupModalTriggers: function () {
        // Handle general modal triggers
        const modalTriggers = document.querySelectorAll('.open-modal, [data-target="#popup-video-playlist"]');

        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                if (e.preventDefault) e.preventDefault();

                // Get target modal ID
                const modalId = trigger.getAttribute('data-modal') ||
                    (trigger.getAttribute('data-target') || '').replace('#', '');
                if (!modalId) return;

                const modal = document.getElementById(modalId);
                if (!modal) return;

                // Show the modal
                modal.classList.add('active');

                // Handle specific video if data-item is provided
                const dataItem = trigger.getAttribute('data-item');
                if (dataItem) {
                    this.showSpecificVideo(modal, dataItem);
                } else {
                    // Otherwise show first video
                    this.resetToFirstVideo(modal);
                }
            });
        });

        // Handle .stacked-af .feature-icons
        const featureIcons = document.querySelectorAll('.stacked-af .feature-icons [data-item]');

        featureIcons.forEach(icon => {
            icon.addEventListener('click', (e) => {
                e.preventDefault();

                const dataItem = icon.getAttribute('data-item');
                const modalId = 'popup-video-playlist';
                const modal = document.getElementById(modalId);

                if (!modal) return;

                // Show the modal
                modal.classList.add('active');

                // Show the specific video
                this.showSpecificVideo(modal, dataItem);
            });
        });

        console.log(`Set up ${modalTriggers.length} modal triggers and ${featureIcons.length} feature icons`);
    },

    /**
     * Show a specific video in a modal
     */
    showSpecificVideo: function (modal, dataItem) {
        const videoWrappers = modal.querySelectorAll('.play-list-container .video-wrapper');
        if (!videoWrappers.length) {
            console.log('No video wrappers found in modal');
            return;
        }

        console.log(`Showing video with data-item: ${dataItem}`);

        // Hide all videos and pause them
        videoWrappers.forEach(wrapper => {
            wrapper.style.display = 'none';
            const video = wrapper.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
        });

        // Find and show the specific video
        const targetWrapper = modal.querySelector(`.video-wrapper[data-item="${dataItem}"]`);
        if (targetWrapper) {
            // Make sure the wrapper is visible
            targetWrapper.style.display = 'block';

            // Play the video
            const video = targetWrapper.querySelector('video');
            if (video) {
                // Set as current active video
                this.currentActiveVideo = video;

                // Reset and prepare to play
                video.currentTime = 0;

                // Play the video
                this.playVideo(video);

                console.log(`Showing video: ${dataItem}`);
            } else {
                console.log(`Video element not found in wrapper with data-item=${dataItem}`);
            }
        } else {
            console.log(`No video wrapper found with data-item=${dataItem}, falling back to first video`);
            this.resetToFirstVideo(modal);
        }
    },

    /**
     * Simple play video method 
     */
    playVideo: function (videoEl) {
        if (!videoEl) return;

        console.log('Playing video');

        // Simple play with basic error handling
        videoEl.play().catch(error => {
            console.log('Error playing video:', error);
        });
    },

    /**
     * Reset a modal to show the first video
     */
    resetToFirstVideo: function (modal) {
        const videoWrappers = modal.querySelectorAll('.play-list-container .video-wrapper');
        if (!videoWrappers.length) return;

        // Hide all videos and pause them
        videoWrappers.forEach((wrapper, index) => {
            wrapper.style.display = index === 0 ? 'block' : 'none';
            const video = wrapper.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;

                // Play first video
                if (index === 0) {
                    this.currentActiveVideo = video;
                    setTimeout(() => {
                        this.playVideo(video);
                    }, 100);
                }
            }
        });

        console.log('Reset to first video');
    },

    /**
     * Set up global ESC key handler to close any modal
     */
    setupGlobalEscHandler: function () {
        // Handle ESC key to close any modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Find any active modals
                const activeModals = document.querySelectorAll('.overlay.active');

                // Close all of them
                activeModals.forEach(modal => {
                    // Close the modal
                    modal.classList.remove('active');

                    // Stop all videos in the modal
                    const videos = modal.querySelectorAll('video');
                    videos.forEach(video => {
                        video.pause();
                        video.currentTime = 0;
                    });
                });

                if (activeModals.length > 0) {
                    console.log(`Closed ${activeModals.length} modals using ESC key`);
                }
            }
        });

        // Handle close button clicks
        const closeBtns = document.querySelectorAll('.overlay .close-btn');
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.overlay');
                if (!modal) return;

                // Close the modal
                modal.classList.remove('active');

                // Stop all videos
                const videos = modal.querySelectorAll('video');
                videos.forEach(video => {
                    video.pause();
                    video.currentTime = 0;
                });
            });
        });

        // Handle click on modal background
        const overlays = document.querySelectorAll('.overlay');
        overlays.forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    // Close the modal
                    overlay.classList.remove('active');

                    // Stop all videos
                    const videos = overlay.querySelectorAll('video');
                    videos.forEach(video => {
                        video.pause();
                        video.currentTime = 0;
                    });
                }
            });
        });

        console.log(`Set up modal close handlers for ${closeBtns.length} close buttons`);
    }
};

/**
 * =======================
 * Telekinetics Masonry Module
 * =======================
 * Handles Pinterest-style masonry grid for telekinetics images
 */
const TelekineticsMasonry = {
    /**
     * Initialize telekinetics masonry grid
     */
    init: function () {
        this.gridContainer = document.querySelector('.telekinetics .masonry-grid');
        this.cards = document.querySelectorAll('.telekinetics .card');

        if (!this.gridContainer || this.cards.length === 0) {
            console.log('Telekinetics grid not found, skipping initialization');
            return;
        }

        this.setupResizeListener();

        console.log('Telekinetics Pinterest-style Masonry initialized');
    },

    /**
     * Setup resize listener for responsive masonry
     */
    setupResizeListener: function () {
        let resizeTimer;
        let currentBreakpoint = this.getCurrentBreakpoint();

        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const newBreakpoint = this.getCurrentBreakpoint();
                if (newBreakpoint !== currentBreakpoint) {
                    currentBreakpoint = newBreakpoint;
                    this.refreshLayout();
                }
            }, 150);
        });
    },

    /**
     * Get current responsive breakpoint based on _variables.scss
     */
    getCurrentBreakpoint: function () {
        const width = window.innerWidth;
        if (width >= 1400) return 'xxl';  // xxl: 1400px
        if (width >= 1200) return 'xl';   // xl: 1200px
        if (width >= 992) return 'lg';    // lg: 992px
        if (width >= 768) return 'md';    // md: 768px
        if (width >= 576) return 'sm';    // sm: 576px
        return 'xs';                      // xs: 320px (default for below 576px)
    },

    /**
     * Refresh masonry layout on resize
     */
    refreshLayout: function () {
        if (!this.gridContainer) return;

        // Force complete reflow by temporarily hiding and showing
        this.gridContainer.style.opacity = '0';
        this.gridContainer.style.transition = 'none';

        // Force immediate reflow
        this.gridContainer.offsetHeight;

        // Reset any inline styles that might interfere
        this.gridContainer.style.columnCount = '';
        this.gridContainer.style.columnGap = '';

        // Trigger another reflow
        this.gridContainer.offsetHeight;

        // Restore visibility with transition
        setTimeout(() => {
            this.gridContainer.style.transition = 'opacity 0.3s ease';
            this.gridContainer.style.opacity = '1';

            console.log('Masonry layout refreshed for breakpoint:', this.getCurrentBreakpoint());
        }, 10);
    }
};

/**
 * =======================
 * SVG Model Animation Module
 * =======================
 * Handles animation from initial to final state for SVG images
 */
const SVGModelAnimations = {
    /**
     * Initialize SVG model animations
     */
    init: function () {
        this.setupScrollObserver();
        this.setupAnimationStyles();
        console.log('SVG Model Animations initialized');
    },

    /**
     * Setup CSS animation styles
     */
    setupAnimationStyles: function () {
        const style = document.createElement('style');
        style.textContent = `
            .model-banner svg image {
                transition: all 1.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                transform-origin: center center;
            }
            
            .model-banner svg image.animating {
                transition-delay: var(--animation-delay, 0s);
            }
        `;
        document.head.appendChild(style);
        console.log('Animation styles added');
    },

    /**
     * Setup intersection observer for scroll-triggered animation
     */
    setupScrollObserver: function () {
        const modelBanner = document.querySelector('.model-banner');
        if (!modelBanner) {
            console.warn('.model-banner not found');
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.startAnimation();
                    }
                });
            },
            {
                threshold: 0.3, // Trigger when 30% visible
                rootMargin: '0px 0px -100px 0px' // Start animation before fully visible
            }
        );

        observer.observe(modelBanner);
        console.log('Scroll observer setup for model banner');
    },

    /**
     * Start the animation sequence
     */
    startAnimation: function () {
        const svgImages = document.querySelectorAll('.model-banner svg image');

        if (svgImages.length === 0) {
            console.warn('No SVG images found');
            return;
        }

        svgImages.forEach((image, index) => {
            // Read animation delay from the image attribute
            const delayAttribute = image.getAttribute('animation-delay');
            const delay = delayAttribute ? parseFloat(delayAttribute) : 0;

            // Set the animation delay as CSS custom property
            image.style.setProperty('--animation-delay', `${delay}s`);
            image.classList.add('animating');

            // Start animation after delay
            setTimeout(() => {
                this.animateImageToFinal(image);
            }, delay * 1000);

            const color = this.getColorFromHref(image.getAttribute('href'));
            console.log(`Scheduled ${color} image animation with ${delay}s delay`);
        });

        console.log('Animation sequence started');
    },

    /**
     * Animate a single image to its final state
     */
    animateImageToFinal: function (image) {
        const finalX = image.getAttribute('final-x');
        const finalY = image.getAttribute('final-y');
        const finalWidth = image.getAttribute('final-width');

        if (!finalX || !finalY || !finalWidth) {
            console.warn('Missing final attributes for image:', image);
            return;
        }

        // Apply final position and size
        image.setAttribute('x', finalX);
        image.setAttribute('y', finalY);
        image.setAttribute('width', finalWidth);

        const color = this.getColorFromHref(image.getAttribute('href'));
        console.log(`Animated ${color} image to final state: x=${finalX}, y=${finalY}, width=${finalWidth}`);
    },

    /**
     * Extract color from image href
     */
    getColorFromHref: function (href) {
        if (!href) return 'unknown';

        if (href.includes('/black/')) return 'black';
        if (href.includes('/blue/')) return 'blue';
        if (href.includes('/white/')) return 'white';
        if (href.includes('/gray/')) return 'gray';
        if (href.includes('/red/')) return 'red';

        return 'unknown';
    },

    /**
     * Reset all images to initial state
     */
    resetToInitial: function () {
        const svgImages = document.querySelectorAll('.model-banner svg image');

        svgImages.forEach(image => {
            // Get initial values (current x, y, width should be initial)
            const initialX = image.getAttribute('x');
            const initialY = image.getAttribute('y');
            const initialWidth = image.getAttribute('width');

            // Store initial values if not already stored
            if (!image.dataset.initialX) {
                image.dataset.initialX = initialX;
                image.dataset.initialY = initialY;
                image.dataset.initialWidth = initialWidth;
            }

            // Reset to initial
            image.setAttribute('x', image.dataset.initialX);
            image.setAttribute('y', image.dataset.initialY);
            image.setAttribute('width', image.dataset.initialWidth);
            image.classList.remove('animating');
        });

        console.log('Reset all images to initial state');
    },

    /**
     * Update animation delay for a specific image
     * @param {number} imageIndex - Index of the image (0-based)
     * @param {number} delay - New delay in seconds
     */
    updateImageDelay: function (imageIndex, delay) {
        const svgImages = document.querySelectorAll('.model-banner svg image');
        if (imageIndex >= 0 && imageIndex < svgImages.length) {
            const image = svgImages[imageIndex];
            image.setAttribute('animation-delay', delay.toString());
            console.log(`Updated image ${imageIndex} delay to ${delay}s`);
        }
    },

    /**
     * Update delays for all images
     * @param {number[]} delays - Array of delays in seconds
     */
    updateAllDelays: function (delays) {
        const svgImages = document.querySelectorAll('.model-banner svg image');
        delays.forEach((delay, index) => {
            if (index < svgImages.length) {
                svgImages[index].setAttribute('animation-delay', delay.toString());
            }
        });
        console.log('Updated all animation delays:', delays);
    },

    /**
     * Manual trigger for testing
     */
    triggerAnimation: function () {
        this.startAnimation();
    }
};


/**
 * =======================
 * Initialize on DOM Ready
 * =======================
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM Content Loaded - Initializing modules');

    // Initialize slider functionality
    SliderManager.initializeSlider();

    // Initialize resize listener for slider refresh
    SliderManager.initializeResizeListener();

    // Setup specs slider color picker functionality 
    setTimeout(() => {
        SliderManager.setupSpecsSliderColorPicker();
    }, 100);

    // Initialize 360 viewer color picker
    Viewer360ColorPicker.init();

    // Initialize choose-variant 360 color picker (if present)
    ChooseVariant360ColorPicker.init();

    // Initialize legend form manager (if present)
    LegendFormManager.init();

    // Initialize EMI calculator (if present)
    EmiCalculator.init();


    // Initialize telekinetics masonry grid
    TelekineticsMasonry.init();

    // Initialize video playlist manager
    VideoPlaylistManager.init();

    // Initialize SVG model animations
    SVGModelAnimations.init();

    // Initialize URL hash scrolling
    URLHashScrollHandler.init();

    // Initialize pincode address autocomplete
    PincodeAddressAutocomplete.init();

    // Initialize pincode city restriction
    PincodeCityRestriction.init();

    // Initialize booking form handler
    BookingFormHandler.init();

    console.log('All modules initialized');
});

// Export for global access if needed
window.SliderManager = SliderManager;
window.Viewer360ColorPicker = Viewer360ColorPicker;
window.TelekineticsMasonry = TelekineticsMasonry;
window.VideoPlaylistManager = VideoPlaylistManager;
window.UIUtils = UIUtils;
window.SVGModelAnimations = SVGModelAnimations;
window.URLHashScrollHandler = URLHashScrollHandler;
window.PincodeAddressAutocomplete = PincodeAddressAutocomplete;
window.PincodeCityRestriction = PincodeCityRestriction;
window.BookingFormHandler = BookingFormHandler;
window.getBaseUrl = getBaseUrl;

// =======================
// Fallback for --features timeline-driven animations (for all devices without animation-timeline support)
// =======================
(function () {
    function supportsAnimationTimeline() {
        return CSS.supports('animation-timeline: auto');
    }
    if (supportsAnimationTimeline()) return;

    document.addEventListener('DOMContentLoaded', function () {
        var timelineSection = document.querySelector('.stacked-360-wrapper .screen.section-area-scroll');
        if (!timelineSection) return;
        var titleImg = document.querySelector('.stacked-360-wrapper .scroll-area-title img');
        var colorPicker = document.querySelector('.stacked-360-wrapper .color-picker');
        var featureLeft = document.querySelectorAll('.feature-left li');
        var featureRight = document.querySelectorAll('.feature-right li');
        var steps = 9;
        var fadeCut = 0.4; // 40%
        var slot = (1 - fadeCut) / steps;

        function clamp(val, min, max) {
            return Math.max(min, Math.min(max, val));
        }

        function getProgress() {
            var rect = timelineSection.getBoundingClientRect();
            var windowH = window.innerHeight || document.documentElement.clientHeight;
            var sectionH = rect.height;
            var entry = windowH - rect.top; // px of section visible
            var progress = (entry) / (sectionH); // 0 (entry) to 1 (cover)
            return clamp(progress, 0, 1);
        }

        function animate() {
            var progress = getProgress();
            // 1. Title image fade: 0% to 25%
            if (titleImg) {
                var fadeStart = 0.0, fadeEnd = 0.25;
                var fadeProg = clamp((progress - fadeStart) / (fadeEnd - fadeStart), 0, 1);
                titleImg.style.transition = 'opacity 0.1s linear';
                titleImg.style.opacity = 1 - fadeProg;
            }
            // 2. Color picker reveal: 10% to 12%
            if (colorPicker) {
                var cpStart = 0.10, cpEnd = 0.12;
                var cpProg = clamp((progress - cpStart) / (cpEnd - cpStart), 0, 1);
                colorPicker.style.transition = 'opacity 0.1s linear, visibility 0.1s linear';
                colorPicker.style.opacity = cpProg;
                colorPicker.style.visibility = cpProg > 0 ? 'visible' : 'hidden';
            }
            // 3. Feature li animations: 9 slots per side, 40% to 100%
            for (var i = 0; i < steps; i++) {
                var slotStart = fadeCut + i * slot;
                var slotEnd = fadeCut + (i + 1) * slot;
                var slotProg = clamp((progress - slotStart) / (slotEnd - slotStart), 0, 1);
                // Animate left li
                if (featureLeft[i]) {
                    featureLeft[i].style.transition = 'opacity 0.1s linear, transform 0.1s linear';
                    featureLeft[i].style.opacity = slotProg;
                    var tx = (1 - slotProg) * -50;
                    featureLeft[i].style.transform = 'translateX(' + tx + 'px)';
                }
                // Animate right li
                if (featureRight[i]) {
                    featureRight[i].style.transition = 'opacity 0.1s linear, transform 0.1s linear';
                    featureRight[i].style.opacity = slotProg;
                    var txr = (1 - slotProg) * 50;
                    featureRight[i].style.transform = 'translateX(' + txr + 'px)';
                }
            }
        }
        window.addEventListener('scroll', animate, { passive: true });
        window.addEventListener('resize', animate);
        animate();
    });
})();

/**
 * =======================
 * Form Validation Module
 * =======================
 * Handles real-time form validation with custom error messages
 */
const FormValidator = {
    validationRules: {
        required: (value) => value.trim() !== '',
        email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        indian_mobile: (value) => {
            // Remove all non-digit characters
            const cleaned = value.replace(/\D/g, '');
            
            // Check for 10 digits (direct mobile number)
            if (/^[6-9]\d{9}$/.test(cleaned)) {
                return true;
            }
            
            // Check for 12-13 digits with country code (91 prefix)
            if (/^91[6-9]\d{9}$/.test(cleaned)) {
                return true;
            }
            
            return false;
        },
        indian_pincode: (value) => /^[1-9][0-9]{5}$/.test(value),
        alphabets_only: (value) => /^[a-zA-Z\s\-]+$/.test(value),
        min_length: (value, length) => value.length >= parseInt(length),
        max_length: (value, length) => value.length <= parseInt(length)
    },

    init: function () {
        // Initialize validation for all forms
        document.addEventListener('DOMContentLoaded', () => {
            this.initFormValidation();
        });

        // Re-initialize if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initFormValidation();
            });
        } else {
            this.initFormValidation();
        }

        console.log('Form Validator initialized');
    },

    initFormValidation: function () {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            this.setupFormValidation(form);
        });
    },

    setupFormValidation(form) {
        const fields = form.querySelectorAll('[data-validation]');

        fields.forEach(field => {
            // Special handling for radio button groups
            if (field.classList.contains('color-options') || field.classList.contains('variant-options')) {
                const radioButtons = field.querySelectorAll('input[type="radio"]');
                radioButtons.forEach(radio => {
                    radio.addEventListener('change', () => this.validateField(field));
                });
            }
            // Special handling for checkboxes
            else if (field.type === 'checkbox') {
                field.addEventListener('change', () => this.validateField(field));
            }
            // Regular fields
            else {
                field.addEventListener('input', () => this.validateField(field));
                field.addEventListener('change', () => this.validateField(field));
                field.addEventListener('blur', () => this.validateField(field));
            }
        });

        // Validate entire form on submit
        form.addEventListener('submit', (e) => {
            console.log('Form submission detected:', form);

            // Always run validation for all forms
            const isValid = this.validateForm(form);

            if (!isValid) {
                console.log('Form validation failed, preventing submission');
                e.preventDefault();
                this.focusFirstError(form);

                // Add visual feedback for failed submission
                this.showSubmissionError(form);
                return false;
            }

            console.log('Form validation passed');

            // For non-AJAX forms, allow normal submission
            if (!form.hasAttribute('ajax-updated')) {
                console.log('Allowing normal form submission');
                return true;
            }
        });
    },

    showSubmissionError(form) {
        // Remove any existing error messages
        const existingError = form.querySelector('.form-submission-error');
        if (existingError) {
            existingError.remove();
        }

        // Add general error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-submission-error';
        errorDiv.style.cssText = 'color: #d32f2f; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 14px;';
        errorDiv.textContent = 'Please correct the errors above and try again.';

        // Insert at the top of the form
        form.insertBefore(errorDiv, form.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    },

    validateField(field) {
        const validationRules = field.getAttribute('data-validation');
        if (!validationRules) return true;

        const rules = validationRules.split(',');
        let value = field.value;
        let formGroup = field.closest('.form-group');
        let errorContainer = formGroup?.querySelector('.error-message');

        // Special handling for radio button groups
        if (field.classList.contains('color-options') || field.classList.contains('variant-options')) {
            const checkedRadio = field.querySelector('input[type="radio"]:checked');
            value = checkedRadio ? checkedRadio.value : '';
        }
        // Special handling for checkboxes
        else if (field.type === 'checkbox') {
            value = field.checked ? 'checked' : '';
        }

        // Clear previous validation state
        this.clearFieldValidation(field, formGroup, errorContainer);

        for (const rule of rules) {
            const [ruleName, ruleParam] = rule.trim().split(':');
            const validator = this.validationRules[ruleName];

            if (!validator) continue;

            // Special handling for checkbox required validation
            if (ruleName === 'required' && field.type === 'checkbox') {
                const isValid = field.checked;
                if (!isValid) {
                    const errorMessage = this.getErrorMessage(field, ruleName);
                    this.showFieldError(field, formGroup, errorContainer, errorMessage);
                    return false;
                }
            } else {
                const isValid = ruleParam ? validator(value, ruleParam) : validator(value);
                if (!isValid) {
                    const errorMessage = this.getErrorMessage(field, ruleName);
                    this.showFieldError(field, formGroup, errorContainer, errorMessage);
                    return false;
                }
            }
        }

        // Field is valid
        this.showFieldSuccess(field, formGroup);
        return true;
    },

    validateForm(form) {
        const fields = form.querySelectorAll('[data-validation]');
        let isFormValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isFormValid = false;
            }
        });

        return isFormValid;
    },

    getErrorMessage(field, ruleName) {
        const customMessage = field.getAttribute(`data-error-${ruleName}`);
        if (customMessage) return customMessage;

        // Default error messages
        const defaultMessages = {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            indian_mobile: 'Please enter a valid 10-digit mobile number (with or without country code)',
            indian_pincode: 'Please enter a valid 6-digit pin code',
            alphabets_only: 'Only letters and spaces are allowed',
            min_length: 'This field is too short',
            max_length: 'This field is too long'
        };

        return defaultMessages[ruleName] || 'Invalid input';
    },

    showFieldError(field, formGroup, errorContainer, message) {
        // Special handling for city, state, and pincode fields - show errors below .flex container
        const fieldName = field.getAttribute('name');
        if (['city', 'state', 'pincode'].includes(fieldName)) {
            const flexContainer = field.closest('.flex');
            if (flexContainer) {
                // Check if there's already a shared error container below the .flex
                let sharedErrorContainer = flexContainer.nextElementSibling;
                if (!sharedErrorContainer || !sharedErrorContainer.classList.contains('flex-error-container')) {
                    // Create shared error container for the .flex group
                    sharedErrorContainer = document.createElement('div');
                    sharedErrorContainer.className = 'flex-error-container';
                    sharedErrorContainer.style.cssText = 'margin-top: 8px; margin-bottom: 8px;';
                    flexContainer.parentNode.insertBefore(sharedErrorContainer, flexContainer.nextSibling);
                }

                // Remove any existing error for this field
                const existingError = sharedErrorContainer.querySelector(`.field-error-${fieldName}`);
                if (existingError) {
                    existingError.remove();
                }

                // Create individual error element for this field
                const errorEl = document.createElement('div');
                errorEl.className = `error-message field-error-${fieldName}`;
                errorEl.style.cssText = 'color: #dc3545; font-size: 0.875em; margin-bottom: 4px; padding: 4px 8px; background-color: rgba(220, 53, 69, 0.1); border-radius: 4px; border-left: 3px solid #dc3545;';
                
                // Get field label text
                const label = field.closest('.form-group')?.querySelector('label');
                const labelText = label?.textContent || fieldName;
                errorEl.textContent = `${labelText}: ${message}`;

                // Add to shared container
                sharedErrorContainer.appendChild(errorEl);

                // Still add error class to form group for styling
                if (formGroup) {
                    formGroup.classList.add('has-error');
                    formGroup.classList.remove('has-success');
                }
                return;
            }
        }

        // Normal handling for other fields
        if (formGroup) {
            formGroup.classList.add('has-error');
            formGroup.classList.remove('has-success');
        }

        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
        }
    },

    showFieldSuccess(field, formGroup) {
        if (formGroup) {
            formGroup.classList.add('has-success');
            formGroup.classList.remove('has-error');
        }
    },

    clearFieldValidation(field, formGroup, errorContainer) {
        // Special handling for city, state, and pincode fields
        const fieldName = field.getAttribute('name');
        if (['city', 'state', 'pincode'].includes(fieldName)) {
            const flexContainer = field.closest('.flex');
            if (flexContainer) {
                const sharedErrorContainer = flexContainer.nextElementSibling;
                if (sharedErrorContainer && sharedErrorContainer.classList.contains('flex-error-container')) {
                    // Remove error for this specific field
                    const fieldError = sharedErrorContainer.querySelector(`.field-error-${fieldName}`);
                    if (fieldError) {
                        fieldError.remove();
                    }
                    
                    // If no more errors in the shared container, remove it
                    if (sharedErrorContainer.children.length === 0) {
                        sharedErrorContainer.remove();
                    }
                }
            }
        }

        // Normal clearing for all fields
        if (formGroup) {
            formGroup.classList.remove('has-error', 'has-success');
        }

        if (errorContainer) {
            errorContainer.style.display = 'none';
            errorContainer.textContent = '';
        }
    },

    focusFirstError(form) {
        const firstErrorField = form.querySelector('.has-error input, .has-error select, .has-error textarea');
        if (firstErrorField) {
            firstErrorField.focus();
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

/**
 * =======================
 * AJAX Form Handler Module (replicated from _index.js)
 * =======================
 * Handles AJAX form submissions for forms with [ajax-updated] attribute
 */
const AjaxFormHandler = {
    init: function () {
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        this.setupMessageAutoRemoval();
        console.log('AJAX Form Handler initialized');
    },
    handleFormSubmit: async function (event) {
        const form = event.target;
        if (!form.hasAttribute('ajax-updated')) return;

        event.preventDefault();
        console.log('AJAX form submission detected:', form);

        // Check if form validation passes before submitting via AJAX
        if (typeof FormValidator !== 'undefined' && !FormValidator.validateForm(form)) {
            console.log('Form validation failed, preventing AJAX submission');
            FormValidator.focusFirstError(form);

            // Show client-side validation error message
            this.showFormMessage(form, 'Please correct the errors below and try again.', 'error');
            return;
        }

        try {
            const action = form.getAttribute('action') || window.location.href;
            const method = form.getAttribute('method') || 'POST';
            const formData = this.formToJson(form);
            console.log('Form data:', formData);
            this.setFormLoading(form, true);
            const response = await this.submitForm(action, method, formData);
            console.log('Form submission successful:', response);
            this.updateFormArea(form, response);
        } catch (error) {
            this.handleFormError(form, error);
        } finally {
            this.setFormLoading(form, false);
        }
    },
    formToJson: function (form) {
        const formData = new FormData(form);
        const json = {};
        for (const [key, value] of formData.entries()) {
            // Sanitize phone fields
            let processedValue = value;
            if (key === 'phone' || key === 'mobile') {
                processedValue = UIUtils.sanitizePhoneNumber(value);
                console.log('Phone field sanitized:', value, '->', processedValue);
            }
            
            if (json[key]) {
                if (Array.isArray(json[key])) {
                    json[key].push(processedValue);
                } else {
                    json[key] = [json[key], processedValue];
                }
            } else {
                json[key] = processedValue;
            }
        }
        const uncheckedInputs = form.querySelectorAll('input[type="checkbox"]:not(:checked), input[type="radio"]:not(:checked)');
        uncheckedInputs.forEach(input => {
            if (input.type === 'checkbox' && !json.hasOwnProperty(input.name)) {
                json[input.name] = false;
            }
        });
        return json;
    },
    submitForm: async function (action, method, data) {
        const options = {
            method: method.toUpperCase(),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        if (["POST", "PUT", "PATCH"].includes(options.method)) {
            // Send as form data (not JSON) so PHP can read $_POST
            const formData = new FormData();
            Object.entries(data).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach(v => formData.append(key + '[]', v));
                } else {
                    formData.append(key, value);
                }
            });
            options.body = formData;
        }
        const response = await fetch(action, options);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        } else {
            return await response.text();
        }
    },
    updateFormArea: function (form, response) {
        console.log('Updating form area with response');
        if (typeof response === 'string') {
            if (response.trim().startsWith('<')) {
                form.outerHTML = response;
                console.log('Form area updated with HTML response');
                this.reinitializeFormHandlers();
                this.setupMessageAutoRemoval();
                return;
            }
        }
        if (typeof response === 'object') {
            if (response.html) {
                form.outerHTML = response.html;
                console.log('Form area updated with JSON HTML response');
                this.reinitializeFormHandlers();
                this.setupMessageAutoRemoval();
                return;
            }
            if (response.success === true) {
                this.showFormMessage(form, response.message || 'Form submitted successfully', 'success');
                if (response.reset !== false) {
                    form.reset();
                }
                if (response.redirect) {
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, response.redirectDelay || 1000);
                }
                return;
            }
            if (response.success === false || response.errors) {
                console.log('Server returned validation/error response:', response);
                this.showFormMessage(form, response.message || 'Please correct the errors and try again.', 'error');
                if (response.errors) {
                    this.showFormErrors(form, response.errors);
                }
                return;
            }
        }
        this.showFormMessage(form, 'Form submitted successfully', 'success');
        form.reset();
    },
    showFormMessage(form, message, type = 'info') {
        const existingMessages = form.querySelectorAll('.form-message');
        existingMessages.forEach(msg => msg.remove());
        const messageEl = document.createElement('div');
        messageEl.className = `form-message form-message-${type}`;
        messageEl.style.cssText = `
            padding: 12px 16px;
            margin: 16px 0;
            border-radius: 4px;
            font-weight: 500;
            ${type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : ''}
            ${type === 'error' ? 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : ''}
            ${type === 'info' ? 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;' : ''}
        `;
        messageEl.textContent = message;
        form.insertBefore(messageEl, form.firstChild);
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 15000);
    },
    setupMessageAutoRemoval: function () {
        const messages = document.querySelectorAll('.success-message, .error-message, .form-message');
        messages.forEach(message => {
            if (!message.hasAttribute('data-auto-remove-set')) {
                message.setAttribute('data-auto-remove-set', 'true');
                setTimeout(() => {
                    if (message.parentNode) {
                        message.remove();
                        this.clearAllFormErrors();
                    }
                }, 15000);
            }
        });
    },
    clearAllFormErrors: function () {
        document.querySelectorAll('.field-error, .flex-error-container').forEach(error => error.remove());
        document.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
    },
    showFormErrors: function (form, errors) {
        console.log('Showing form errors:', errors);

        // Clear existing errors
        form.querySelectorAll('.field-error, .error-message, .flex-error-container').forEach(error => error.remove());
        form.querySelectorAll('.error, .has-error').forEach(field => field.classList.remove('error', 'has-error'));

        // Show general error message if provided
        if (errors.message) {
            this.showFormMessage(form, errors.message, 'error');
        }

        // Handle field-specific errors
        const fieldErrors = errors.errors || errors.field_errors || errors;
        if (typeof fieldErrors === 'object') {
            Object.keys(fieldErrors).forEach(fieldName => {
                if (fieldName === 'message') return; // Skip the general message

                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    console.log(`Setting error for field ${fieldName}:`, fieldErrors[fieldName]);

                    // Add error class to field
                    field.classList.add('error');

                    // Special handling for city, state, and pincode fields - show errors below .flex container
                    if (['city', 'state', 'pincode'].includes(fieldName)) {
                        const flexContainer = field.closest('.flex');
                        if (flexContainer) {
                            // Check if there's already a shared error container below the .flex
                            let sharedErrorContainer = flexContainer.nextElementSibling;
                            if (!sharedErrorContainer || !sharedErrorContainer.classList.contains('flex-error-container')) {
                                // Create shared error container for the .flex group
                                sharedErrorContainer = document.createElement('div');
                                sharedErrorContainer.className = 'flex-error-container';
                                sharedErrorContainer.style.cssText = 'margin-top: 8px; margin-bottom: 8px;';
                                flexContainer.parentNode.insertBefore(sharedErrorContainer, flexContainer.nextSibling);
                            }

                            // Create individual error element for this field
                            const errorEl = document.createElement('div');
                            errorEl.className = `error-message field-error-${fieldName}`;
                            errorEl.style.cssText = 'color: #dc3545; font-size: 0.875em; margin-bottom: 4px; padding: 4px 8px; background-color: rgba(220, 53, 69, 0.1); border-radius: 4px; border-left: 3px solid #dc3545;';
                            
                            // Get field label text
                            const label = field.closest('.form-group')?.querySelector('label');
                            const labelText = label?.textContent || fieldName;
                            errorEl.textContent = `${labelText}: ${Array.isArray(fieldErrors[fieldName]) ? fieldErrors[fieldName][0] : fieldErrors[fieldName]}`;

                            // Add to shared container
                            sharedErrorContainer.appendChild(errorEl);
                        } else {
                            // Fallback to normal behavior if .flex container not found
                            const formGroup = field.closest('.form-group');
                            if (formGroup) {
                                formGroup.classList.add('has-error');
                                let errorContainer = formGroup.querySelector('.error-message');
                                if (errorContainer) {
                                    errorContainer.textContent = Array.isArray(fieldErrors[fieldName]) ? fieldErrors[fieldName][0] : fieldErrors[fieldName];
                                    errorContainer.style.display = 'block';
                                    errorContainer.style.color = '#dc3545';
                                }
                            }
                        }
                    } else {
                        // Normal handling for other fields
                        // Add error class to form group if it exists
                        const formGroup = field.closest('.form-group');
                        if (formGroup) {
                            formGroup.classList.add('has-error');

                            // Find existing error container or create new one
                            let errorContainer = formGroup.querySelector('.error-message');
                            if (errorContainer) {
                                errorContainer.textContent = Array.isArray(fieldErrors[fieldName]) ? fieldErrors[fieldName][0] : fieldErrors[fieldName];
                                errorContainer.style.display = 'block';
                                errorContainer.style.color = '#dc3545';
                            } else {
                                // Create new error element
                                const errorEl = document.createElement('div');
                                errorEl.className = 'error-message';
                                errorEl.style.cssText = 'color: #dc3545; font-size: 0.875em; margin-top: 4px; display: block;';
                                errorEl.textContent = Array.isArray(fieldErrors[fieldName]) ? fieldErrors[fieldName][0] : fieldErrors[fieldName];

                                // Insert after the field
                                field.parentNode.insertBefore(errorEl, field.nextSibling);
                            }
                        }
                    }
                }
            });

            // Focus first error field
            const firstErrorField = form.querySelector('.error');
            if (firstErrorField) {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    },
    setFormLoading: function (form, isLoading) {
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        if (isLoading) {
            form.style.opacity = '0.7';
            form.style.pointerEvents = 'none';
            submitButtons.forEach(btn => {
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent || btn.value;
                if (btn.tagName === 'BUTTON') {
                    btn.textContent = 'Submitting...';
                } else {
                    btn.value = 'Submitting...';
                }
            });
        } else {
            form.style.opacity = '';
            form.style.pointerEvents = '';
            submitButtons.forEach(btn => {
                btn.disabled = false;
                if (btn.dataset.originalText) {
                    if (btn.tagName === 'BUTTON') {
                        btn.textContent = btn.dataset.originalText;
                    } else {
                        btn.value = btn.dataset.originalText;
                    }
                    delete btn.dataset.originalText;
                }
            });
        }
    },
    handleFormError: function (form, error) {
        console.error('Form submission error:', error);
        let errorMessage = 'An error occurred while submitting the form.';
        if (error.message) {
            errorMessage = error.message;
        }
        this.showFormMessage(form, errorMessage, 'error');
    },
    reinitializeFormHandlers: function () {
        // Add any re-initialization logic if needed
        console.log('Re-initialized form handlers for updated content');
    }
};

// Initialize Form Validator
FormValidator.init();

// Initialize AJAX Form Handler
AjaxFormHandler.init();

/**
 * Video Play Button Handler
 * Shows a play button overlay when video is paused
 * Works with video controls enabled
 */
const VideoPlayButtonHandler = {
    init: function () {
        // Find all video wrappers on the page
        const videoWrappers = document.querySelectorAll('.video-wrapper');

        console.log('Video Play Button Handler initialized, found wrappers:', videoWrappers.length);

        // First, remove any existing click handlers to prevent duplicates
        videoWrappers.forEach(wrapper => {
            const clone = wrapper.cloneNode(true);
            wrapper.parentNode.replaceChild(clone, wrapper);
        });

        // Re-query after cloning
        const refreshedWrappers = document.querySelectorAll('.video-wrapper');

        refreshedWrappers.forEach(wrapper => {
            const video = wrapper.querySelector('video');

            if (!video) return;

            console.log('Initial video state:', video.paused ? 'paused' : 'playing');

            // Explicitly set initial state - default to showing play button
            // Force paused state initially to ensure play button shows
            wrapper.classList.remove('playing');
            console.log('Video initially set to paused state - showing play button');

            // Play/pause event listeners
            video.addEventListener('play', () => {
                wrapper.classList.add('playing');
                console.log('Video playing - hiding play button');
            });

            video.addEventListener('pause', () => {
                wrapper.classList.remove('playing');
                console.log('Video paused - showing play button');
            });

            video.addEventListener('ended', () => {
                wrapper.classList.remove('playing');
                console.log('Video ended - showing play button');

                // Check if this video is part of a playlist
                const playlistContainer = wrapper.closest('.play-list-container');
                if (playlistContainer) {
                    // Find all videos in this playlist
                    const allWrappers = Array.from(playlistContainer.querySelectorAll('.video-wrapper'));
                    if (allWrappers.length > 1) {
                        // Find current video index
                        const currentIndex = allWrappers.indexOf(wrapper);
                        if (currentIndex !== -1) {
                            // Calculate next index with looping
                            const nextIndex = (currentIndex + 1) % allWrappers.length;

                            // Hide all videos
                            allWrappers.forEach(w => w.style.display = 'none');

                            // Show the next video wrapper
                            allWrappers[nextIndex].style.display = 'block';

                            // Get and play the next video
                            const nextVideo = allWrappers[nextIndex].querySelector('video');
                            if (nextVideo) {
                                nextVideo.currentTime = 0;
                                console.log(`Playing next video ${nextIndex + 1} of ${allWrappers.length}`);

                                // Use the VideoPlaylistManager's method if available
                                if (window.VideoPlaylistManager && typeof window.VideoPlaylistManager.playVideo === 'function') {
                                    window.VideoPlaylistManager.playVideo(nextVideo);
                                } else {
                                    nextVideo.play().catch(err => console.log('Error playing next video:', err));
                                }
                            }
                        }
                    }
                }
            });

            // Add click handler to the wrapper but only for the overlay area
            wrapper.addEventListener('click', (e) => {
                // Only handle clicks directly on the wrapper (not on video or controls)
                if (e.target === wrapper) {
                    e.stopPropagation();

                    if (video.paused) {
                        console.log('Wrapper clicked - attempting to play video');
                        video.play().catch(e => console.warn('Video play failed:', e));
                    }
                }
            });

            // Handle visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    if (!video.paused) {
                        video.pause();
                    }
                }
            });
        });

        console.log('Video play button handler initialized with controls support');
    }
};

// Initialize the video play button handler when the DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    VideoPlayButtonHandler.init();
});

/**
 * =======================
 * Pincode City Restriction Module
 * =======================
 * Restricts pincode input to cities within 50km road distance of Mumbai and Pune
 * Uses Google Distance Matrix API with client-side caching for performance
 */
const PincodeCityRestriction = {
    // Configuration
    config: {
        GOOGLE_API_KEY: 'AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw',
        MAX_DISTANCE_KM: 50,
        CACHE_EXPIRY_HOURS: 24,
        ALLOWED_CITIES: [] // Will be populated from API
    },

    // Cache for distance results
    distanceCache: new Map(),

    // Fetch allowed cities from API
    fetchAllowedCities: async function() {
        try {
            const response = await fetch(`${getBaseUrl()}/api/get-allowed-cities.php`);
            const data = await response.json();
            if (data.success) {
                this.config.ALLOWED_CITIES = data.cities.map(city => ({
                    name: city.city_name,
                    coordinates: city.coordinates
                }));
                console.log('Fetched allowed cities:', this.config.ALLOWED_CITIES);
            } else {
                console.error('Failed to fetch allowed cities:', data.error);
            }
        } catch (error) {
            console.error('Error fetching allowed cities:', error);
        }
    },

    /**
     * Initialize pincode city restriction
     */
    init: async function () {
        // Only run on book-now page
        this.pincodeInput = document.querySelector('input[name="pincode"]');
        this.payButton = document.querySelector('.pay-button');
        if (this.payButton) {
            this.form = this.payButton.closest("form");
        }

        if (!this.pincodeInput || !this.payButton) {
            console.log('Pincode city restriction not applicable for this page');
            return;
        }

        // Fetch allowed cities before setting up validation
        await this.fetchAllowedCities();
        
        this.setupRestrictionValidation();
        this.loadCacheFromStorage();
        console.log('Pincode City Restriction initialized');
    },

    /**
     * Setup validation for pincode restriction
     */
    setupRestrictionValidation: function () {
        // Listen for pincode input changes to clear validation errors immediately
        this.pincodeInput.addEventListener('input', this.handlePincodeInput.bind(this));
        
        // Listen for pincode selection from autocomplete
        this.pincodeInput.addEventListener('pincodeSelected', this.handlePincodeSelection.bind(this));

        // Listen for manual pincode input
        this.pincodeInput.addEventListener('blur', this.debounce(this.validatePincodeManual.bind(this), 500));

        // Listen for form submission to do final validation
        const form = this.pincodeInput.closest('form');
        if (form) {
            form.addEventListener('submit', this.handleFormSubmission.bind(this));
        }
    },

    /**
     * Handle pincode input changes - clear validation errors immediately
     */
    handlePincodeInput: function () {
        // Clear pincode validation error messages as soon as user starts typing
        this.clearMessages();
    },

    /**
     * Handle pincode selection from autocomplete
     */
    handlePincodeSelection: function (event) {
        const { pincode, city, state, formattedAddress } = event.detail;
        console.log('Pincode selected from autocomplete:', { pincode, city, state });

        // Validate the selected pincode
        this.validatePincode(pincode, city, state, formattedAddress);
    },

    /**
     * Handle manual pincode input (when user types directly)
     */
    validatePincodeManual: function (event) {
        const pincode = event.target.value.trim();

        // Only validate complete 6-digit pincodes
        if (!/^\d{6}$/.test(pincode)) {
            return;
        }

        console.log('Manual pincode input detected:', pincode);

        // For manual input, we need to geocode first to get location
        this.geocodePincodeAndValidate(pincode);
    },

    /**
     * Geocode pincode to get coordinates and then validate distance
     */
    geocodePincodeAndValidate: async function (pincode) {
        try {
            // this.showValidationLoader();

            // Use our PHP proxy API instead of direct Google API call
            const response = await fetch(`${getBaseUrl()}/api/distance-check?pincode=${encodeURIComponent(pincode)}`);
            const data = await response.json();

            if (data.success && data.isAllowed !== undefined) {
                console.log('Distance validation result:', data);

                const result = {
                    minDistance: data.minDistance,
                    nearestCity: data.nearestCity,
                    isAllowed: data.isAllowed,
                    timestamp: Date.now()
                };

                // Cache the result
                const cacheKey = this.getCacheKey(data.coordinates);
                this.cacheDistance(cacheKey, result);

                // Handle the result
                this.handleDistanceResult(result, pincode, data.city, data.state, `${data.city}, ${data.state}`);
                this.hideValidationLoader();
            } else {
                this.showError(data.error || 'Unable to verify pincode location. Please check the pincode.');
                this.hideValidationLoader();
            }
        } catch (error) {
            console.error('Error geocoding pincode:', error);
            this.showError('Error verifying pincode. Please try again.');
            this.hideValidationLoader();
        }
    },

    /**
     * Main validation function for pincode distance
     */
    validatePincode: function (pincode, city, state, formattedAddress) {
        // First geocode to get coordinates
        this.geocodePincodeForValidation(pincode, city, state, formattedAddress);
    },

    /**
     * Geocode pincode specifically for validation (with city/state context)
     */
    geocodePincodeForValidation: async function (pincode, city, state, formattedAddress) {
        try {
            // this.showValidationLoader();

            // Use our PHP proxy API instead of direct Google API call
            const response = await fetch(`${getBaseUrl()}/api/distance-check.php?pincode=${encodeURIComponent(pincode)}`);
            const data = await response.json();

            if (data.success && data.isAllowed !== undefined) {
                console.log('Distance validation result:', data);

                const result = {
                    minDistance: data.minDistance,
                    nearestCity: data.nearestCity,
                    isAllowed: data.isAllowed,
                    timestamp: Date.now()
                };

                // Cache the result
                const cacheKey = this.getCacheKey(data.coordinates);
                this.cacheDistance(cacheKey, result);

                // Handle the result using provided city/state or fallback to API response
                this.handleDistanceResult(result, pincode, city || data.city, state || data.state, formattedAddress);
                this.hideValidationLoader();
            } else {
                this.showError(data.error || 'Unable to verify location. Please select a different pincode.');
                this.hideValidationLoader();
            }
        } catch (error) {
            console.error('Error geocoding for validation:', error);
            this.showError('Error verifying location. Please try again.');
            this.hideValidationLoader();
        }
    },

    /**
     * Handle distance validation result
     */
    handleDistanceResult: function (result, pincode, city, state, formattedAddress) {
        if (result.isAllowed) {
            this.showSuccess(result, pincode, city, state);
            this.enablePayButton();
        } else {
            this.showRestrictionError(result, pincode, city, state);
            this.disablePayButton();
        }
    },

    /**
     * Show success message for allowed location
     */
    showSuccess: function (result, pincode, city, state) {
        // Clear any existing messages but don't show success message
        this.clearMessages();
        
        // Log success for debugging but don't display to user
        console.log(`Location Verified: ${city}, ${state} - Distance from ${result.nearestCity}: ${result.minDistance}km`);
    },

    /**
     * Show restriction error for disallowed location
     */
    showRestrictionError: function (result, pincode, city, state) {
        this.clearMessages();
        let name = this.form.querySelector('[name="firstname"]').value ?? "";
        let email = this.form.querySelector('[name="email"]').value ?? "";
        let phone = this.form.querySelector('[name="phone"]').value ?? "";
        let intent = "enquiry";
        
        // Sanitize phone number for consistency
        const sanitizedPhone = UIUtils.sanitizePhoneNumber(phone);
        
        // Check if phone is verified
        const isPhoneVerified = this.checkPhoneVerificationStatus(phone);
        const verifiedParam = isPhoneVerified ? '&verified=1' : '&verified=0';
        
        console.log('[' + new Date().toLocaleTimeString() + '] Generating restriction error link:', {
            phone: phone,
            sanitizedPhone: sanitizedPhone,
            name: name,
            email: email,
            intent: intent,
            isPhoneVerified: isPhoneVerified,
            verifiedParam: verifiedParam
        });
        
        const message = document.createElement('div');
        message.className = 'pincode-validation-error';
        message.style.cssText = `
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
            line-height: 1.4;
        `;

        // Get list of allowed cities
        const allowedCityNames = this.getAllowedCities();
        const cityList = allowedCityNames.join(' and ');

        message.innerHTML = `
            <div style="color: #856404; background: #fff3cd; padding: 4px 8px; border-radius: 3px; display: block; ">
                Bookings open for ${cityList}. 
                <br/>
                From another city? <a href="${getBaseUrl()}/contact-us?phone=${sanitizedPhone}&name=${name}&email=${email}&intent=${intent}${verifiedParam}">Register your interest.</a>
            </div>
        `;
        this.insertMessage(message);
    },

    /**
     * Check if phone number is verified via OTP
     */
    checkPhoneVerificationStatus: function(phone) {
        if (!phone) return false;
        
        // Sanitize the input phone number for comparison
        const sanitizedPhone = UIUtils.sanitizePhoneNumber(phone);
        
        // Check if form has phone verification attribute
        // OTP verification sets data-phone-verified="1", so check for "1"
        const verificationStatus = this.form.getAttribute('data-phone-verified');
        const verifiedPhone = this.form.getAttribute('data-verified-phone');
        
        // Sanitize the verified phone for comparison too
        const sanitizedVerifiedPhone = UIUtils.sanitizePhoneNumber(verifiedPhone || '');
        
        console.log('[' + new Date().toLocaleTimeString() + '] Phone verification check:', {
            phone: phone,
            sanitizedPhone: sanitizedPhone,
            verificationStatus: verificationStatus,
            verifiedPhone: verifiedPhone,
            sanitizedVerifiedPhone: sanitizedVerifiedPhone,
            isVerified: verificationStatus === '1' && sanitizedPhone === sanitizedVerifiedPhone
        });
        
        // Phone is verified if status is '1' and the sanitized phone numbers match
        return verificationStatus === '1' && sanitizedPhone === sanitizedVerifiedPhone;
    },

    /**
     * Show general error message
     */
    showError: function (message) {
        this.clearMessages();

        const errorDiv = document.createElement('div');
        errorDiv.className = 'pincode-validation-error';
        errorDiv.style.cssText = `
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        `;

        errorDiv.innerHTML = `${message}`;

        this.insertMessage(errorDiv);

        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 8000);
    },

    /**
     * Show validation loader
     */
    showValidationLoader: function () {
        this.clearMessages();

        const loader = document.createElement('div');
        loader.className = 'pincode-validation-loader';
        loader.style.cssText = `
            background: #d1ecf1;
            color: #0c5460;
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 4px;
            border: 1px solid #bee5eb;
            font-size: 14px;
            text-align: center;
        `;

        loader.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <div style="width: 16px; height: 16px; border: 2px solid #0c5460; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                Verifying location accessibility...
            </div>
        `;

        // Add spinner animation if not already present
        if (!document.getElementById('pincode-spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'pincode-spinner-styles';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        this.insertMessage(loader);
    },

    /**
     * Hide validation loader
     */
    hideValidationLoader: function () {
        const loader = document.querySelector('.pincode-validation-loader');
        if (loader) {
            loader.remove();
        }
    },

    /**
     * Insert message under .flex container
     */
    insertMessage: function (messageElement) {
        // Find the .flex parent container that contains the pincode input
        const flexContainer = this.pincodeInput.closest('.flex');
        
        if (flexContainer) {
            // Insert after the .flex container
            flexContainer.parentNode.insertBefore(messageElement, flexContainer.nextSibling);
            console.log('Validation message inserted under .flex container');
        } else {
            console.warn('.flex parent container not found, falling back to form group');
            // Fallback to original behavior if .flex container not found
            const pincodeFormGroup = this.pincodeInput.closest('.form-group');
            if (pincodeFormGroup) {
                // Insert after the form group
                pincodeFormGroup.parentNode.insertBefore(messageElement, pincodeFormGroup.nextSibling);
            } else {
                // Final fallback: insert after pincode input
                this.pincodeInput.parentNode.insertBefore(messageElement, this.pincodeInput.nextSibling);
            }
        }
    },

    /**
     * Clear all validation messages
     */
    clearMessages: function () {
        const messages = document.querySelectorAll('.pincode-validation-success, .pincode-validation-error, .pincode-validation-loader');
        messages.forEach(msg => msg.remove());
    },

    /**
     * Enable pay button (notify BookingFormHandler to update state)
     */
    enablePayButton: function () {
        this.payButton.removeAttribute('data-pincode-restricted');

        // Trigger custom event to notify BookingFormHandler
        document.dispatchEvent(new CustomEvent('pincodeValidationChanged', {
            detail: { isValid: true }
        }));

        console.log('Pay button enabled - location allowed');
    },

    /**
     * Disable pay button (notify BookingFormHandler to update state)
     */
    disablePayButton: function () {
        this.payButton.setAttribute('data-pincode-restricted', 'true');

        // Trigger custom event to notify BookingFormHandler
        document.dispatchEvent(new CustomEvent('pincodeValidationChanged', {
            detail: { isValid: false }
        }));

        console.log('Pay button disabled - location restricted');
    },

    /**
     * Handle form submission to validate pincode restriction
     */
    handleFormSubmission: function (event) {
        // Check if pay button is restricted due to pincode
        if (this.payButton.hasAttribute('data-pincode-restricted')) {
            event.preventDefault();
            const cityList = this.getAllowedCities().join(' or ');
            this.showError(`Please enter a pincode within our delivery area (50km from ${cityList}).`);
            return false;
        }

        return true;
    },

    /**
     * Parse address components from Google Geocoding result
     */
    parseAddressComponents: function (addressComponents) {
        const components = {};

        addressComponents.forEach(comp => {
            if (comp.types.includes('administrative_area_level_1')) {
                components.state = comp.long_name;
            }
            if (comp.types.includes('administrative_area_level_2') && !components.city) {
                components.city = comp.long_name;
            }
            if (comp.types.includes('locality') && !components.city) {
                components.city = comp.long_name;
            }
            if (comp.types.includes('sublocality_level_1') && !components.city) {
                components.city = comp.long_name;
            }
        });

        return {
            city: components.city || 'Unknown City',
            state: components.state || 'Unknown State'
        };
    },

    /**
     * Cache management functions
     */
    getCacheKey: function (coordinates) {
        return `distance_${coordinates.replace(/[,\s]/g, '_')}`;
    },

    getCachedDistance: function (cacheKey) {
        const cached = this.distanceCache.get(cacheKey);
        if (!cached) return null;

        // Check if cache is expired
        const ageHours = (Date.now() - cached.timestamp) / (1000 * 60 * 60);
        if (ageHours > this.config.CACHE_EXPIRY_HOURS) {
            this.distanceCache.delete(cacheKey);
            return null;
        }

        return cached;
    },

    cacheDistance: function (cacheKey, result) {
        this.distanceCache.set(cacheKey, result);
        this.saveCacheToStorage();
    },

    saveCacheToStorage: function () {
        try {
            const cacheData = Array.from(this.distanceCache.entries());
            localStorage.setItem('pincodeDistanceCache', JSON.stringify(cacheData));
        } catch (error) {
            console.warn('Failed to save distance cache to localStorage:', error);
        }
    },

    loadCacheFromStorage: function () {
        try {
            const cacheData = localStorage.getItem('pincodeDistanceCache');
            if (cacheData) {
                const entries = JSON.parse(cacheData);
                this.distanceCache = new Map(entries);

                // Clean expired entries
                const now = Date.now();
                for (const [key, value] of this.distanceCache.entries()) {
                    const ageHours = (now - value.timestamp) / (1000 * 60 * 60);
                    if (ageHours > this.config.CACHE_EXPIRY_HOURS) {
                        this.distanceCache.delete(key);
                    }
                }

                console.log(`Loaded ${this.distanceCache.size} cached distance results`);
            }
        } catch (error) {
            console.warn('Failed to load distance cache from localStorage:', error);
            this.distanceCache = new Map();
        }
    },

    /**
     * Debounce function to limit API calls
     */
    debounce: function (func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Get allowed cities for reference
     */
    getAllowedCities: function () {
        return this.config.ALLOWED_CITIES.map(city => city.name);
    },

    /**
     * Clear all cache (for debugging/testing)
     */
    clearCache: function () {
        this.distanceCache.clear();
        localStorage.removeItem('pincodeDistanceCache');
        console.log('Distance cache cleared');
    }
};

/**
 * =======================
 * Pincode Address Autocomplete Module
 * =======================
 * Handles pincode-based address autosuggestion and auto-fills city/state fields
 */
const PincodeAddressAutocomplete = {
    /**
     * Initialize pincode autocomplete functionality
     */
    init: function () {
        // Only run on book-now page
        this.pincodeInput = document.querySelector('input[name="pincode"]');
        this.cityInput = document.querySelector('input[name="city"]');
        this.stateInput = document.querySelector('input[name="state"]');

        if (!this.pincodeInput || !this.cityInput || !this.stateInput) {
            console.log('Pincode autocomplete not applicable for this page');
            return;
        }

        this.setupPincodeAutocomplete();
        console.log('Pincode Address Autocomplete initialized');
    },

    /**
     * Setup pincode autocomplete with debounced search
     */
    setupPincodeAutocomplete: function () {
        // Create autocomplete container
        this.createAutocompleteContainer();

        // Add event listeners
        this.pincodeInput.addEventListener('input', this.debounce(this.handlePincodeInput.bind(this), 300));
        this.pincodeInput.addEventListener('keydown', this.handleKeydown.bind(this));
        this.pincodeInput.addEventListener('blur', this.handleBlur.bind(this));
        this.pincodeInput.addEventListener('focus', this.handleFocus.bind(this));

        // Close suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.autocompleteContainer.contains(e.target) && e.target !== this.pincodeInput) {
                this.hideSuggestions();
            }
        });
    },

    /**
     * Create autocomplete container for suggestions
     */
    createAutocompleteContainer: function () {
        // Find the .flex parent container that contains the pincode input
        const flexContainer = this.pincodeInput.closest('.flex');
        
        if (!flexContainer) {
            console.warn('.flex parent container not found, falling back to pincode parent');
            // Fallback to original behavior if .flex container not found
            const pincodeParent = this.pincodeInput.parentNode;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'pincode-autocomplete-wrapper';
            wrapper.style.cssText = 'position: relative; width: 100%; flex: none;';
            pincodeParent.insertBefore(wrapper, this.pincodeInput.nextSibling);
            
            this.autocompleteContainer = document.createElement('div');
            this.autocompleteContainer.className = 'pincode-suggestions';
            this.autocompleteContainer.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                max-height: 200px;
                overflow-y: auto;
                z-index: 1000;
                display: none;
            `;
            
            wrapper.appendChild(this.autocompleteContainer);
            return;
        }

        // Create wrapper for autocomplete positioning under .flex container
        const wrapper = document.createElement('div');
        wrapper.className = 'pincode-autocomplete-wrapper';
        wrapper.style.cssText = 'position: relative; width: 100%;';

        // Insert wrapper inside the .flex container as the last item
        flexContainer.appendChild(wrapper);

        // Create suggestions container
        this.autocompleteContainer = document.createElement('div');
        this.autocompleteContainer.className = 'pincode-suggestions';
        this.autocompleteContainer.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;

        wrapper.appendChild(this.autocompleteContainer);

        console.log('Pincode autocomplete container created under .flex parent');
    },

    /**
     * Handle pincode input with API call
     */
    handlePincodeInput: function (e) {
        const value = e.target.value.trim();

        // Clear city and state when pincode changes
        this.clearCityState();

        // Hide suggestions if input is too short
        if (value.length < 3) {
            this.hideSuggestions();
            return;
        }

        // Only allow numeric input
        if (!/^\d+$/.test(value)) {
            this.hideSuggestions();
            return;
        }

        console.log('Searching for pincode:', value);

        // For partial pincodes (less than 6 digits), show pattern-based suggestions
        if (value.length < 6) {
            // this.showPartialPincodeSuggestions(value);
        } else {
            // For complete pincodes, use Google Geocoding API
            this.fetchPincodeSuggestions(value);
        }
    },

    /**
     * Fetch pincode suggestions using Google Geocoding API
     */
    fetchPincodeSuggestions: async function (pincode) {
        try {
            this.showLoading();

            const GOOGLE_API_KEY = 'AIzaSyA-2N9fbAPu2cWVLNGYu0qWL8Gs1Xu3QTw';
            const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(pincode)},India&key=${GOOGLE_API_KEY}`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.status === 'OK' && data.results && data.results.length > 0) {
                const suggestions = this.parseGoogleGeocodingResults(data.results, pincode);
                this.showSuggestions(suggestions);
                console.log(`Found ${suggestions.length} pincode suggestions from Google Geocoding`);
            } else {
                this.showNoResults();
                console.log('No pincode suggestions found from Google Geocoding');
            }
        } catch (error) {
            console.error('Error fetching pincode suggestions:', error);
            this.showError();
        }
    },

    /**
     * Parse Google Geocoding API results into our format
     */
    parseGoogleGeocodingResults: function (results, searchPincode) {
        const suggestions = [];

        results.forEach(result => {
            const components = {};

            // Extract address components with specific priority for administrative levels
            result.address_components.forEach(comp => {
                if (comp.types.includes('postal_code')) {
                    components.postcode = comp.long_name;
                }

                // Priority for state: administrative_area_level_1 (state/province level)
                if (comp.types.includes('administrative_area_level_1')) {
                    components.state = comp.long_name;
                }

                // Priority for city: political, sublocality, sublocality_level_1
                if (comp.types.includes('political') && comp.types.includes('sublocality')) {
                    components.city = comp.long_name;
                }
                // Fallback for city: sublocality_level_1
                else if (comp.types.includes('sublocality_level_1') && !components.city) {
                    components.city = comp.long_name;
                }
                // Fallback for city: administrative_area_level_3 if sublocality not available
                else if (comp.types.includes('administrative_area_level_3') && !components.city) {
                    components.city = comp.long_name;
                }
                // Fallback for city: administrative_area_level_2 if level_3 not available
                else if (comp.types.includes('administrative_area_level_2') && !components.city) {
                    components.city = comp.long_name;
                }
                // Final fallback for city: locality
                else if (comp.types.includes('locality') && !components.city) {
                    components.city = comp.long_name;
                }

                // Area/locality details
                if (comp.types.includes('sublocality_level_1') || comp.types.includes('sublocality')) {
                    components.area = comp.long_name;
                }
                if (comp.types.includes('neighborhood') && !components.area) {
                    components.area = comp.long_name;
                }
            });

            // Create suggestion object with formatted address
            const suggestion = {
                pincode: components.postcode || searchPincode,
                city: components.city || 'Unknown City',
                state: components.state || 'Unknown State',
                area: components.area || 'Main Area',
                formatted_address: result.formatted_address || 'Address not available'
            };

            console.log('Parsed suggestion:', suggestion);

            // Only add if we have meaningful data
            if (suggestion.city !== 'Unknown City' || suggestion.state !== 'Unknown State') {
                suggestions.push(suggestion);
            }
        });

        // Remove duplicates based on formatted address
        const uniqueSuggestions = suggestions.filter((suggestion, index, self) =>
            index === self.findIndex(s =>
                s.formatted_address === suggestion.formatted_address
            )
        );

        return uniqueSuggestions;
    },

    /**
     * Show partial pincode suggestions based on common patterns
     */
    showPartialPincodeSuggestions: function (partialPincode) {
        const suggestions = this.getPartialPincodeSuggestions(partialPincode);

        if (suggestions.length > 0) {
            this.showSuggestions(suggestions);
            console.log(`Showing ${suggestions.length} partial pincode suggestions`);
        } else {
            this.showNoResults();
        }
    },

    /**
     * Get partial pincode suggestions based on common Indian pincode patterns
     */
    getPartialPincodeSuggestions: function (partialPincode) {
        const suggestions = [];

        // Get all allowed cities first
        const allowedCities = this.config.ALLOWED_CITIES || [];
        
        // Common Indian pincode patterns - filtered to only include our serviceable cities
        const pincodePatterns = {
            '4': [ // Maharashtra pincodes
                { range: '400001-400714', city: 'Mumbai', state: 'Maharashtra', area: 'Mumbai' },
                { range: '411001-411062', city: 'Pune', state: 'Maharashtra', area: 'Pune' }
            ]
        };

        // Create a dynamic pattern based on allowed cities
        allowedCities.forEach(city => {
            const cityName = city.name;
            // Add pattern based on city - this is a simplification, 
            // in a real implementation you might want to fetch actual pincode ranges for these cities
            switch(cityName.toLowerCase()) {
                case 'mumbai':
                    if (!pincodePatterns['4']) pincodePatterns['4'] = [];
                    pincodePatterns['4'].push({ 
                        range: '400001-400714', 
                        city: cityName, 
                        state: 'Maharashtra', 
                        area: cityName 
                    });
                    break;
                case 'pune':
                    if (!pincodePatterns['4']) pincodePatterns['4'] = [];
                    pincodePatterns['4'].push({ 
                        range: '411001-411062', 
                        city: cityName, 
                        state: 'Maharashtra', 
                        area: cityName 
                    });
                    break;
                // Add more cities as needed
            }
        });

        const firstDigit = partialPincode.charAt(0);
        const patterns = pincodePatterns[firstDigit] || [];

        patterns.forEach(pattern => {
            // Check if partial pincode could match this pattern
            const [start] = pattern.range.split('-');
            if (start.startsWith(partialPincode)) {
                suggestions.push({
                    pincode: partialPincode + '0'.repeat(6 - partialPincode.length), // Pad with zeros
                    city: pattern.city,
                    state: pattern.state,
                    area: pattern.area
                });
            }
        });

        // Limit to 5 suggestions
        return suggestions.slice(0, 5);
    },

    /**
     * Show loading state
     */
    showLoading: function () {
        this.autocompleteContainer.innerHTML = `
            <div class="suggestion-item loading" style="padding: 12px; text-align: center; color: #666;">
                Searching...
            </div>
        `;
        this.autocompleteContainer.style.display = 'block';
    },

    /**
     * Show suggestions list
     */
    showSuggestions: function (suggestions) {
        const html = suggestions.map((item, index) => `
            <div class="suggestion-item" 
                 data-pincode="${item.pincode}" 
                 data-city="${item.city}" 
                 data-state="${item.state}"
                 data-area="${item.area}"
                 data-formatted-address="${item.formatted_address || ''}"
                 data-index="${index}"
                 style="padding: 12px; cursor: pointer; border-bottom: 1px solid #eee; transition: background-color 0.2s;">
                <div style="font-weight: 600; color: #192A5A;">${item.pincode}</div>
                <div style="font-size: 13px; color: #666; line-height: 1.4;">${item.formatted_address || `${item.area}, ${item.city}`}</div>
                <div style="font-size: 12px; color: #999;">${item.state}</div>
            </div>
        `).join('');

        this.autocompleteContainer.innerHTML = html;
        this.autocompleteContainer.style.display = 'block';

        // Add click listeners to suggestions
        this.autocompleteContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', this.selectSuggestion.bind(this));

            // Prevent blur when clicking on suggestions
            item.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Prevent input from losing focus
            });

            // Add hover effects
            item.addEventListener('mouseenter', (e) => {
                e.target.style.backgroundColor = '#f5f5f5';
            });
            item.addEventListener('mouseleave', (e) => {
                e.target.style.backgroundColor = '';
            });
        });

        this.selectedIndex = -1; // Reset selection
    },

    /**
     * Show no results message
     */
    showNoResults: function () {
        this.autocompleteContainer.innerHTML = `
            <div class="suggestion-item no-results" style="padding: 12px; text-align: center; color: #999;">
                📭 No locations found for this pincode
            </div>
        `;
        this.autocompleteContainer.style.display = 'block';
    },

    /**
     * Show error message
     */
    showError: function () {
        this.autocompleteContainer.innerHTML = `
            <div class="suggestion-item error" style="padding: 12px; text-align: center; color: #d32f2f;">
                Error loading suggestions. Please try again.
            </div>
        `;
        this.autocompleteContainer.style.display = 'block';
    },

    /**
     * Hide suggestions
     */
    hideSuggestions: function () {
        this.autocompleteContainer.style.display = 'none';
        this.selectedIndex = -1;
    },

    /**
     * Handle keyboard navigation
     */
    handleKeydown: function (e) {
        const suggestions = this.autocompleteContainer.querySelectorAll('.suggestion-item:not(.loading):not(.no-results):not(.error)');

        if (suggestions.length === 0) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, suggestions.length - 1);
                this.highlightSuggestion(suggestions);
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.highlightSuggestion(suggestions);
                break;

            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && suggestions[this.selectedIndex]) {
                    this.selectSuggestionByElement(suggestions[this.selectedIndex]);
                }
                break;

            case 'Escape':
                this.hideSuggestions();
                this.pincodeInput.blur();
                break;
        }
    },

    /**
     * Highlight selected suggestion
     */
    highlightSuggestion: function (suggestions) {
        // Remove previous highlight
        suggestions.forEach(item => {
            item.style.backgroundColor = '';
        });

        // Highlight current selection
        if (this.selectedIndex >= 0 && suggestions[this.selectedIndex]) {
            suggestions[this.selectedIndex].style.backgroundColor = '#e3f2fd';
        }
    },

    /**
     * Select suggestion by clicking
     */
    selectSuggestion: function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.selectSuggestionByElement(e.currentTarget);
    },

    /**
     * Select suggestion by element
     */
    selectSuggestionByElement: function (element) {
        const pincode = element.dataset.pincode;
        const city = element.dataset.city;
        const state = element.dataset.state;
        const area = element.dataset.area;
        const formattedAddress = element.dataset.formattedAddress;

        // Dispatch custom event with all data including formatted address
        this.pincodeInput.dispatchEvent(new CustomEvent('pincodeSelected', {
            detail: { pincode, city, state, area, formattedAddress },
            bubbles: true
        }));

        // Fill form fields
        this.pincodeInput.value = pincode;
        if (this.cityInput && city) {
            this.cityInput.value = city;
        }
        if (this.stateInput && state) {
            this.stateInput.value = state;
        }

        // Trigger change events for validation
        this.pincodeInput.dispatchEvent(new Event('change', { bubbles: true }));
        if (this.cityInput) {
            this.cityInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (this.stateInput) {
            this.stateInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Hide suggestions
        this.hideSuggestions();

        // Show success feedback with formatted address
        this.showSuccessFeedback(city, state, formattedAddress);
    },

    /**
     * Show success feedback
     */
    showSuccessFeedback: function (city, state, formattedAddress) {
        // Create temporary success message
        const feedback = document.createElement('div');
        feedback.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #d4edda;
            color: #155724;
            padding: 8px 12px;
            border-radius: 0 0 4px 4px;
            font-size: 13px;
            z-index: 1001;
            border: 1px solid #c3e6cb;
            line-height: 1.4;
        `;

        // Use formatted address if available, otherwise fallback to city, state
        const displayText = formattedAddress && formattedAddress !== 'Address not available'
            ? formattedAddress
            : `${city}, ${state}`;

        feedback.innerHTML = `<strong>Address auto-filled:</strong><br>${displayText}`;

        // Add to wrapper - use the autocomplete container since wrapper is now a sibling
        const wrapper = this.autocompleteContainer;
        if (wrapper) {
            wrapper.appendChild(feedback);
        }

        // Remove after 4 seconds (increased for longer formatted address)
        setTimeout(() => {
            if (feedback.parentNode) {
                feedback.remove();
            }
        }, 4000);
    },

    /**
     * Clear city and state fields
     */
    clearCityState: function () {
        this.cityInput.value = '';
        this.stateInput.value = '';
    },

    /**
     * Handle focus event
     */
    handleFocus: function (e) {
        const value = e.target.value.trim();
        if (value.length >= 3 && /^\d+$/.test(value)) {
            // Show suggestions if we have valid input
            this.fetchPincodeSuggestions(value);
        }
    },

    /**
     * Handle blur event with delay to allow clicks
     */
    handleBlur: function (e) {
        // Delay hiding to allow clicking on suggestions
        setTimeout(() => {
            this.hideSuggestions();
        }, 150);
    },

    /**
     * Debounce function to limit API calls
     */
    debounce: function (func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

/**
 * =======================
 * URL Hash Scroll Handler Module
 * =======================
 * Handles smooth scrolling to elements based on URL hash values
 */
const URLHashScrollHandler = {
    /**
     * Initialize URL hash scrolling functionality
     */
    init: function () {
        // Handle hash scrolling on page load
        this.handleInitialHash();

        // Handle hash changes during navigation
        this.setupHashChangeListener();

        // Handle anchor link clicks
        this.setupAnchorLinkListener();

        console.log('URL Hash Scroll Handler initialized');
    },

    /**
     * Handle hash scrolling when page initially loads
     */
    handleInitialHash: function () {
        // Check if there's a hash in the URL on page load
        const hash = window.location.hash;
        if (hash && hash.length > 1) {
            // Small delay to ensure page is fully loaded and DOM is ready
            setTimeout(() => {
                this.scrollToElement(hash);
            }, 300); // Increased delay for better reliability
        }
    },

    /**
     * Setup listener for hash changes (back/forward navigation, anchor clicks)
     */
    setupHashChangeListener: function () {
        window.addEventListener('hashchange', (e) => {
            const hash = window.location.hash;
            console.log('Hash changed to:', hash);
            if (hash && hash.length > 1) {
                this.scrollToElement(hash);
            }
        });
    },

    /**
     * Setup listener for anchor link clicks (for immediate response)
     */
    setupAnchorLinkListener: function () {
        // Listen for clicks on anchor links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                const href = link.getAttribute('href');
                if (href && href.length > 1) {
                    console.log('🔗 Anchor link clicked:', href);

                    // Small delay to allow URL to update
                    setTimeout(() => {
                        this.scrollToElement(href);
                    }, 50);
                }
            }
        });
    },

    /**
     * Scroll to element with enhanced smooth animation
     * @param {string} hash - The hash value (including #)
     */
    scrollToElement: function (hash) {
        try {
            // Remove the # to get the element ID
            const elementId = hash.substring(1);
            const targetElement = document.getElementById(elementId);

            if (targetElement) {
                // Calculate offset to account for fixed headers or spacing
                const offset = this.calculateScrollOffset();

                // Get element position
                const elementPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                const targetPosition = elementPosition - offset;

                // Enhanced smooth scroll with fallback
                this.performSmoothScroll(targetPosition, targetElement);

                // Optional: Add a subtle highlight effect
                this.highlightElement(targetElement);

                // Update URL hash if it's different (for programmatic calls)
                if (window.location.hash !== hash) {
                    history.replaceState(null, null, hash);
                }
            } else {
                console.warn(`Element with ID "${elementId}" not found`);
            }
        } catch (error) {
            console.error('Error scrolling to hash element:', error);
        }
    },

    /**
     * Perform enhanced smooth scroll with custom easing
     * @param {number} targetPosition - The target scroll position
     * @param {HTMLElement} targetElement - The target element for focus
     */
    performSmoothScroll: function (targetPosition, targetElement) {
        const finalPosition = Math.max(0, targetPosition);

        // Check if browser supports smooth scrolling behavior
        if ('scrollBehavior' in document.documentElement.style) {
            // Use native smooth scroll with enhanced options
            window.scrollTo({
                top: finalPosition,
                left: 0,
                behavior: 'smooth'
            });

            // Add custom CSS for smoother scrolling if not already present
            this.addSmoothScrollCSS();
        } else {
            // Fallback for older browsers with custom smooth scroll
            this.customSmoothScroll(finalPosition);
        }

        // Ensure accessibility - set focus after scroll completes
        setTimeout(() => {
            if (targetElement) {
                // Make element focusable if it isn't already
                if (!targetElement.hasAttribute('tabindex')) {
                    targetElement.setAttribute('tabindex', '-1');
                }
                targetElement.focus();
            }
        }, 800); // Wait for scroll animation to complete
    },

    /**
     * Add CSS for enhanced smooth scrolling
     */
    addSmoothScrollCSS: function () {
        if (!document.getElementById('smooth-scroll-styles')) {
            const style = document.createElement('style');
            style.id = 'smooth-scroll-styles';
            style.textContent = `
                html {
                    scroll-behavior: smooth;
                    scroll-padding-top: 80px; /* Account for fixed headers */
                }
                
                /* Enhanced smooth scrolling for better performance */
                @media (prefers-reduced-motion: no-preference) {
                    html {
                        scroll-behavior: smooth;
                    }
                }
                
                /* Respect user's motion preferences */
                @media (prefers-reduced-motion: reduce) {
                    html {
                        scroll-behavior: auto;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    },

    /**
     * Custom smooth scroll implementation for older browsers
     * @param {number} targetPosition - The target scroll position
     */
    customSmoothScroll: function (targetPosition) {
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        const duration = Math.min(Math.abs(distance) / 2, 1000); // Max 1 second
        let startTime = null;

        // Easing function for smooth animation
        const easeInOutCubic = (t) => {
            return t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
        };

        const animateScroll = (currentTime) => {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const progress = Math.min(timeElapsed / duration, 1);

            const easedProgress = easeInOutCubic(progress);
            const currentPosition = startPosition + (distance * easedProgress);

            window.scrollTo(0, currentPosition);

            if (progress < 1) {
                requestAnimationFrame(animateScroll);
            }
        };

        requestAnimationFrame(animateScroll);
    },

    /**
     * Calculate scroll offset to account for fixed headers
     * @returns {number} Offset in pixels
     */
    calculateScrollOffset: function () {
        // Check for common fixed header patterns
        const fixedHeader = document.querySelector('header.fixed, .header.fixed, .navbar-fixed-top, .fixed-header');

        if (fixedHeader) {
            const headerHeight = fixedHeader.offsetHeight;
            return headerHeight + 20; // Add 20px extra spacing
        }

        // Default offset for general spacing
        return 80;
    },

    /**
     * Add enhanced highlight effect to target element
     * @param {HTMLElement} element - The target element
     */
    highlightElement: function (element) {
        // Only add highlight if element doesn't already have focus-related styling
        if (!element.classList.contains('highlighted')) {
            // Add temporary highlight class
            element.classList.add('url-hash-target');

            // Create enhanced highlight styles if they don't exist
            if (!document.getElementById('url-hash-styles')) {
                const style = document.createElement('style');
                style.id = 'url-hash-styles';
                style.textContent = `
                    .url-hash-target {
                        animation: urlHashHighlight 2.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                        position: relative;
                        z-index: 1;
                    }
                    
                    @keyframes urlHashHighlight {
                        0% {
                            background-color: rgba(25, 42, 90, 0.12);
                            box-shadow: 0 0 0 3px rgba(25, 42, 90, 0.15);
                            transform: scale(1.005);
                        }
                        25% {
                            background-color: rgba(25, 42, 90, 0.08);
                            box-shadow: 0 0 0 2px rgba(25, 42, 90, 0.1);
                        }
                        75% {
                            background-color: rgba(25, 42, 90, 0.03);
                            box-shadow: 0 0 0 1px rgba(25, 42, 90, 0.05);
                        }
                        100% {
                            background-color: transparent;
                            box-shadow: none;
                            transform: scale(1);
                        }
                    }
                    
                    /* Respect user's motion preferences */
                    @media (prefers-reduced-motion: reduce) {
                        .url-hash-target {
                            animation: urlHashHighlightReduced 1s ease;
                        }
                        
                        @keyframes urlHashHighlightReduced {
                            0% { background-color: rgba(25, 42, 90, 0.1); }
                            100% { background-color: transparent; }
                        }
                    }
                `;
                document.head.appendChild(style);
            }

            // Remove highlight class after animation
            setTimeout(() => {
                element.classList.remove('url-hash-target');
            }, 2500);
        }
    },

    /**
     * Manually trigger scroll to a specific element ID
     * @param {string} elementId - The ID of the element (without #)
     */
    scrollToId: function (elementId) {
        this.scrollToElement(`#${elementId}`);
    },

    /**
     * Check and handle current hash (can be called manually)
     * Useful for calling after dynamic content loads
     */
    handleCurrentHash: function () {
        const hash = window.location.hash;
        if (hash && hash.length > 1) {
            this.scrollToElement(hash);
        }
    },

    /**
     * Force re-check of URL hash (useful after AJAX content loads)
     */
    recheckHash: function () {
        setTimeout(() => {
            this.handleCurrentHash();
        }, 100);
    }
};

/**
 * Initialize the Dealership Finder module
 * The module is imported in main.js and available globally
 */

// Initialize all modules when the DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Initialize dealership finder using the global variable
    if (typeof DealershipFinder !== 'undefined' && typeof DealershipFinder.init === 'function') {
        DealershipFinder.init();
    } else {
        console.warn('DealershipFinder global not defined or missing init method');
    }

    // Check if DealershipMap exists (imported in main.js) and initialize it
    if (typeof DealershipMap !== 'undefined') {
        try {
            // Create an instance of the DealershipMap class
            const dealershipMap = new DealershipMap();

            // Store the instance globally to allow for interaction from other scripts
            window.dealershipMapInstance = dealershipMap;
        } catch (error) {
            console.error('Error creating DealershipMap instance:', error);
        }
    } else {
        console.warn('DealershipMap not defined. Check import in main.js');
    }

    // Check if ContactMap exists (imported in main.js) and initialize it
    if (typeof ContactMap !== 'undefined') {
        try {
            // Create an instance of the ContactMap class
            const contactMap = new ContactMap();

            // Store the instance globally to allow for interaction from other scripts
            window.contactMapInstance = contactMap;
        } catch (error) {
            console.error('Error creating ContactMap instance:', error);
        }
    } else {
        console.warn('ContactMap not defined. Check import in main.js');
    }

    // Initialize Meta Pixel Tracking
    if (typeof MetaPixelTracking !== 'undefined') {
        MetaPixelTracking.init();
    }

    // Initialize EMI Calculator (homepage section)
    EmiCalculator.init();

    // Initialize EMI Calculator Page (full calculator page)
    EmiCalculatorPage.init();
});
