/**
 * Mobile optimizations for better form handling on mobile devices
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fix for iOS zoom on input focus
    const metaViewport = document.querySelector('meta[name=viewport]');
    if (metaViewport) {
        metaViewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    }
    
    // Add touch-friendly classes to all interactive elements
    const interactiveElements = document.querySelectorAll('button, input[type=submit], input[type=button], a.btn, .action-btn');
    interactiveElements.forEach(function(element) {
        element.classList.add('touch-friendly');
        
        // Add active state handling for better touch feedback
        element.addEventListener('touchstart', function() {
            this.classList.add('active');
        });
        
        element.addEventListener('touchend', function() {
            this.classList.remove('active');
        });
    });
    
    // Improve form element sizing for touch
    const formElements = document.querySelectorAll('input, select, textarea');
    formElements.forEach(function(element) {
        if (window.innerWidth <= 768) {
            if (element.offsetWidth < 44) {
                element.style.minHeight = '44px';
            }
        }
    });
    
    // Fix for iOS position:fixed issues
    const fixedElements = document.querySelectorAll('.modal-content, .fixed-header, .fixed-footer');
    let scrollPosition = 0;
    
    function lockScroll() {
        scrollPosition = window.pageYOffset;
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
    }
    
    function unlockScroll() {
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('position');
        document.body.style.removeProperty('top');
        document.body.style.removeProperty('width');
        window.scrollTo(0, scrollPosition);
    }
    
    // Apply to all modal open/close buttons
    const modalOpenButtons = document.querySelectorAll('[data-modal-open]');
    modalOpenButtons.forEach(function(button) {
        button.addEventListener('click', lockScroll);
    });
    
    const modalCloseButtons = document.querySelectorAll('[data-modal-close]');
    modalCloseButtons.forEach(function(button) {
        button.addEventListener('click', unlockScroll);
    });
    
    // Adjust table displays for mobile
    const tables = document.querySelectorAll('table');
    if (window.innerWidth <= 768) {
        tables.forEach(function(table) {
            if (!table.parentElement.classList.contains('table-container')) {
                const wrapper = document.createElement('div');
                wrapper.classList.add('table-container');
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }
}); 