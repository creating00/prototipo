import { Chart, registerables } from "chart.js";
Chart.register(...registerables);

document.addEventListener("DOMContentLoaded", () => {
    if (!window.analyticsData) return;

    /** REVENUE **/
    const revenueCtx = document.getElementById("revenueChart");
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: "line",
            data: {
                labels: window.analyticsData.months,
                datasets: [
                    {
                        label: "Recaudación",
                        data: window.analyticsData.revenue,
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            },
        });
    }

    /** PROFITS **/
    const profitCtx = document.getElementById("profitChart");
    if (profitCtx) {
        new Chart(profitCtx, {
            type: "bar",
            data: {
                labels: window.analyticsData.months,
                datasets: [
                    {
                        label: "Ganancia",
                        data: window.analyticsData.profits,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            },
        });
    }
});
