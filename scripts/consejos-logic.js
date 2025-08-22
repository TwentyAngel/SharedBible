document.addEventListener('DOMContentLoaded', () => {
    const counselSearch = document.getElementById('counselSearch');
    const searchButton = document.getElementById('searchButton');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const counselList = document.getElementById('counselList');
    const counselCards = counselList.querySelectorAll('.counsel-card');

    // Function to filter and search cards
    const filterAndSearchCards = () => {
        const searchTerm = counselSearch.value.toLowerCase().trim();
        const activeCategory = document.querySelector('.filter-btn.active').dataset.category;

        counselCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const cardCategory = card.dataset.category;

            const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
            const matchesCategory = activeCategory === 'Todos' || cardCategory === activeCategory;

            if (matchesSearch && matchesCategory) {
                card.style.display = 'block'; // Show the card
            } else {
                card.style.display = 'none'; // Hide the card
            }
        });
    };

    // Event listener for search button click
    searchButton.addEventListener('click', filterAndSearchCards);

    // Event listener for pressing Enter in the search input
    counselSearch.addEventListener('keyup', (event) => {
        if (event.key === 'Enter') {
            filterAndSearchCards();
        }
    });

    // Event listeners for filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all filter buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to the clicked button
            button.classList.add('active');
            // Apply filters
            filterAndSearchCards();
        });
    });

    // Initial filter on page load (to show "Todos" by default)
    filterAndSearchCards();
});