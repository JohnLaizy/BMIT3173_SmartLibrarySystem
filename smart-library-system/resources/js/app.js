function animatePageContent() {
    const page = document.querySelector(
        '[data-page-transition]'
    );

    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (!page || reduceMotion) {
        return;
    }

    page.getAnimations().forEach((animation) => {
        animation.cancel();
    });

    page.animate(
        [
            {
                opacity: 0,
                transform: 'translateY(14px) scale(0.99)',
                filter: 'blur(2px)',
            },
            {
                opacity: 1,
                transform: 'translateY(0) scale(1)',
                filter: 'blur(0)',
            },
        ],
        {
            duration: 280,
            easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
            fill: 'both',
        },
    );
}

document.addEventListener('livewire:navigated', () => {
    requestAnimationFrame(animatePageContent);
});