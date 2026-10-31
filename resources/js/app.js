import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
window.formatBRL = (v) => brl.format(Number(v || 0));

// Gráficos declarativos: <canvas data-chart='{"type":"bar","data":{...}}'>
function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((el) => {
        if (el._chart) return;
        const cfg = JSON.parse(el.dataset.chart);
        const money = el.dataset.money !== 'false';
        cfg.options = Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } },
                tooltip: money ? {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label || ctx.label}: ${brl.format(ctx.parsed.y ?? ctx.parsed)}`,
                    },
                } : {},
            },
        }, cfg.options || {});
        if (money && ['bar', 'line'].includes(cfg.type)) {
            cfg.options.scales = Object.assign({
                y: { ticks: { callback: (v) => brl.format(v) } },
            }, cfg.options.scales || {});
        }
        el._chart = new Chart(el, cfg);
    });
}

document.addEventListener('DOMContentLoaded', renderCharts);
Alpine.start();
