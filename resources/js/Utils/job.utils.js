import * as moment from "moment";
import {
    monthNames,
    monthOccurrenceArr,
    weekOccurrenceArr,
} from "./common.utils";
import Swal from "sweetalert2";

export const frequencyDescription = (_service) => {
    let descriptionStr = "";
    if (_service.period == "w" && _service.cycle == 1) {
        descriptionStr += `On ${_service.weekday}`;
    }

    if (["m", "2m", "3m", "6m", "y"].includes(_service.period)) {
        if (_service.monthday_selection_type == "date") {
            descriptionStr += `Day ${_service.month_date}`;
        }
    }

    if (
        ["2w", "3w", "4w", "5w", "m", "2m", "3m", "6m", "y"].includes(
            _service.period
        )
    ) {
        if (_service.monthday_selection_type == "weekday") {
            const _occurence = weekOccurrenceArr.find(
                (i) => i.value == _service.weekday_occurrence
            );
            descriptionStr += `The ${_occurence.label} ${_service.weekday}`;
        }
    }

    if (["2m", "3m", "6m"].includes(_service.period)) {
        const _occurence = monthOccurrenceArr.find(
            (i) => i.value == _service.month_occurrence
        );
        descriptionStr += ` of ${_occurence.label} month`;
    } else if (_service.period == "y") {
        descriptionStr += ` of ${
            monthNames[parseInt(_service.month_occurrence) - 1]
        } month`;
    }

    if (_service.period == "w" && _service.cycle > 1) {
        descriptionStr += `${_service.weekdays}`;
    }

    return descriptionStr;
};

export const createHourlyTimeArray = (startTime, endTime) => {
    const timeArray = [];
    const startHour = parseInt(startTime.split(":")[0]);
    const endHour = parseInt(endTime.split(":")[0]);

    for (let hour = startHour; hour <= endHour; hour++) {
        const timeString = hour.toString().padStart(2, "0") + ":00";
        timeArray.push(timeString);
    }

    return timeArray;
};

export const generateHourlyTimeSlots = (startTime, endTime) => {
    let slots = [];
    let start = moment(startTime, "HH:mm:ss");
    let end = moment(endTime, "HH:mm:ss");

    while (start < end) {
        slots.push(start.format("HH:mm:ss"));
        start.add(1, "hours");
    }

    return slots;
};

export const convertShiftsFormat = (shiftsArray) => {
    const convertedShifts = [];

    // Create an object to store shifts for each worker and date
    const shiftsMap = {};

    // Iterate over the original array and aggregate shifts
    shiftsArray.forEach((shift) => {
        const key = `${shift.workerName}_${shift.workerId}_${shift.date}`;

        // If the key doesn't exist in the map, initialize it
        if (!shiftsMap[key]) {
            shiftsMap[key] = [];
        }

        // Push the shift time to the array
        shiftsMap[key].push(shift.time.time);
    });

    // Convert the aggregated shifts to the desired format
    for (const key in shiftsMap) {
        const [workerName, workerId, date] = key.split("_");
        const shifts = shiftsMap[key]
            .map((time, index) => {
                const startTime = time;
                return `${moment(startTime, "HH:mm:ss").format(
                    "HH:mm"
                )}-${moment(startTime, "HH:mm:ss")
                    .add(1, "hour")
                    .format("HH:mm")}`;
            })
            .join(",");

        convertedShifts.push({
            worker_id: workerId,
            worker_name: workerName,
            date: date,
            shifts: shifts,
        });
    }

    return convertedShifts;
};

export const getAvailableSlots = async (
    workerAvailabilities,
    w_id,
    date,
    shift,
    workHours,
    isClient = false,
    alert,
) => {
    const chosenDateMoment = moment(date, "YYYY-MM-DD");
    const chosenStartTimeMoment = moment(shift.time, "HH:mm:ss");
    let workerName = "";
    // Find the worker's slots for the chosen start date
    const workerSlots = workerAvailabilities.find((worker) => {
        return (
            worker.workerId === w_id &&
            worker.slots.some((slot) =>
                moment(slot.date, "YYYY-MM-DD").isSame(chosenDateMoment, "day")
            )
        );
    });

    if (!workerSlots) {
        alert.error("Worker is not available on the chosen start date");
        return [];
    }
    workerName = workerSlots.workerName;

    // Find the slots for the chosen start time
    const chosenDateSlots = workerSlots.slots.find((slot) =>
        moment(slot.date, "YYYY-MM-DD").isSame(chosenDateMoment, "day")
    );
    const startIndex = chosenDateSlots.slots.findIndex((slot) =>
        moment(slot.time, "HH:mm:ss").isSame(chosenStartTimeMoment, "minute")
    );

    if (startIndex === -1) {
        alert.error("Chosen start time is not available");
        return [];
    }

    // Get the available slots based on work hours
    const availableSlots = [];
    let remainingHours = parseInt(workHours);
    for (
        let i = startIndex;
        i < chosenDateSlots.slots.length && remainingHours > 0;
        i++
    ) {
        availableSlots.push({
            workerName: workerName,
            workerId: w_id,
            date: chosenDateSlots.date,
            time: chosenDateSlots.slots[i],
        });
        remainingHours--;
    }

    if(isClient && remainingHours > 0) {
        alert.error("Not enough available slots for the chosen work hours");
        return [];
    }

    if (remainingHours > 0) {
        const alert = await Swal.fire({
            title: "Are you sure?",
            text: "Not enough available slots for the chosen work hours",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, do it!",
        });
        if (alert.isConfirmed) {
            try {
                const lastSlot =
                    chosenDateSlots.allSlots[
                        chosenDateSlots.allSlots.length - 1
                    ];
                if (lastSlot) {
                    const startTimeMoment = moment(lastSlot.time, "HH:mm:ss");
                    for (let i = 0; i < remainingHours; i++) {
                        const slotTime = startTimeMoment.add(1, "hours");
                        availableSlots.push({
                            workerName: workerName,
                            workerId: w_id,
                            date: chosenDateSlots.date,
                            time: { time: slotTime.format("HH:mm:ss") },
                        });
                    }
                }
            } catch (error) {
                return [];
            }
        }
    }

    return availableSlots;
};

export const getWorkerAvailabilities = (workers) => {
    return workers?.map((worker) => {
        const shiftFreezeTime = {
            start: worker.freeze_shift_start_time,
            end: worker.freeze_shift_end_time,
        };
        const booked_slots = worker.booked_slots ?? [];

        const notAvailableDates = worker.not_available_on;
        const availabilityArray = Object.entries(worker?.availabilities);
        let slots = availabilityArray?.map(([key, value]) => {
            let slots = filterShiftOptions(
                value ?? [],
                booked_slots[key] ?? [],
                shiftFreezeTime,
                notAvailableDates?.find((n) => n.date == key)
            );

            return {
                date: key,
                allSlots: slots,
                slots: slots
                    .filter(
                        (slot) =>
                            !slot?.isBooked &&
                            !slot?.isFreezed &&
                            !slot?.notAvailable
                    )
                    .map((slot) => {
                        return { time: slot.time };
                    }),
            };
        });
        return {
            workerId: worker.id,
            workerName: worker.firstname + " " + worker.lastname,
            slots: slots,
        };
    });
};

const parseTimeSlots = (slots) => {
    // Split the string into pairs
    const pairs = slots.split(",").map((slot) => slot.split("-"));

    // This will store the final groups of time slots
    let groupedSlots = [];
    let currentGroup = [pairs[0][0]]; // Initialize with the start of the first slot

    // Iterate over the pairs to group them
    pairs.forEach((pair, index) => {
        if (index > 0) {
            // Check if the current pair's start time is the same as the last pair's end time
            if (pair[0] === pairs[index - 1][1]) {
                // Continue the current group
                currentGroup[1] = pair[1];
            } else {
                // Finish the current group and start a new one
                groupedSlots.push(currentGroup.join(" - "));
                currentGroup = [pair[0], pair[1]];
            }
        }
    });

    // Add the last group
    groupedSlots.push(currentGroup.join(" - "));

    return groupedSlots;
};

export const getWorkersData = (workers) => {
    const data = [];
    workers.forEach((worker, index) => {
        worker?.formattedSlots?.forEach((slots) => {
            let shifts = parseTimeSlots(slots?.shifts ?? "");
            data.push({
                worker_name: slots?.worker_name ?? "",
                date: slots?.date ?? "",
                shifts: shifts.join(", ") ?? "",
            });
        });
    });
    return data;
};

export const filterShiftOptions = (
    availableTimeRanges,
    bookedTimeRanges,
    shiftFreezeTime = {},
    notAvailableDates = {},
    selectedHours = [],
    workerId = null,
    date = null,
) => {
    let _availSlots = [];
    availableTimeRanges.forEach((range) => {
        _availSlots = _availSlots.concat(
            generateHourlyTimeSlots(range.start_time, range.end_time)
        );
    });

    if(selectedHours.length > 0) {
        selectedHours.forEach((worker) => {
            if(worker.slots && worker.slots.length > 0) {
                worker.slots.forEach((slot) => {
                    if (!_availSlots.includes(slot.time.time) && workerId == slot.workerId && date == slot.date) {
                        _availSlots.push(slot.time.time);
                    }
                });
            }
        });
    }

    let _bookedSlots = bookedTimeRanges.map((range) => {
        const [start, end] = range.slot.split("-");
        const _slots = generateHourlyTimeSlots(start + ":00", end + ":00");
        return {
            client_name: range.client_name,
            slots: _slots,
        };
    });

    let _freezeSlots = [];
    if (shiftFreezeTime?.start && shiftFreezeTime?.end) {
        _freezeSlots = generateHourlyTimeSlots(
            shiftFreezeTime?.start,
            shiftFreezeTime?.end
        );
    }

    let _notAvailableSlots = [];
    if (notAvailableDates?.date) {
        if (notAvailableDates?.start_time && notAvailableDates?.end_time) {
            _notAvailableSlots = generateHourlyTimeSlots(
                notAvailableDates?.start_time,
                notAvailableDates?.end_time
            );
        } else {
            _notAvailableSlots = _availSlots;
        }
    }

    return _availSlots.map((slot) => {
        const bookedSlots = _bookedSlots.find((bookedSlot) => {
            return bookedSlot?.slots?.includes(slot);
        });

        return {
            time: slot,
            isBooked: bookedSlots ? true : false,
            clientName: bookedSlots?.client_name ?? null,
            isFreezed: _freezeSlots?.includes(slot),
            notAvailable: _notAvailableSlots?.includes(slot),
        };
    });
};

export const createHalfHourlyTimeArray = (startTime, endTime) => {
    const [startHour, startMinute] = startTime.split(":").map(Number);
    const [endHour, endMinute] = endTime.split(":").map(Number);
    let times = [];

    let currentHour = startHour;
    let currentMinute = startMinute;

    while (
        currentHour < endHour ||
        (currentHour === endHour && currentMinute <= endMinute)
    ) {
        let hourString =
            currentHour < 10 ? "0" + currentHour : currentHour.toString();
        let minuteString =
            currentMinute < 10 ? "0" + currentMinute : currentMinute.toString();
        times.push(hourString + ":" + minuteString);

        currentMinute += 30;
        if (currentMinute >= 60) {
            currentMinute -= 60;
            currentHour++;
        }
    }

    return times;
};
