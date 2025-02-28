document.addEventListener('alpine:init', () => {
    Alpine.data('checkboxGrid', () => ({
        columnHeader: {
            ['x-on:click']() {
                let cellIndex = this.$el.cellIndex + 1;
                let cells = this.$root.querySelectorAll(
                    `tbody tr td:nth-child(${cellIndex}) > input[type="checkbox"]`,
                );

                if (Array.from(cells).some((el) => el.checked)) {
                    cells.forEach((el) => (el.checked = false));
                } else {
                    cells.forEach((el) => (el.checked = true));
                }
            },
            ['x-bind:style']() {
                return {
                    cursor: 'pointer',
                };
            },
        },
        rowHeader: {
            ['x-on:click']() {
                let rowIndex = this.$el.parentElement.sectionRowIndex + 1;
                let cells = this.$root.querySelectorAll(
                    `tbody tr:nth-child(${rowIndex}) td > input[type="checkbox"]`,
                );

                if (Array.from(cells).some((el) => el.checked)) {
                    cells.forEach((el) => (el.checked = false));
                } else {
                    cells.forEach((el) => (el.checked = true));
                }
            },
            ['x-bind:style']() {
                return {
                    cursor: 'pointer',
                };
            },
        },
    }));
});
