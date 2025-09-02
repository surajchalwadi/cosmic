/**
 * Search Result Highlighting System
 * Highlights search terms on destination pages when coming from global search
 */

// CSS for highlighting
const highlightCSS = `
<style>
.search-highlight {
    background-color: #fff3cd !important;
    color: #856404 !important;
    padding: 2px 4px !important;
    border-radius: 3px !important;
    font-weight: 600 !important;
    border: 1px solid #ffeaa7 !important;
    box-shadow: 0 1px 3px rgba(255, 193, 7, 0.3) !important;
    animation: highlightPulse 2s ease-in-out !important;
}

@keyframes highlightPulse {
    0% { 
        background-color: #ffc107 !important;
        transform: scale(1.05);
    }
    50% { 
        background-color: #fff3cd !important;
        transform: scale(1.02);
    }
    100% { 
        background-color: #fff3cd !important;
        transform: scale(1);
    }
}

.search-highlight-row {
    background-color: rgba(255, 193, 7, 0.1) !important;
    border-left: 4px solid #ffc107 !important;
    animation: rowHighlight 3s ease-in-out !important;
}

@keyframes rowHighlight {
    0% { 
        background-color: rgba(255, 193, 7, 0.3) !important;
    }
    100% { 
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
}
</style>
`;

// Add CSS to document head
document.head.insertAdjacentHTML('beforeend', highlightCSS);

/**
 * Get URL parameters
 */
function getURLParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Escape special regex characters
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Highlight text within an element
 */
function highlightTextInElement(element, searchTerm) {
    if (!element || !searchTerm) return;
    
    const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
    
    // Get all text nodes
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        if (node.nodeValue.trim() && regex.test(node.nodeValue)) {
            textNodes.push(node);
        }
    }
    
    // Highlight text in each text node
    textNodes.forEach(textNode => {
        const parent = textNode.parentNode;
        if (parent && !parent.classList.contains('search-highlight')) {
            const highlightedHTML = textNode.nodeValue.replace(regex, '<span class="search-highlight">$1</span>');
            const wrapper = document.createElement('span');
            wrapper.innerHTML = highlightedHTML;
            parent.replaceChild(wrapper, textNode);
        }
    });
}

/**
 * Highlight search terms in table rows
 */
function highlightTableRows(searchTerm) {
    if (!searchTerm) return;
    
    const tables = document.querySelectorAll('table tbody tr');
    let highlightedCount = 0;
    
    tables.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        const searchLower = searchTerm.toLowerCase();
        
        if (rowText.includes(searchLower)) {
            row.classList.add('search-highlight-row');
            highlightTextInElement(row, searchTerm);
            highlightedCount++;
            
            // Scroll to first highlighted row
            if (highlightedCount === 1) {
                setTimeout(() => {
                    row.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 500);
            }
        }
    });
    
    return highlightedCount;
}

/**
 * Highlight search terms in card elements
 */
function highlightCards(searchTerm) {
    if (!searchTerm) return;
    
    const cards = document.querySelectorAll('.card, .list-group-item, .product-item');
    let highlightedCount = 0;
    
    cards.forEach(card => {
        const cardText = card.textContent.toLowerCase();
        const searchLower = searchTerm.toLowerCase();
        
        if (cardText.includes(searchLower)) {
            card.classList.add('search-highlight-row');
            highlightTextInElement(card, searchTerm);
            highlightedCount++;
            
            // Scroll to first highlighted card
            if (highlightedCount === 1) {
                setTimeout(() => {
                    card.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 500);
            }
        }
    });
    
    return highlightedCount;
}

/**
 * Show notification about highlighted results
 */
function showHighlightNotification(count, searchTerm) {
    if (count === 0) return;
    
    const notification = document.createElement('div');
    notification.className = 'alert alert-info alert-dismissible fade show';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        <i class="fas fa-search me-2"></i>
        <strong>${count}</strong> result${count > 1 ? 's' : ''} highlighted for "<strong>${searchTerm}</strong>"
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Main highlighting function
 */
function initializeSearchHighlighting() {
    const highlightTerm = getURLParameter('highlight');
    
    if (!highlightTerm) return;
    
    // Wait for page to be fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            performHighlighting(highlightTerm);
        });
    } else {
        performHighlighting(highlightTerm);
    }
}

/**
 * Perform the actual highlighting
 */
function performHighlighting(searchTerm) {
    let totalHighlighted = 0;
    
    // Highlight in tables
    totalHighlighted += highlightTableRows(searchTerm);
    
    // Highlight in cards if no table results
    if (totalHighlighted === 0) {
        totalHighlighted += highlightCards(searchTerm);
    }
    
    // Show notification
    showHighlightNotification(totalHighlighted, searchTerm);
    
    // Update page title to indicate search results
    if (totalHighlighted > 0) {
        document.title = `${document.title} - Search: ${searchTerm}`;
    }
}

/**
 * Clear highlighting (utility function)
 */
function clearHighlighting() {
    // Remove highlight classes
    document.querySelectorAll('.search-highlight').forEach(el => {
        const parent = el.parentNode;
        parent.replaceChild(document.createTextNode(el.textContent), el);
        parent.normalize();
    });
    
    document.querySelectorAll('.search-highlight-row').forEach(el => {
        el.classList.remove('search-highlight-row');
    });
    
    // Remove URL parameter
    const url = new URL(window.location);
    url.searchParams.delete('highlight');
    window.history.replaceState({}, document.title, url);
}

// Initialize highlighting when script loads
initializeSearchHighlighting();

// Export functions for manual use
window.searchHighlight = {
    highlight: performHighlighting,
    clear: clearHighlighting,
    init: initializeSearchHighlighting
};
