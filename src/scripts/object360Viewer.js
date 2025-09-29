class Object360Viewer {
    constructor(options = {}) {
        console.log('Object360Viewer constructor called with options:', options);
        this.options = options;
        this.totalFrames = options.totalFrames || 180;
        // Support initialAngle (in degrees) or initialFrame. Default to 60deg if not provided.
        let angle = 60;
        if (typeof options.initialAngle === 'number') {
            angle = options.initialAngle;
        }
        // Clamp angle between 0 and 360, then map to frame (1-based)
        angle = ((angle % 360) + 360) % 360;
        this.currentFrame = Math.round((angle / 360) * this.totalFrames) || 1;
        if (typeof options.initialFrame === 'number') {
            this.currentFrame = Math.min(Math.max(1, options.initialFrame), this.totalFrames);
        }
        this.images = new Array(this.totalFrames); // Initialize as empty array with length = totalFrames
        this.imagesLoaded = 0;
        this.isDragging = false;
        this.lastMouseX = 0;
        this.sensitivity = options.sensitivity || 3; // Reduced sensitivity for smoother movement
        this.isAnimating = false;
        this.animationId = null;

        // Memory management
        this.memoryManagement = options.memoryManagement || 'moderate'; // 'aggressive', 'moderate', 'none'
        this.cacheSize = options.cacheSize || 40; // Number of frames to keep in memory for 'moderate' mode

        // Preload configuration
        this.preloadStrategy = options.preloadStrategy || 'progressive'; // 'eager' | 'lazy' | 'progressive' | 'adaptive'
        this.preloadConcurrency = typeof options.preloadConcurrency === 'number' ?
            Math.max(1, options.preloadConcurrency) :
            this._getOptimalConcurrency();

        // Progressive loading configuration
        this.progressiveConfig = {
            immediateRadius: options.immediateRadius || 5, // Load frames within this distance immediately
            priorityRadius: options.priorityRadius || 15,  // Load frames within this distance with high priority
            idleLoadDelay: options.idleLoadDelay || 100,   // Delay before starting idle loading (ms)
            maxIdleConcurrency: options.maxIdleConcurrency || 3 // Max concurrent loads during idle time
        };

        this._requestedFrames = new Set();
        this._inFlight = 0;
        this._queue = [];
        this._priorityQueue = [];
        this._idleQueue = [];
        this._initialReadyEmitted = false;
        this._isDestroyed = false;
        this._idleLoadingActive = false;
        this._lastInteractionTime = Date.now();

        // For request cancellation and retry tracking
        this._abortControllers = {};
        this._loadRetries = {};

        // Accept DOM elements or selectors for viewerElement, imageElement, frameCounter
        this.viewerElement = this._resolveElement(options.viewerElement || '#viewer');
        this.imageElement = this._resolveElement(options.imageElement || '#viewerImage');
        this.frameCounter = this._resolveElement(options.frameCounter || null);

        console.log('Elements resolved:', {
            viewerElement: this.viewerElement, 
            imageElement: this.imageElement,
            frameCounter: this.frameCounter
        });

        // Only initialize if required elements exist
        if (!this.viewerElement || !this.imageElement) {
            console.warn('Object360Viewer: Required elements not found');
            return;
        }

        console.log('Starting initialization - preloading images and setting up event listeners');
        this._setupIntersectionObserver();
        this.preloadImages();
        this.setupEventListeners();
    }

    /**
     * Determine optimal concurrency based on network and device capabilities
     */
    _getOptimalConcurrency() {
        // Start with a reasonable default
        let concurrency = 6;

        // Check for available logical processors (rough estimate of device capability)
        if (navigator.hardwareConcurrency) {
            concurrency = Math.min(Math.max(2, navigator.hardwareConcurrency / 2), 8);
        }

        // Check connection type if available
        if (navigator.connection) {
            const connection = navigator.connection;

            if (connection.effectiveType === '4g') {
                concurrency = Math.min(concurrency + 2, 10);
            } else if (connection.effectiveType === '3g') {
                concurrency = Math.min(concurrency, 4);
            } else if (connection.effectiveType === '2g' || connection.saveData) {
                concurrency = 2;
            }

            // If it's a metered connection, be more conservative
            if (connection.saveData) {
                concurrency = Math.min(concurrency, 2);
            }
        }

        return Math.floor(concurrency);
    }

    // Helper to resolve DOM element from selector or element
    _resolveElement(ref) {
        console.log('Resolving element:', ref);
        
        if (!ref) {
            console.log('Reference is null or undefined, returning null');
            return null;
        }
        
        if (typeof ref === 'string') {
            const element = document.querySelector(ref);
            console.log(`Queried selector "${ref}", result:`, element);
            return element;
        }
        
        if (ref instanceof HTMLElement) {
            console.log('Reference is already an HTMLElement');
            return ref;
        }
        
        console.log('Reference is not a string or HTMLElement:', typeof ref);
        return null;
        return null;
    }

    preloadImages() {
        // Load the initial frame first (priority)
        this._loadFrame(this.currentFrame, true);
        // Set src immediately for faster first paint
        this.imageElement.src = this._frameToUrl(this.currentFrame);
        this.imageElement.style.display = 'block';

        if (this.preloadStrategy === 'eager') {
            // Eagerly queue all remaining frames
            for (let i = 1; i <= this.totalFrames; i++) {
                if (i === this.currentFrame) continue;
                this._queue.push(i);
            }
        } else if (this.preloadStrategy === 'progressive') {
            this._setupProgressiveLoading();
        } else {
            // Lazy: ring-order around the current frame: +1, -1, +2, -2, ...
            for (let offset = 1; offset < this.totalFrames; offset++) {
                const forward = this._wrapFrame(this.currentFrame + offset);
                const backward = this._wrapFrame(this.currentFrame - offset);
                if (forward !== this.currentFrame) this._queue.push(forward);
                if (backward !== this.currentFrame) this._queue.push(backward);
                if (this._queue.length >= this.totalFrames - 1) break;
            }
            // De-duplicate while preserving order
            const seen = new Set();
            this._queue = this._queue.filter(f => (seen.has(f) ? false : (seen.add(f), true)));
        }

        // Start pumping the queue with concurrency control
        this._pumpQueue();
    }

    /**
     * Setup Intersection Observer to optimize loading based on visibility
     */
    _setupIntersectionObserver() {
        if (!window.IntersectionObserver || !this.viewerElement) return;

        this._intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Viewer is visible, increase loading priority
                    this._isViewerVisible = true;
                    if (this.preloadStrategy === 'progressive') {
                        this.preloadConcurrency = Math.min(this.preloadConcurrency + 2, 10);
                        this._pumpQueue();
                    }
                } else {
                    // Viewer is not visible, reduce loading priority
                    this._isViewerVisible = false;
                    if (this.preloadStrategy === 'progressive') {
                        this.preloadConcurrency = Math.max(this.preloadConcurrency - 2, 2);
                    }
                }
            });
        }, {
            threshold: 0.1, // Trigger when 10% of the viewer is visible
            rootMargin: '50px' // Start loading 50px before coming into view
        });

        this._intersectionObserver.observe(this.viewerElement);
    }

    /**
     * Setup progressive loading strategy for optimal user experience
     */
    _setupProgressiveLoading() {
        const currentFrame = this.currentFrame;
        const immediateFrames = [];
        const priorityFrames = [];
        const idleFrames = [];

        // Categorize frames by distance from current frame
        for (let i = 1; i <= this.totalFrames; i++) {
            if (i === currentFrame) continue;

            const distance = this._getFrameDistance(currentFrame, i);
            
            if (distance <= this.progressiveConfig.immediateRadius) {
                immediateFrames.push(i);
            } else if (distance <= this.progressiveConfig.priorityRadius) {
                priorityFrames.push(i);
            } else {
                idleFrames.push(i);
            }
        }

        // Sort by distance (closest first)
        immediateFrames.sort((a, b) => 
            this._getFrameDistance(currentFrame, a) - this._getFrameDistance(currentFrame, b)
        );
        priorityFrames.sort((a, b) => 
            this._getFrameDistance(currentFrame, a) - this._getFrameDistance(currentFrame, b)
        );

        // Setup queues
        this._queue = immediateFrames; // Load immediately
        this._priorityQueue = priorityFrames; // Load after immediate frames
        this._idleQueue = idleFrames; // Load during idle time

        // Start idle loading after a delay
        setTimeout(() => {
            this._startIdleLoading();
        }, this.progressiveConfig.idleLoadDelay);
    }

    /**
     * Calculate the shortest distance between two frames (considering wraparound)
     */
    _getFrameDistance(frame1, frame2) {
        const direct = Math.abs(frame1 - frame2);
        const wraparound = this.totalFrames - direct;
        return Math.min(direct, wraparound);
    }

    /**
     * Start loading frames during idle time
     */
    _startIdleLoading() {
        if (this._idleLoadingActive || this._isDestroyed) return;
        
        this._idleLoadingActive = true;
        this._processIdleQueue();
    }

    /**
     * Process idle queue when browser is idle
     */
    _processIdleQueue() {
        if (!this._idleLoadingActive || this._isDestroyed || this._idleQueue.length === 0) return;

        // Check if user has been inactive for a reasonable time
        const timeSinceLastInteraction = Date.now() - this._lastInteractionTime;
        if (timeSinceLastInteraction < 500) {
            // User is actively interacting, wait a bit
            setTimeout(() => this._processIdleQueue(), 200);
            return;
        }

        // Use requestIdleCallback if available, otherwise use setTimeout
        if (window.requestIdleCallback) {
            window.requestIdleCallback((deadline) => {
                this._loadIdleFrames(deadline);
            });
        } else {
            setTimeout(() => {
                this._loadIdleFrames({ timeRemaining: () => 16 }); // Simulate 16ms budget
            }, 0);
        }
    }

    /**
     * Load frames during idle time with time budget management
     */
    _loadIdleFrames(deadline) {
        if (!this._idleLoadingActive || this._isDestroyed) return;

        let idleLoadsStarted = 0;
        const maxIdleConcurrency = this.progressiveConfig.maxIdleConcurrency;

        while (
            deadline.timeRemaining() > 5 && // Keep 5ms buffer
            idleLoadsStarted < maxIdleConcurrency &&
            this._idleQueue.length > 0 &&
            this._inFlight < this.preloadConcurrency
        ) {
            const nextFrame = this._idleQueue.shift();
            if (!this._requestedFrames.has(nextFrame)) {
                this._loadFrame(nextFrame, false);
                idleLoadsStarted++;
            }
        }

        // Continue processing if there are more frames to load
        if (this._idleQueue.length > 0) {
            setTimeout(() => this._processIdleQueue(), 100);
        }
    }

    _frameToUrl(i) {
        const imagePathTemplate = (typeof this.options.imagePathTemplate === 'string') ? this.options.imagePathTemplate : '/-/images/all-bikes/kinetic-dx-red/new/{frame}.png';
        const frameNumber = i.toString().padStart(6, '0');
        return imagePathTemplate.replace('{frame}', frameNumber);
    }

    _wrapFrame(i) {
        if (i < 1) return ((i % this.totalFrames) + this.totalFrames - 1) % this.totalFrames + 1;
        if (i > this.totalFrames) return ((i - 1) % this.totalFrames) + 1;
        return i;
    }

    _loadFrame(i, priority = false) {
        // Ensure requestedFrames Set exists
        if (!this._requestedFrames) {
            this._requestedFrames = new Set();
        }

        if (this._requestedFrames.has(i)) return;
        this._requestedFrames.add(i);

        const img = new Image();
        const src = this._frameToUrl(i);
        this._inFlight += 1;

        img.onload = () => {
            // Ensure images array exists before setting properties
            if (!this.images) {
                console.warn('Object360Viewer: images array was not initialized, creating it now');
                this.images = new Array(this.totalFrames);
            }
            
            try {
                this.images[i] = img;
                this.imagesLoaded++;
            } catch (err) {
                console.error(`Error storing image at index ${i}:`, err);
            }

            // Calculate and throttle progress updates (no need to spam the UI)
            const loadingPercent = Math.round((this.imagesLoaded / this.totalFrames) * 100);

            // Emit progress updates more strategically
            if (
                loadingPercent === 100 ||
                loadingPercent % 5 === 0 || // Every 5%
                this.imagesLoaded <= 10 ||  // First 10 frames
                this.imagesLoaded === 1     // First frame always
            ) {
                this._emit('o360:loadingProgress', {
                    frame: i,
                    loaded: this.imagesLoaded,
                    total: this.totalFrames,
                    percent: loadingPercent
                });
            }

            // Handle current frame display
            if (i === this.currentFrame) {
                // Ensure current frame paints as soon as it's available
                if (this.imageElement && this.imageElement.src !== img.src) {
                    this.imageElement.src = img.src;
                }
                if (!this._initialReadyEmitted) {
                    this._initialReadyEmitted = true;
                    this._emit('o360:ready', { frame: this.currentFrame });
                }
            }

            // Signal all images loaded
            if (this.imagesLoaded === this.totalFrames) {
                this._emit('o360:allLoaded', { total: this.totalFrames });
            }

            this._inFlight -= 1;
            this._pumpQueue();
        };

        img.onerror = (event) => {
            console.error(`Failed to load image: ${src}`);
            console.error('Image load error details:', {
                src: src,
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight,
                complete: img.complete,
                currentSrc: img.currentSrc,
                error: event
            });

            // Check if the viewer is in a valid state for retries
            if (!this || this._isDestroyed || !this._loadRetries || typeof this._loadRetries !== 'object') {
                console.warn(`Cannot retry loading frame ${i} - viewer is in an invalid state or being destroyed`);
                if (this && !this._isDestroyed) {
                    this._inFlight -= 1;
                    this._pumpQueue();
                }
                return;
            }

            // Safely handle retry logic
            if (typeof this._loadRetries[i] === 'undefined') {
                this._loadRetries[i] = 0;
            }

            // Retry loading up to 2 times
            if (this._loadRetries[i] < 2) {
                this._loadRetries[i]++;
                
                // Test the URL with fetch for debugging
                fetch(src, { method: 'HEAD' })
                    .then(response => {
                        console.log(`Fetch test for ${src}:`, {
                            status: response.status,
                            statusText: response.statusText,
                            headers: Object.fromEntries(response.headers.entries())
                        });
                    })
                    .catch(fetchError => {
                        console.error(`Fetch test failed for ${src}:`, fetchError);
                    });

                // Remove from requested frames so it can be requested again
                if (this._requestedFrames && this._requestedFrames instanceof Set) {
                    this._requestedFrames.delete(i);
                }

                // Retry after delay with exponential backoff
                const backoff = 500 * Math.pow(2, this._loadRetries[i] - 1);
                setTimeout(() => {
                    // Check again if _loadRetries still exists before retrying
                    if (this && this._loadRetries) {
                        console.log(`Retrying frame ${i}, attempt ${this._loadRetries[i]}`);
                        this._loadFrame(i, i === this.currentFrame); // Priority if it's current frame
                    } else {
                        console.warn(`Cannot retry frame ${i}, viewer has been destroyed`);
                    }
                }, backoff);
            } else {
                // After retries, give up and emit error
                if (this._emit) {
                    this._emit('o360:loadError', { frame: i, src });
                }
            }

            this._inFlight -= 1;
            this._pumpQueue();
        };

        img.src = src;
    }

    // Compatibility methods with empty bodies - functionality moved to inline handlers
    _handleImageLoad(img, i) {
        // Implementation moved to inline handler in _loadFrame
    }

    _handleImageError(i, src) {
        // Implementation moved to inline handler in _loadFrame
    }

    _pumpQueue() {
        // Ensure the queue and requested frames set exist
        if (!this._queue) {
            this._queue = [];
        }

        if (!this._requestedFrames) {
            this._requestedFrames = new Set();
        }

        // Process main queue first
        while (this._inFlight < this.preloadConcurrency && this._queue.length > 0) {
            const next = this._queue.shift();
            if (next === undefined || this._requestedFrames.has(next)) {
                continue;
            }
            this._loadFrame(next, false);
        }

        // If main queue is empty and we have priority frames, move them to main queue
        if (this._queue.length === 0 && this._priorityQueue && this._priorityQueue.length > 0) {
            // Move a batch of priority frames to main queue
            const batchSize = Math.min(10, this._priorityQueue.length);
            for (let i = 0; i < batchSize; i++) {
                this._queue.push(this._priorityQueue.shift());
            }
            
            // Continue pumping
            while (this._inFlight < this.preloadConcurrency && this._queue.length > 0) {
                const next = this._queue.shift();
                if (next === undefined || this._requestedFrames.has(next)) {
                    continue;
                }
                this._loadFrame(next, false);
            }
        }
    }

    _emit(name, detail) {
        try {
            const evt = new CustomEvent(name, { detail });
            (this.viewerElement || this.imageElement || document).dispatchEvent(evt);
        } catch (e) {
            // Older browsers: no-op
        }
    }

    setupEventListeners() {
        // Mouse events - only on viewer element to reduce event overhead
        this.onMouseDown = (e) => {
            this.isDragging = true;
            this.lastMouseX = e.clientX;
            e.preventDefault();
        };
        this.viewerElement.addEventListener('mousedown', this.onMouseDown);

        // Use throttled mouse move for better performance
        this.throttledMouseMove = this.throttle((e) => {
            if (this.isDragging) {
                this.handleDrag(e.clientX);
            }
        }, 16); // ~60fps

        document.addEventListener('mousemove', this.throttledMouseMove);

        this.onMouseUp = () => {
            if (this.isDragging) {
                this.isDragging = false;
            }
        };
        document.addEventListener('mouseup', this.onMouseUp);

        // Touch events for mobile - also throttled
        this.onTouchStart = (e) => {
            this.isDragging = true;
            this.lastMouseX = e.touches[0].clientX;
            e.preventDefault(); // Prevent scrolling
        };
        this.viewerElement.addEventListener('touchstart', this.onTouchStart, { passive: false }); // CRITICAL: Allows preventDefault to work

        this.throttledTouchMove = this.throttle((e) => {
            if (this.isDragging && e.touches && e.touches[0]) {
                this.handleDrag(e.touches[0].clientX);
                e.preventDefault(); // Prevent scrolling during drag
            }
        }, 16);

        document.addEventListener('touchmove', this.throttledTouchMove, { passive: false });

        // Store handlers for cleanup
        this.touchEndHandler = () => {
            if (this.isDragging) {
                this.isDragging = false;
            }
        };

        this.touchCancelHandler = () => {
            if (this.isDragging) {
                this.isDragging = false;
            }
        };

        document.addEventListener('touchend', this.touchEndHandler);

        // Add touchcancel for mobile interruptions
        document.addEventListener('touchcancel', this.touchCancelHandler);

        // Prevent context menu
        this.onContextMenu = (e) => {
            e.preventDefault();
        };
        this.viewerElement.addEventListener('contextmenu', this.onContextMenu);

        // Mouse wheel support - always prevent scrolling when over the viewer
        this.throttledWheel = this.throttle((e) => {
            // Always prevent default and stop propagation when wheel is used on the viewer
            e.preventDefault();
            e.stopPropagation();

            const delta = e.deltaY > 0 ? 1 : -1;
            this.currentFrame += delta;
            this.normalizeFrame();
            this.scheduleUpdate();
        }, 50);

        // Always use non-passive listener to ensure we can preventDefault
        this.viewerElement.addEventListener('wheel', this.throttledWheel, {
            passive: false
        });

        // Additional safety: capture any wheel events on the viewer element
        this.stopWheelPropagation = (e) => {
            // This ensures absolutely no wheel events escape the viewer
            e.stopPropagation();
        };

        this.viewerElement.addEventListener('wheel', this.stopWheelPropagation, {
            passive: false,
            capture: true
        });
    }

    // Throttle function to limit how often functions can be called
    throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    handleDrag(currentX) {
        const deltaX = currentX - this.lastMouseX;

        // Track user interaction for idle loading optimization
        this._lastInteractionTime = Date.now();

        // Only update if movement is significant enough
        if (Math.abs(deltaX) > 2) {
            const frameChange = Math.round(deltaX / this.sensitivity);

            if (frameChange !== 0) {
                this.currentFrame -= frameChange;
                this.normalizeFrame();
                this.scheduleUpdate();
                this.lastMouseX = currentX;

                // Preload nearby frames when user is actively dragging
                this._preloadNearbyFrames();
            }
        }
    }

    /**
     * Preload frames near the current frame when user is actively interacting
     */
    _preloadNearbyFrames() {
        if (this.preloadStrategy !== 'progressive') return;

        const currentFrame = this.currentFrame;
        const nearbyRadius = 3; // Load frames within 3 positions

        for (let offset = 1; offset <= nearbyRadius; offset++) {
            const forward = this._wrapFrame(currentFrame + offset);
            const backward = this._wrapFrame(currentFrame - offset);

            // Add to priority queue if not already requested
            if (!this._requestedFrames.has(forward)) {
                this._priorityQueue.unshift(forward); // Add to front for higher priority
            }
            if (!this._requestedFrames.has(backward)) {
                this._priorityQueue.unshift(backward);
            }
        }

        // Trigger queue processing
        this._pumpQueue();
    }

    // Use requestAnimationFrame for smooth updates
    scheduleUpdate() {
        if (!this.isAnimating) {
            this.isAnimating = true;
            this.animationId = requestAnimationFrame(() => {
                this.updateImage();
                this.isAnimating = false;
            });
        }
    }

    normalizeFrame() {
        if (this.currentFrame > this.totalFrames) {
            this.currentFrame = 1;
        } else if (this.currentFrame < 1) {
            this.currentFrame = this.totalFrames;
        }
    }

    updateImage() {
        if (this.images[this.currentFrame] && this.images[this.currentFrame].complete) {
            // Only update if the image actually changed
            const newSrc = this.images[this.currentFrame].src;
            if (this.imageElement.src !== newSrc) {
                this.imageElement.src = newSrc;
            }

            // Update frame counter if it exists
            if (this.frameCounter) {
                this.frameCounter.textContent = this.currentFrame;
            }

            // Handle memory management after each frame change
            if (this.memoryManagement !== 'none') {
                this._manageMemory();
            }

            // Emit frame change event
            this._emit('o360:frameChange', {
                frame: this.currentFrame,
                total: this.totalFrames,
                percent: Math.round((this.currentFrame / this.totalFrames) * 100)
            });
        } else {
            // Not loaded yet: kick off a priority load and optimistically set the src
            const url = this._frameToUrl(this.currentFrame);
            if (this._requestedFrames && !this._requestedFrames.has(this.currentFrame)) {
                this._loadFrame(this.currentFrame, true);
            }
            if (this.imageElement.src !== url) {
                this.imageElement.src = url;
            }
        }

        // Preload neighboring frames if they're not already loaded (for smoother scrubbing)
        const nextFrame = this._wrapFrame(this.currentFrame + 1);
        const prevFrame = this._wrapFrame(this.currentFrame - 1);

        if (this._requestedFrames && !this._requestedFrames.has(nextFrame)) {
            this._loadFrame(nextFrame, true);
        }

        if (this._requestedFrames && !this._requestedFrames.has(prevFrame)) {
            this._loadFrame(prevFrame, true);
        }
    }

    // Cleanup method to remove event listeners
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }

        // Cancel any pending requests
        for (const frameId in this._abortControllers) {
            if (this._abortControllers[frameId]) {
                try {
                    this._abortControllers[frameId].abort();
                } catch (e) {
                    // Ignore errors during abort
                }
            }
        }

        // Clear all image references to help with garbage collection
        if (this.images) {
            for (let i = 1; i <= this.totalFrames; i++) {
                if (this.images[i]) {
                    // Clear image src to release memory
                    if (this.images[i].src) {
                        this.images[i].src = '';
                    }
                    this.images[i] = null;
                }
            }
        }

        // Remove event listeners to prevent memory leaks
        if (this.onMouseDown) {
            this.viewerElement.removeEventListener('mousedown', this.onMouseDown);
        }
        if (this.throttledMouseMove) {
            document.removeEventListener('mousemove', this.throttledMouseMove);
        }
        if (this.onMouseUp) {
            document.removeEventListener('mouseup', this.onMouseUp);
        }
        if (this.throttledTouchMove) {
            document.removeEventListener('touchmove', this.throttledTouchMove);
        }
        if (this.onTouchStart) {
            this.viewerElement.removeEventListener('touchstart', this.onTouchStart, { passive: false });
        }
        if (this.throttledWheel) {
            this.viewerElement.removeEventListener('wheel', this.throttledWheel);
        }
        if (this.onContextMenu) {
            this.viewerElement.removeEventListener('contextmenu', this.onContextMenu);
        }
        // Clean up touch event listeners
        document.removeEventListener('touchend', this.touchEndHandler);
        document.removeEventListener('touchcancel', this.touchCancelHandler);
        // Clean up the capture listener as well
        if (this.viewerElement) {
            this.viewerElement.removeEventListener('wheel', this.stopWheelPropagation, { capture: true });
        }

        // Clear all data structures to prevent memory leaks
        if (this._requestedFrames) {
            this._requestedFrames.clear();
            this._requestedFrames = null;
        }

        // Cancel any pending image loads
        if (this._abortControllers) {
            Object.values(this._abortControllers).forEach(controller => {
                if (controller && typeof controller.abort === 'function') {
                    try {
                        controller.abort();
                    } catch (e) {
                        // Ignore errors during cleanup
                    }
                }
            });
        }

        // Set flag to indicate we're being destroyed
        this._isDestroyed = true;

        // Clean up intersection observer
        if (this._intersectionObserver) {
            this._intersectionObserver.disconnect();
            this._intersectionObserver = null;
        }

        // Cancel idle loading
        this._idleLoadingActive = false;

        this._abortControllers = null;
        this._loadRetries = null;
        this._queue = [];
        this._priorityQueue = [];
        this._idleQueue = [];

        // Clear all references to DOM elements
        this.viewerElement = null;
        this.imageElement = null;
        this.frameCounter = null;

        // Clear other object references
        this._abortControllers = null;
        this._requestedFrames = null;
        this._queue = null;
        this._priorityQueue = null;
        this._idleQueue = null;
        this.images = null;
        this.options = null;
    }

    // Helpers
    getCurrentFrame() {
        return this.currentFrame;
    }

    goToFrame(n) {
        if (typeof n !== 'number') return;
        this.currentFrame = Math.min(Math.max(1, Math.round(n)), this.totalFrames);
        this.scheduleUpdate();
    }

    // Switch from lazy to eager loading strategy
    switchToEager() {
        if (this.preloadStrategy === 'eager') return;

        console.log('Switching to eager loading strategy');
        this.preloadStrategy = 'eager';
        
        // Cancel idle loading
        this._idleLoadingActive = false;
        
        // Move all remaining frames to main queue
        if (this._priorityQueue) {
            this._queue.push(...this._priorityQueue);
            this._priorityQueue = [];
        }
        if (this._idleQueue) {
            this._queue.push(...this._idleQueue);
            this._idleQueue = [];
        }

        // Clear existing queue and re-prioritize frames near the current one
        const frameDistances = [];

        for (let i = 1; i <= this.totalFrames; i++) {
            if (!this.images[i] || !this.images[i].complete) {
                if (this._requestedFrames && !this._requestedFrames.has(i)) {
                    // Calculate distance from current frame, accounting for wraparound
                    let distance = Math.abs(i - this.currentFrame);
                    // Handle wraparound (e.g. frame 1 and frame 179 are close to each other in a circle)
                    distance = Math.min(distance, this.totalFrames - distance);

                    frameDistances.push({ frame: i, distance });
                }
            }
        }

        // Sort by distance (closest first)
        frameDistances.sort((a, b) => a.distance - b.distance);

        // Add to queue in order of distance
        this._queue = frameDistances.map(item => item.frame);
        
        // Resume pumping with higher concurrency
        this.preloadConcurrency = Math.min(this.preloadConcurrency + 2, 12);
        this._pumpQueue();
    }

    /**
     * Unload a frame to free up memory (called by memory management)
     */
    unloadFrame(frameIndex) {
        // Skip current frame and neighbors
        if (frameIndex === this.currentFrame) return false;
        if (frameIndex === this._wrapFrame(this.currentFrame - 1)) return false;
        if (frameIndex === this._wrapFrame(this.currentFrame + 1)) return false;

        // If we have the image loaded, we can dispose of it
        if (this.images[frameIndex]) {
            // Set image source to blank to help garbage collection
            if (this.images[frameIndex].src) {
                this.images[frameIndex].src = '';
            }

            // Remove references
            this.images[frameIndex] = null;
            return true;
        }

        return false;
    }

    /**
     * Get loading statistics
     */
    getLoadingStats() {
        return {
            loaded: this.imagesLoaded,
            total: this.totalFrames,
            percent: Math.round((this.imagesLoaded / this.totalFrames) * 100),
            strategy: this.preloadStrategy,
            inFlight: this._inFlight,
            queueLength: this._queue ? this._queue.length : 0,
            priorityQueueLength: this._priorityQueue ? this._priorityQueue.length : 0,
            idleQueueLength: this._idleQueue ? this._idleQueue.length : 0
        };
    }
    _manageMemory() {
        // Skip if memory management is disabled
        if (this.memoryManagement === 'none') return;

        // For aggressive mode, we only keep neighboring frames
        if (this.memoryManagement === 'aggressive') {
            for (let i = 1; i <= this.totalFrames; i++) {
                if (i !== this.currentFrame &&
                    i !== this._wrapFrame(this.currentFrame - 1) &&
                    i !== this._wrapFrame(this.currentFrame + 1)) {
                    this.unloadFrame(i);
                }
            }
        }
        // For moderate mode, we keep a sliding window of frames
        else if (this.memoryManagement === 'moderate') {
            // If we've loaded more than our cache limit
            if (this.images.filter(Boolean).length > this.cacheSize) {
                // Find furthest frame from current that is loaded
                let furthestFrame = null;
                let maxDistance = 0;

                for (let i = 1; i <= this.totalFrames; i++) {
                    if (this.images[i]) {
                        let distance = Math.abs(i - this.currentFrame);
                        // Handle wraparound
                        distance = Math.min(distance, this.totalFrames - distance);

                        if (distance > maxDistance) {
                            maxDistance = distance;
                            furthestFrame = i;
                        }
                    }
                }

                // Unload the furthest frame
                if (furthestFrame !== null) {
                    this.unloadFrame(furthestFrame);
                }
            }
        }
    }
}
console.log("===================================== object360Viewer.js loaded =========================================");

// Make available globally
window.Object360Viewer = Object360Viewer;

// Export for ES modules
export default Object360Viewer;
