// Only register the chart types used by dashboard and reports.
// This module remains a dynamic import, so pages without charts do not download it.
import {
    Chart, LineController, BarController, DoughnutController,
    CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement,
    Filler, Tooltip, Legend,
} from 'chart.js';

Chart.register(
    LineController, BarController, DoughnutController,
    CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement,
    Filler, Tooltip, Legend,
);

export default Chart;
