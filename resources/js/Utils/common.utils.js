export const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const adminToken = localStorage.getItem("admin-token");

export const weekOccurrenceArr = [
    { value: 1, label: "first" },
    { value: 2, label: "second" },
    { value: 3, label: "third" },
    { value: 4, label: "fourth" },
    { value: "last", label: "last" },
];

export const monthOccurrenceArr = [
    { value: 1, label: "first" },
    { value: 2, label: "second" },
    { value: 3, label: "third" },
    { value: 4, label: "fourth" },
    { value: 5, label: "fifth" },
    { value: 6, label: "sixth" },
    { value: 7, label: "seventh" },
    { value: 8, label: "eighth" },
    { value: 9, label: "ninth" },
    { value: 10, label: "tenth" },
    { value: 11, label: "eleventh" },
    { value: 12, label: "twelfth" },
];

export const convertMinsToDecimalHrs = (minutes) => {
    return parseFloat(minutes / 60).toFixed(2);
};

export const convertTimeSlotsToRange = (slots) => {
    const pairs = slots?.split(",").map((slot) => slot?.split("-"));
    let groupedSlots = [];
    let currentGroup = [pairs[0][0], pairs[0][1]];

    // Helper function to convert time "HH:MM" to total minutes
    const timeToMinutes = (time) => {
        const [hours, minutes] = time.split(":").map(Number);
        return hours * 60 + minutes;
    };

    // Iterate over pairs and group them based on the time difference
    pairs.forEach((pair, index) => {
        if (index > 0) {
            const previousEndTime = currentGroup[1]; // Previous group end time
            const currentStartTime = pair[0]; // Current start time

            // Check if the break is 15 minutes or less (consecutive slots)
            const timeDifference = timeToMinutes(currentStartTime) - timeToMinutes(previousEndTime);

            if (timeDifference <= 15) {
                // Extend the current group if slots are consecutive
                currentGroup[1] = pair[1];
            } else {
                // If not consecutive, push the current group and start a new one
                groupedSlots.push(currentGroup.join(" - "));
                currentGroup = [pair[0], pair[1]];
            }
        }
    });

    // Add the last group
    groupedSlots.push(currentGroup.join(" - "));
    return groupedSlots;
};


export const generateUnique15MinShifts = (shiftsArray, maxDurationInHours) => {
    const uniqueShifts = new Set(); // To avoid duplicate shifts
    let totalShiftDuration = 0; // Track total duration in hours

    shiftsArray.forEach(shift => {
        const [start, end] = shift.split("-");
        let currentHour = parseInt(start.split(":")[0], 10);
        let currentMinute = parseInt(start.split(":")[1], 10);

        // Continue generating 15-minute intervals until maxDurationInHours is reached
        while (totalShiftDuration < maxDurationInHours) {
            const startTime = `${String(currentHour).padStart(2, "0")}:${String(currentMinute).padStart(2, "0")}`;

            // Add 15 minutes to the current time
            currentMinute += 15;
            if (currentMinute === 60) {
                currentMinute = 0;
                currentHour++;
            }

            const endTime = `${String(currentHour).padStart(2, "0")}:${String(currentMinute).padStart(2, "0")}`;

            uniqueShifts.add(`${startTime}-${endTime}`);
            totalShiftDuration += 0.25; // Each 15-minute interval equals 0.25 hours

            if (totalShiftDuration >= maxDurationInHours) break; // Stop when max duration is reached
        }
    });

    return Array.from(uniqueShifts);
};

export function getShiftsDetails(job) {
    let durationInMinutes;
    let durationInHours;

    // Check if job.duration_minutes exists and is not empty or undefined
    if (job?.duration_minutes) {
        durationInMinutes = job?.jobservice?.duration_minutes
            ? job?.jobservice?.duration_minutes / 4
            : job?.duration_minutes / 4;
        durationInHours = durationInMinutes / 60;
    } else {
        durationInHours = 0;
    }

    const shiftsArray = job?.shifts ? job?.shifts?.split(",") : [];
    if (durationInHours == 0) {
        durationInHours = shiftsArray?.length / 4;
    }
    const allShifts = generateUnique15MinShifts(shiftsArray, durationInHours);
    // console.log(allShifts, "all");

    const startTime = allShifts?.length > 0 ? allShifts[0]?.split("-")[0] : "";
    const endTime = allShifts?.length > 0 ? allShifts[allShifts.length - 1].split("-")[1] : "";

    return {
        durationInHours,
        startTime,
        endTime
    };
}


// export function sendDirect(job) {

//     const shiftsArray = job?.shifts ? job?.shifts?.split(",") : [];
//     console.log(shiftsArray);

//     // const allShifts = generateUnique15MinShifts(shiftsArray, durationInHours);

//     // return {
//     //     durationInHours,
//     //     startTime,
//     //     endTime
//     // };
// }


// Function to convert JSON object to FormData
export const objectToFormData = (obj, formData, namespace) => {
    const fd = formData || new FormData();
    let formKey;

    for (const property in obj) {
        if (obj.hasOwnProperty(property)) {
            if (namespace) {
                formKey = namespace + "[" + property + "]";
            } else {
                formKey = property;
            }

            if (
                typeof obj[property] === "object" &&
                !(obj[property] instanceof File)
            ) {
                objectToFormData(obj[property], fd, formKey);
            } else {
                fd.append(formKey, obj[property]);
            }
        }
    }

    return fd;
};

export const workerHours = (s, msg) => {
    if (adminToken) {
        return `${s.workers.map((i) => i.jobHours).join(", ")} ${msg}`;
    }else{
        if (s.type === "hourly") {
            return `${s.workers.map((i) => i.jobHours).join(", ")} ${msg}`;
        }
    }
    return "--";
};
