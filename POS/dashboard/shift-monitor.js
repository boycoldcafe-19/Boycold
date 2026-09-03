(function () {
    let knownShiftId = null;
    let knownSalesDate = null;
    let hasBaseline = false;
    let checking = false;

    async function checkShift() {
        if (checking || document.hidden) return;
        checking = true;
        try {
            const response = await fetch('shift-status.php', { credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success) return;

            const shiftId = data.shift ? String(data.shift.id) : null;
            if (hasBaseline && (shiftId !== knownShiftId || data.sales_date !== knownSalesDate)) {
                window.location.reload();
                return;
            }
            knownShiftId = shiftId;
            knownSalesDate = data.sales_date;
            hasBaseline = true;
        } catch (error) {
            console.error('Unable to refresh POS shift status:', error);
        } finally {
            checking = false;
        }
    }

    checkShift();
    setInterval(checkShift, 30000);
})();
