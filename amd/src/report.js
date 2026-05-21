import * as d3 from './d3';

const renderPlot = (element, options, responseStats) => {
    const width = 250;
    const height = 130;
    const margin = {top: 0, right: 5, bottom: 40, left: 5};

    // Select container.
    const svg = d3.select(element)
        .html(null)
        .append("svg")
        .attr("viewBox", `0 0 ${width} ${height}`)
        .attr("preserveAspectRatio", "xMidYMid meet");

    const counts = options.map((x) => x.responses);
    const countsPercent = counts.map((x) => Math.round((x / responseStats.n) * 1000) / 10);

    // Axes scales.
    const x = d3.scaleLinear().domain([0.5, options.length + 0.5]).range([margin.left, width - margin.right]);
    const y = d3.scaleLinear().domain([0, responseStats.n]).range([height - margin.bottom, margin.top]);

    // Dynamic bar width calculation - uses 70% of space currently
    const bandwidth = x(2) - x(1);
    const barWidth = bandwidth * 0.7;

    svg.append("rect")
        .attr("x", margin.left)
        .attr("y", margin.top)
        .attr("width", x(options.length + 0.5) - x(0.5))
        .attr("height", y(0) - y(responseStats.n))
        .attr("fill", "#eee");

    // Plot the bars
    svg.selectAll("rect.bar")
        .data(counts)
        .enter()
        .append("rect")
        .attr("class", "bar")
        .attr("x", (d, i) => x(i + 1) - (barWidth / 2))
        .attr("y", d => y(d))
        .attr("width", barWidth)
        .attr("height", d => Math.max(0, height - margin.bottom - y(d)))
        .attr("fill", "#8EAC00");

    if (responseStats.n > 0) {

        // Get and define some values.
        const nMean = parseFloat(responseStats.mean);
        const nMedian = parseFloat(responseStats.median);
        const nStdDev = parseFloat(responseStats.stddev);
        const nCount = counts.length;
        const xMean = x(nMean);
        const xMedian = x(nMedian);
        const xStart = x(Math.max(0.5, nMean - nStdDev));
        const xEnd = x(Math.min(nCount + 0.5, nMean + nStdDev));

        const lineY = margin.top + 15;
        // Plot the standard deviation.
        if (nStdDev > 0) {

            // Main line
            svg.append("line")
                .attr("x1", xStart)
                .attr("x2", xEnd)
                .attr("y1", lineY)
                .attr("y2", lineY)
                .attr("stroke", "#000")
                .attr("stroke-width", 1.5);

            // Left Tick
            svg.append("line")
                .attr("x1", xStart).attr("x2", xStart)
                .attr("y1", lineY - 5).attr("y2", lineY + 5)
                .attr("stroke", "#000")
                .attr("stroke-width", 1.5);

            // Right Tick
            svg.append("line")
                .attr("x1", xEnd).attr("x2", xEnd)
                .attr("y1", lineY - 5).attr("y2", lineY + 5)
                .attr("stroke", "#000")
                .attr("stroke-width", 1.5);
        }

        // Mean - Red square.
        svg.append("rect")
            .attr("x", xMean - 2)
            .attr("y", lineY - 10) // Ein Stück über den Balken
            .attr("rx", 2)
            .attr("width", 4)
            .attr("height", 20)
            .attr("fill", "#a50000")
            .attr("class", "marker-mean");

        // Median - Blue triangle.
        svg.append("path")
            .attr("d", `M ${xMedian - 3} ${lineY - 10} L ${xMedian + 3} ${lineY - 10} L ${xMedian} ${lineY + 2} Z`)
            .attr("fill", "#00007a")
            .attr("class", "marker-median");

    }

    // Number and percent values beneath the x-axis.
    counts.forEach((val, i) => {
        const posX = x(i + 1);

        // Percent values.
        if (countsPercent && countsPercent[i] !== undefined) {
            svg.append("text")
                .attr("x", posX)
                .attr("y", height - 13)
                .attr("text-anchor", "middle")
                .attr("font-size", "8px")
                .attr("fill", "#555")
                .text(countsPercent[i] + "%");
        }

        // Number values.
        svg.append("text")
            .attr("x", posX)
            .attr("y", height - 2)
            .attr("text-anchor", "middle")
            .attr("font-size", "8px")
            .attr("fill", "#555")
            .text(val + "x");

    });

    // Axis.
    svg.append("g")
        .attr("transform", `translate(0,${height - margin.bottom})`)
        .call(d3.axisBottom(x).ticks(counts.length).tickFormat((i) => options[i - 1]?.tick_label || i));
};


/**
 */
export function init() {
    for (let el of document.querySelectorAll('[data-coursefeedback-chartdata]')) {
        const obj = JSON.parse(el.dataset.coursefeedbackChartdata);
        switch (obj.type) {
            case 'scalequestion':
                renderPlot(el, obj.options, obj.response_stats);
                break;
            case 'emoji':
                renderPlot(el, obj.choices.map((x) => {
                    x.tick_label = x.emoji;
                    return x;
                }), obj.response_stats);
                break;
        }
        window.console.log(obj);
    }
}