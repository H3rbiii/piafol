const track = document.querySelector('[data-products-track]');
const previousButton = document.querySelector('[data-carousel-prev]');
const nextButton = document.querySelector('[data-carousel-next]');
const filterButtons = document.querySelectorAll('[data-filter]');

if (track && previousButton && nextButton) {
    const scrollTrack = (direction) => {
        track.scrollBy({
            left: direction * track.clientWidth * 0.82,
            behavior: 'smooth'
        });
    };

    const updateButtons = () => {
        const maxScroll = track.scrollWidth - track.clientWidth - 1;
        previousButton.disabled = track.scrollLeft <= 1;
        nextButton.disabled = track.scrollLeft >= maxScroll;
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;

            filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
            track.scrollTo({ left: 0, behavior: 'auto' });
            track.querySelectorAll('.product-card').forEach((card) => {
                const categories = card.dataset.category?.split(' ') ?? [];
                card.hidden = filter !== 'all' && !categories.includes(filter);
            });
            updateButtons();
        });
    });

    previousButton.addEventListener('click', () => scrollTrack(-1));
    nextButton.addEventListener('click', () => scrollTrack(1));
    track.addEventListener('scroll', updateButtons, { passive: true });
    track.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') scrollTrack(-1);
        if (event.key === 'ArrowRight') scrollTrack(1);
    });
    window.addEventListener('resize', updateButtons);
    updateButtons();
}

