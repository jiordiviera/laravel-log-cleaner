document.addEventListener('DOMContentLoaded', (event) => {
    highlightCode();
    setupIntersectionObserver();
    setupSmoothScrolling();
    setCurrentYear();
});

function highlightCode() {
    document.querySelectorAll('pre code').forEach((el) => {
        hljs.highlightElement(el);
    });
}

function setupIntersectionObserver() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                updateActiveLink(entry.target.id);
                history.pushState(null, null, `#${entry.target.id}`);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.section-content').forEach(section => {
        observer.observe(section);
    });
}

function updateActiveLink(targetId) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active-link');
        if (link.getAttribute('href').slice(1) === targetId) {
            link.classList.add('active-link');
        }
    });
}

function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
}

function setCurrentYear() {
    document.getElementById('current-year').textContent = new Date().getFullYear();
}

function copyCode(button) {
    const pre = button.parentElement.querySelector('pre');
    const code = pre.textContent;
    navigator.clipboard.writeText(code).then(() => {
        button.textContent = 'Copié !';
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
        setTimeout(() => {
            button.textContent = 'Copier';
        }, 2000);
    });
}