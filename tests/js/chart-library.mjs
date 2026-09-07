import { test } from 'node:test';
import assert from 'node:assert/strict';
import Chart from '../../resources/js/chart-library.js';

for (const type of ['line', 'bar', 'doughnut']) {
    test(`optimized library renders and destroys ${type} charts`, () => {
        const canvas = { width: 600, height: 300 };
        const context = new Proxy({
            canvas,
            measureText: text => ({ width: String(text).length * 8 }),
            getLineDash: () => [],
        }, { get: (target, key) => key in target ? target[key] : () => {} });
        canvas.getContext = () => context;
        const chart = new Chart(canvas, {
            type,
            data: { labels: ['Masuk', 'Keluar'], datasets: [{ data: [10, 20], fill: type === 'line' }] },
            options: { responsive: false, animation: false },
        });
        assert.equal(chart.getDatasetMeta(0).type, type);
        assert.equal(chart.getDatasetMeta(0).data.length, 2);
        chart.update();
        chart.destroy();
        assert.equal(Chart.getChart(canvas), undefined);
    });
}
