document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const required = form.querySelectorAll('[required]');
            let valid = true;
            required.forEach((input) => {
                if (!input.value.trim()) {
                    input.classList.add('border-red-500');
                    valid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            if (!valid) {
                event.preventDefault();
                alert('Please complete all required fields.');
            }
        });
    });

    document.querySelectorAll('.accordion-item').forEach((item) => {
        item.querySelector('.accordion-header')?.addEventListener('click', () => {
            item.classList.toggle('active');
        });
    });

    document.querySelectorAll('[data-animate]').forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
        el.classList.add('fade-in');
    });
});
