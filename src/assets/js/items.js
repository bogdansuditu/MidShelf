/**
 * JavaScript for Item Modal and Form Handling
 */

document.addEventListener('DOMContentLoaded', () => {
    // Attach submit handler only after DOM is loaded
    const itemForm = document.getElementById('itemForm');
    if (itemForm) {
        itemForm.addEventListener('submit', handleItemSubmit);
    }

    // Initial setup for tags and rating inputs (ensure elements exist first)
    const ratingInput = document.getElementById('ratingInput');
    if (ratingInput) {
        initializeRatingInput();
    }
    const tagsInput = document.getElementById('tagsInput');
    if (tagsInput) {
        initializeTagsInput();
    }

    // Attach cancel button handler
    const cancelButton = document.getElementById('cancelItemForm');
    if (cancelButton) {
        cancelButton.addEventListener('click', closeItemModal);
    }
});

// --- Modal Management ---

async function openItemModal(itemId = null) {
    const modal = document.getElementById('itemModal');
    const form = document.getElementById('itemForm');
    const title = document.getElementById('itemModalTitle');
    const submitButton = form.querySelector('button[type="submit"]');

    form.reset(); // Clear previous data
    resetTagsInput(); // Clear existing tag spans
    setRating(0); // Reset rating visually
    document.getElementById('tagsHidden').value = ''; // Clear hidden tags
    form.querySelector('input[name="id"]').value = ''; // Clear hidden ID

    if (itemId) {
        title.textContent = 'Edit Item';
        submitButton.textContent = 'Update Item';
        try {
            const response = await fetch(`/api/items.php?id=${itemId}`);
            if (!response.ok) {
                throw new Error('Failed to load item data');
            }
            const item = await response.json();

            // Populate form
            form.querySelector('input[name="id"]').value = item.id;
            form.querySelector('#name').value = item.name || '';
            form.querySelector('#description').value = item.description || '';
            form.querySelector('#category').value = item.category_id || '';
            form.querySelector('#location').value = item.location_id || '';
            setRating(item.rating || 0); // Set rating value and update stars

            // Populate tags
            const tags = item.tags || [];
            tags.forEach(addTag);
            document.getElementById('tagsHidden').value = tags.join(',');

        } catch (error) {
            console.error('Error loading item:', error);
            alert('Could not load item details.');
            return; // Don't open modal if data fails to load
        }
    } else {
        title.textContent = 'Add Item';
        submitButton.textContent = 'Add Item';
        // Ensure hidden ID is cleared for new items
        form.querySelector('input[name="id"]').value = ''; 
    }

    modal.classList.add('active');
}

function closeItemModal() {
    const modal = document.getElementById('itemModal');
    modal.classList.remove('active');
}

// --- Form Submission ---

async function handleItemSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const data = {
        id: formData.get('id') || null, // Get ID from hidden input
        name: formData.get('name'),
        description: formData.get('description'),
        category_id: formData.get('category_id') || null, // Handle empty selection
        location_id: formData.get('location_id') || null, // Handle empty selection
        tags: formData.get('tags') ? formData.get('tags').split(',') : [],
        rating: parseInt(formData.get('rating'), 10) || 0
    };

    const method = data.id ? 'PUT' : 'POST';
    const url = data.id ? `/api/items.php?id=${data.id}` : '/api/items.php';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            closeItemModal();
            location.reload(); // Reload the page to see changes
            // Future enhancement: Update table via JS instead of reload
        } else {
            const errorData = await response.json();
            alert('Error: ' + (errorData.message || 'Failed to save item.'));
        }
    } catch (error) {
        console.error('Error submitting item:', error);
        alert('An error occurred while saving the item.');
    }
}

// --- Rating Input Logic ---

function initializeRatingInput() {
    const ratingInput = document.getElementById('ratingInput');
    const buttons = ratingInput.querySelectorAll('button');
    buttons.forEach((button, index) => {
        button.addEventListener('click', () => setRating(index + 1));
        // Ensure initial state based on hidden input is correct (handled in openItemModal)
    });
}

function setRating(value) {
    const ratingHidden = document.getElementById('ratingHidden');
    const ratingInput = document.getElementById('ratingInput');
    if (!ratingHidden || !ratingInput) return; // Exit if elements don't exist

    ratingHidden.value = value;
    const buttons = ratingInput.querySelectorAll('button');
    buttons.forEach((button, index) => {
        const icon = button.querySelector('i');
        if (index < value) {
            icon.className = 'fas fa-star'; // Filled star
        } else {
            icon.className = 'far fa-star'; // Empty star
        }
    });
}

// --- Tags Input Logic ---

let currentTags = [];
let tagSuggestions = [];

function initializeTagsInput() {
    const tagsInputDiv = document.getElementById('tagsInput');
    const tagInputElement = document.getElementById('tagInput');
    const tagSuggestionsDiv = document.getElementById('tagSuggestions');
    const tagsHiddenInput = document.getElementById('tagsHidden');

    if (!tagsInputDiv || !tagInputElement || !tagSuggestionsDiv || !tagsHiddenInput) {
        console.error('Tag input elements not found');
        return;
    }

    // Initialize currentTags from hidden input if editing
    if (tagsHiddenInput.value) {
        currentTags = tagsHiddenInput.value.split(',').filter(tag => tag.trim() !== '');
    }

    let suggestionDebounceTimeout;
    tagInputElement.addEventListener('input', () => {
        const search = tagInputElement.value.trim();
        clearTimeout(suggestionDebounceTimeout);
        if (search.length > 1) {
            suggestionDebounceTimeout = setTimeout(async () => {
                tagSuggestions = await fetchTagSuggestions(search);
                showSuggestions(tagSuggestions);
            }, 300);
        } else {
            hideSuggestions();
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!tagsInputDiv.contains(e.target)) {
            hideSuggestions();
        }
    });

    // Add tag on Enter key
    tagInputElement.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const value = tagInputElement.value.trim();
            if (value) {
                addTag(value);
            }
        }
    });
}

function updateTagsHiddenInput() {
    const tagsHiddenInput = document.getElementById('tagsHidden');
    if(tagsHiddenInput) {
      tagsHiddenInput.value = currentTags.join(',');
    } 
}

function resetTagsInput() {
    const tagsInputDiv = document.getElementById('tagsInput');
    const existingTags = tagsInputDiv.querySelectorAll('span.tag');
    existingTags.forEach(tag => tag.remove());
    currentTags = [];
    updateTagsHiddenInput();
}

function addTag(value) {
    const tagsInputDiv = document.getElementById('tagsInput');
    const tagInputElement = document.getElementById('tagInput');
    value = value.trim();

    // Don't add if already exists or is empty
    if (!value || currentTags.includes(value)) {
        tagInputElement.value = ''; // Clear input even if tag wasn't added
        hideSuggestions();
        return;
    }

    currentTags.push(value);

    const tag = document.createElement('span');
    tag.className = 'tag';
    tag.innerHTML = `
        ${value}
        <button type="button" onclick="removeTag(this, '${value}')">×</button>
    `;
    tagsInputDiv.insertBefore(tag, tagInputElement);
    updateTagsHiddenInput();
    tagInputElement.value = '';
    hideSuggestions();
}

function removeTag(button, value) {
    button.parentElement.remove();
    currentTags = currentTags.filter(tag => tag !== value);
    updateTagsHiddenInput();
}

// Fetch tag suggestions
async function fetchTagSuggestions(search) {
    try {
        const response = await fetch(`/api/tags.php?search=${encodeURIComponent(search)}`);
        if (response.ok) {
            const suggestions = await response.json();
            return suggestions.map(s => s.name); // Assuming API returns {name: 'tagname'}
        }
        return [];
    } catch (error) {
        console.error('Error fetching tag suggestions:', error);
        return [];
    }
}

// Show tag suggestions
function showSuggestions(suggestions) {
    const tagSuggestionsDiv = document.getElementById('tagSuggestions');
    const tagInputElement = document.getElementById('tagInput');
    tagSuggestionsDiv.innerHTML = '';

    const filteredSuggestions = suggestions.filter(tag => !currentTags.includes(tag));

    if (filteredSuggestions.length === 0) {
        hideSuggestions();
        return;
    }

    filteredSuggestions.forEach(tag => {
        const suggestion = document.createElement('div');
        suggestion.className = 'tag-suggestion';
        suggestion.textContent = tag;
        suggestion.addEventListener('click', () => {
            addTag(tag);
            tagInputElement.focus(); // Keep focus on input after adding
        });
        tagSuggestionsDiv.appendChild(suggestion);
    });

    tagSuggestionsDiv.style.display = 'block';
}

// Hide tag suggestions
function hideSuggestions() {
    const tagSuggestionsDiv = document.getElementById('tagSuggestions');
    if (tagSuggestionsDiv) {
      tagSuggestionsDiv.style.display = 'none';
      tagSuggestionsDiv.innerHTML = ''; // Clear suggestions
    }
}

// --- Duplicate Item Function ---
async function duplicateItem(id) {
    try {
        // 1. Fetch the original item data
        const fetchResponse = await fetch(`/api/items.php?id=${id}`);
        if (!fetchResponse.ok) {
            const errorData = await fetchResponse.json();
            throw new Error(errorData.message || 'Failed to fetch item data for duplication.');
        }
        const itemData = await fetchResponse.json();

        // 2. Modify the data for the new item
        const newItemData = { ...itemData }; // Create a copy
        delete newItemData.id; // Remove ID to indicate new item
        newItemData.name = `${itemData.name} - copy`; // Append to name
        // Ensure tags is an array if it exists
        if (newItemData.tags && typeof newItemData.tags === 'string') {
             newItemData.tags = newItemData.tags.split(',');
        }
        
        // 3. Send the new data using POST
        const postResponse = await fetch('/api/items.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(newItemData)
        });

        if (postResponse.ok) {
            location.reload(); // Reload to show the new item
        } else {
            const errorData = await postResponse.json();
            throw new Error(errorData.message || 'Failed to create duplicate item.');
        }

    } catch (error) {
        console.error('Error duplicating item:', error);
        alert(`Failed to duplicate item: ${error.message}`);
    }
}

// Delete Item Function (from app.js - needed if called from items.php)
async function deleteItem(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // Standard red for delete
        cancelButtonColor: '#3085d6', // Standard blue for cancel
        confirmButtonText: 'Yes, delete it!',
        background: 'var(--color-surface-raised)', // Use CSS variables for theme consistency
        color: 'var(--color-text)', // Use CSS variables
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`/api/items.php?id=${id}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    location.reload(); // Just reload on success
                } else {
                    const errorData = await response.json();
                    Swal.fire(
                        'Error!',
                        `Failed to delete item: ${errorData.message || 'Unknown error'}`,
                        'error'
                    );
                }
            } catch (error) {
                console.error('Error deleting item:', error);
                Swal.fire(
                    'Error!',
                    'An unexpected error occurred while deleting the item.',
                    'error'
                );
            }
        }
    });
}
