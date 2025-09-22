document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('img.fade-img').forEach(img => {
        img.addEventListener('load', () => {
            img.style.opacity = 1;
        });
    });
});