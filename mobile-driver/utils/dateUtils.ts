export const formatDate = (dateString: string | undefined) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${day}-${month}-${year}`;
    } catch (e) {
        return dateString;
    }
};

export const formatTime = (timeString: string | undefined) => {
    if (!timeString) return '-';
    try {
        // Handle H:i:s or full ISO string
        let hours, minutes;
        if (timeString.includes(':')) {
            const parts = timeString.split(':');
            hours = parseInt(parts[0]);
            minutes = parseInt(parts[1]);
        } else {
            const date = new Date(timeString);
            if (isNaN(date.getTime())) return timeString;
            hours = date.getHours();
            minutes = date.getMinutes();
        }

        const ampm = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = hours % 12 || 12;
        const formattedMinutes = String(minutes).padStart(2, '0');

        return `${formattedHours}:${formattedMinutes} ${ampm}`;
    } catch (e) {
        return timeString;
    }
};
