/**
 * Star Rating question type JavaScript
 * 
 * This file is part of Moodle - http://moodle.org/
 *
 * @package    mod_questionnaire
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    /**
     * Initialize star rating functionality
     */
    function initStarRating() {
        var starContainers = document.querySelectorAll('.star-rating-row');
        
        starContainers.forEach(function(container) {
            var stars = container.querySelectorAll('.star:not([disabled])');
            var input = container.querySelector('.star-rating-value');
            var ratingText = container.querySelector('.current-rating');
            
            if (!stars.length || !input) {
                return;
            }
            
            // Click handler
            stars.forEach(function(star) {
                star.addEventListener('click', function() {
                    var value = parseInt(this.getAttribute('data-value'));
                    updateRating(stars, input, ratingText, value);
                });
                
                // Keyboard support for accessibility
                star.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        var value = parseInt(this.getAttribute('data-value'));
                        updateRating(stars, input, ratingText, value);
                    }
                });
            });
            
            // Hover effect to preview rating
            stars.forEach(function(star, index) {
                star.addEventListener('mouseenter', function() {
                    highlightStars(stars, index + 1, true);
                });
            });
            
            // Reset to actual rating when mouse leaves
            container.addEventListener('mouseleave', function() {
                var currentValue = parseInt(input.value) || 0;
                highlightStars(stars, currentValue, false);
            });
        });
    }
    
    /**
     * Update rating value
     * @param {NodeList} stars - Star elements
     * @param {HTMLElement} input - Hidden input element
     * @param {HTMLElement} ratingText - Rating text display element
     * @param {number} value - Rating value
     */
    function updateRating(stars, input, ratingText, value) {
        // Allow clicking same star to clear rating
        if (parseInt(input.value) === value) {
            value = 0;
        }
        
        input.value = value;
        if (ratingText) {
            ratingText.textContent = value;
        }
        highlightStars(stars, value, false);
        
        // Trigger change event for form validation
        var event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
    }
    
    /**
     * Highlight stars up to the given value
     * @param {NodeList} stars - Star elements
     * @param {number} value - Number of stars to highlight
     * @param {boolean} isHover - Whether this is a hover state
     */
    function highlightStars(stars, value, isHover) {
        stars.forEach(function(star, index) {
            var starValue = index + 1;
            var icon = star.querySelector('i');
            
            if (starValue <= value) {
                if (!isHover) {
                    star.classList.add('star-selected');
                    star.classList.remove('star-hover');
                } else {
                    star.classList.add('star-hover');
                }
                if (icon) {
                    icon.className = 'fa fa-star';
                }
            } else {
                star.classList.remove('star-selected', 'star-hover');
                if (icon) {
                    icon.className = 'fa fa-star-o';
                }
            }
        });
    }
    
    /**
     * Initialize keyboard navigation for stars
     * @param {NodeList} stars - Star elements
     */
    function initKeyboardNavigation(stars) {
        stars.forEach(function(star, index) {
            star.addEventListener('keydown', function(e) {
                var currentIndex = index;
                var nextStar = null;
                
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        if (currentIndex > 0) {
                            nextStar = stars[currentIndex - 1];
                        }
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        if (currentIndex < stars.length - 1) {
                            nextStar = stars[currentIndex + 1];
                        }
                        break;
                    case 'Home':
                        e.preventDefault();
                        nextStar = stars[0];
                        break;
                    case 'End':
                        e.preventDefault();
                        nextStar = stars[stars.length - 1];
                        break;
                }
                
                if (nextStar) {
                    nextStar.focus();
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStarRating);
    } else {
        initStarRating();
    }
    
    // Re-initialize for dynamic content (e.g., AJAX loaded content)
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldInit = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && 
                            (node.classList && node.classList.contains('star-rating-question') ||
                             node.querySelector && node.querySelector('.star-rating-question'))) {
                            shouldInit = true;
                        }
                    });
                }
            });
            if (shouldInit) {
                initStarRating();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Export for potential external use
    window.QuestionnaireStarRating = {
        init: initStarRating
    };
})();
