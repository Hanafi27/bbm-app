// Loading effect
function showLoading(btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-spin mr-2">&#9696;</span>Loading...';
}

// 3D Card Hover
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.card-3d').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.transform = `scale(1.03) rotateY(${(x-rect.width/2)/20}deg) rotateX(${-(y-rect.height/2)/20}deg)`;
            card.classList.add('shadow-2xl');
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.classList.remove('shadow-2xl');
        });
    });
});

// Chart.js init (contoh dasar)
// window.initChart = function(ctx, data, options) {
//     return new Chart(ctx, { type: 'bar', data, options });
// } 