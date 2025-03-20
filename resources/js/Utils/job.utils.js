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

export const generate15MinutesTimeSlots = (startTime, endTime, workerId = null) => {
    let slots = [];
    
    let start = moment(startTime, "HH:mm:ss");
    let end = moment(endTime, "HH:mm:ss");

    while (start < end) {
        slots.push(start.format("HH:mm:ss"));
        start.add(15, "minutes");
    }
    
    return slots;
};

export const convertShiftsFormat = (shiftsArray) => {
    const convertedShifts = [];

    // Create an object to store shifts for each worker and date
    const shiftsMap = {};

    // Iterate over the original array and aggregate shifts
    shiftsArray.forEach((shift) => {
        const key = `${shift.workerName}_${shift.workerId}_${shift.date}_${
            shift.jobId ?? 0
        }`;

        if (!shiftsMap[key]) {
            shiftsMap[key] = [];
        }

        // Push both start time and end time to the array
        shiftsMap[key].push({
            startTime: shift.time.time, // Assuming shift.time.time is the start time
            endTime: shift.time.endTime // Assuming shift.endTime is the end time
        });

    });

    // Convert the aggregated shifts to the desired format
    for (const key in shiftsMap) {
        const [workerName, workerId, date, jobId] = key.split("_");
        const shifts = shiftsMap[key];

        // Get the start time of the first shift
        const firstShiftStartTime = shifts[0].startTime;
        const startTime = moment(firstShiftStartTime, "HH:mm:ss").isValid()
            ? moment(firstShiftStartTime, "HH:mm:ss").format("HH:mm")
            : "Invalid time";

        // Get the end time of the last shift directly from the endTime property
        const lastShiftEndTime = shifts[shifts.length - 1].endTime;
        const endTime = moment(lastShiftEndTime, "HH:mm:ss").isValid()
            ? moment(lastShiftEndTime, "HH:mm:ss").format("HH:mm")
            : "Invalid time";

        // Combine start and end time
        const shiftTime = `${startTime}-${endTime}`;

        // Push the object with the worker's details and the calculated shift time
        convertedShifts.push({
            worker_id: workerId,
            worker_name: workerName,
            date: date,
            job_id: jobId,
            shifts: shiftTime, // Correct shift format using endTime from the slot
        });
    }

    return convertedShifts;
};

export const adjustSchedule = (overlapSlots, slots) => {
    let tmpSlots = [...slots];
    // Convert time to an integer representing minutes since start of the day for easy comparison and manipulation
    overlapSlots.forEach((overlap) => {
        slots.forEach((_s, index) => {
            const overlapTime = moment(overlap.time, "HH:mm:ss");
            const slotTime = moment(_s.time, "HH:mm:ss");
            if (overlapTime.isSameOrBefore(slotTime)) {
                if (overlap.time == _s.time) {
                    tmpSlots[index] = {
                        ...tmpSlots[index],
                        clientName: null,
                        isBooked: false,
                        isFreezed: false,
                        jobId: null,
                        notAvailable: false,
                    };
                }
                if (
                    typeof tmpSlots[index + overlapSlots.length] !== "undefined"
                ) {
                    tmpSlots[index + overlapSlots.length] = {
                        ..._s,
                        time: tmpSlots[index + overlapSlots.length].time,
                    };
                }
                if (!_s.isBooked && !_s.isFreezed && !_s.notAvailable) {
                    return true;
                }
            }
        });
    });
    return tmpSlots;
};



export const getAvailableSlots = async (
    workerAvailabilities,
    w_id,
    date,
    shift,
    workHours,
    isClient = false,
    alert,
    setWorkerAvailabilities,
    setUpdatedJobs
) => {
    const chosenDateMoment = moment(date, "YYYY-MM-DD");
    const chosenStartTimeMoment = moment(shift.time, "HH:mm:ss");
    const chosenEndTimeMoment = moment(shift.endTime, "HH:mm:ss");
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

    const startIndex = chosenDateSlots.allSlots.findIndex((slot) =>
        moment(slot.time, "HH:mm:ss").isSame(chosenStartTimeMoment, "minute")
    );

    if (startIndex === -1) {
        alert.error("Chosen start time is not available");
        return [];
    }

    // Get the available slots based on 15-minute intervals
    let availableSlots = [];
    let status = null;

    let fullHours = Math.floor(workHours); 
    let fractionalPart = workHours - fullHours; // Get the fractional part
    // Convert the full hours to 15-minute slots (4 slots per hour)
    let remainingSlots = fullHours * 4;
    
    // Handle fractional part:
    if (fractionalPart > 0 && fractionalPart <= 0.15) {
        remainingSlots += 1; // Add 1 slot (15 minutes)
    } else if (fractionalPart > 0.15 && fractionalPart <= 0.50) {
        remainingSlots += 2; // Add 2 slots (30 minutes)
    } else if (fractionalPart > 0.50 && fractionalPart <= 0.99) {
        remainingSlots += 3; // Add 3 slots (45 minutes)
    }


    for (let i = startIndex; i < chosenDateSlots.allSlots.length && remainingSlots > 0; i++) {
        const currentSlot = chosenDateSlots.allSlots[i];

        if (currentSlot.isBooked) {
            status = "booked";
            break;
        } else if (currentSlot.isFreezed) {
            status = "freezed";
            break;
        } else if (currentSlot.notAvailable) {
            status = "unavailable";
            break;
        }

        if (
            !currentSlot.isBooked &&
            (((!currentSlot.isFreezed || currentSlot.isFreezed) && !isClient) ||
            (!currentSlot.isFreezed && isClient)) &&
            !currentSlot.notAvailable
        ) {
            availableSlots.push({
                workerName: workerName,
                workerId: w_id,
                date: chosenDateSlots.date,
                time: currentSlot,
            });
            remainingSlots--;
        }
    }

    if (isClient && remainingSlots > 0) {
        alert.error("Not enough available slots for the chosen work hours");
        return [];
    }

    if (remainingSlots > 0) {
        let message = "";
        if (status != null) {
            switch (status) {
                case "booked":
                    message = "Some slots overlap with other bookings. Do you want to move the next booking ahead?";
                    break;
                case "freezed":
                    message = "Some slots overlap with the frozen time. Do you want to move the frozen time ahead?";
                    break;
                case "unavailable":
                    message = "Some slots overlap with the unavailable time. Do you want to move the unavailable time ahead?";
                    break;
            }

            const confirmAlert = await Swal.fire({
                title: "Are you sure?",
                text: message,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, do it!",
            });

            if (confirmAlert.isConfirmed) {
                try {
                    availableSlots = [];
                    // remainingSlots = parseInt(workHours) * 4;
                    let overlapSlots = [];

                    for (let i = startIndex; i < chosenDateSlots.allSlots.length && remainingSlots > 0; i++) {
                        availableSlots.push({
                            workerName: workerName,
                            workerId: w_id,
                            date: chosenDateSlots.date,
                            time: chosenDateSlots.allSlots[i],
                        });

                        if (
                            chosenDateSlots.allSlots[i].isBooked ||
                            chosenDateSlots.allSlots[i].isFreezed ||
                            chosenDateSlots.allSlots[i].notAvailable
                        ) {
                            overlapSlots.push(chosenDateSlots.allSlots[i]);
                        }
                        remainingSlots--;
                    }

                    if (overlapSlots.length > 0) {
                        setWorkerAvailabilities(
                            workerAvailabilities.map((worker) => {
                                if (worker.workerId == w_id) {
                                    let slots = worker.slots.map((s) => {
                                        if (s.date == date) {
                                            let _allSlots = adjustSchedule(overlapSlots, s.allSlots);
                                            let shifts = [];
                                            _allSlots.forEach((slot) => {
                                                if (slot.jobId != null) {
                                                    shifts.push({
                                                        workerId: w_id,
                                                        workerName: worker.workerName,
                                                        date: date,
                                                        jobId: slot.jobId,
                                                        time: {
                                                            time: slot.time,
                                                        },
                                                    });
                                                }
                                            });
                                            setUpdatedJobs(shifts.length > 0 ? convertShiftsFormat(shifts) : null);
                                            return {
                                                ...s,
                                                slots: adjustSchedule(overlapSlots, s.slots),
                                                allSlots: _allSlots,
                                            };
                                        }
                                        return s;
                                    });
                                    return { ...worker, slots };
                                }
                                return worker;
                            })
                        );
                    }

                    return availableSlots;
                } catch (error) {
                    return [];
                }
            } else {
                return [];
            }
        }
        // Handle not enough available slots for the chosen work hours
        const confirmAlert = await Swal.fire({
            title: "Are you sure?",
            text: "Not enough available slots for the chosen work hours",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, do it!",
        });

        if (confirmAlert.isConfirmed) {
            try {
                const lastSlot = chosenDateSlots.allSlots[chosenDateSlots.allSlots.length - 1];
                if (lastSlot) {
                    const startTimeMoment = moment(lastSlot.time, "HH:mm:ss");
                    const endTimeMoment = moment(lastSlot.time, "HH:mm:ss");

                    for (let i = 0; i < remainingSlots; i++) {
                        const startTime = startTimeMoment.add(15, "minutes"); // Add 15 minutes per slot
                        const endTime = endTimeMoment.add(15, "minutes"); // Add 15 minutes per slot
                        availableSlots.push({
                            workerName: workerName,
                            workerId: w_id,
                            date: chosenDateSlots.date,
                            time: { 
                                time: startTime.format("HH:mm:ss"), 
                                endTime: endTime.format("HH:mm:ss") 
                            },
                        });
                        setWorkerAvailabilities(
                            workerAvailabilities.map((worker) => {
                                if (worker.workerId == w_id) {
                                    let slots = worker.slots.map((s) => {
                                        if (s.date == chosenDateSlots.date) {
                                            s.slots.push({
                                                time: startTime.format("HH:mm:ss"),
                                                endTime: endTime.format("HH:mm:ss"),
                                                clientName: null,
                                                isBooked: false,
                                                isFreezed: false,
                                                jobId: null,
                                                notAvailable: false,
                                            });
                                            s.allSlots.push({
                                                time: startTime.format("HH:mm:ss"),
                                                endTime: endTime.format("HH:mm:ss"),
                                                clientName: null,
                                                isBooked: false,
                                                isFreezed: false,
                                                jobId: null,
                                                notAvailable: false,
                                            });

                                            return {
                                                ...s,
                                                slots: s.slots,
                                                allSlots: s.allSlots,
                                            };
                                        }
                                        return s;
                                    });
                                    return { ...worker, slots };
                                }
                                return worker;
                            })
                        );
                    }
                }
            } catch (error) {
                return [];
            }
        } else {
            return [];
        }
    }

    // Calculate end time based on available slots
    let endTime;
    if (availableSlots.length > 0) {
        const lastSlot = availableSlots[availableSlots.length - 1].time;
        endTime = moment(lastSlot.time, "HH:mm:ss").add(workHours, 'hours').format("HH:mm:ss");
    }

    return availableSlots; // Return both available slots and end time
};

// const handleSlotAdjustment = async (workerAvailabilities, chosenDateSlots, availableSlots, status, remainingSlots, w_id, date, setWorkerAvailabilities, setUpdatedJobs) => {
//     availableSlots.length = 0; // Clear available slots
//     remainingSlots = parseInt(remainingSlots) * 4;

//     const overlapSlots = [];

//     for (let i = 0; i < chosenDateSlots.allSlots.length && remainingSlots > 0; i++) {
//         const currentSlot = chosenDateSlots.allSlots[i];

//         availableSlots.push({
//             workerName: workerSlots.workerName,
//             workerId: w_id,
//             date: chosenDateSlots.date,
//             time: currentSlot,
//         });

//         if (currentSlot.isBooked || currentSlot.isFreezed || currentSlot.notAvailable) {
//             overlapSlots.push(currentSlot);
//         }
//         remainingSlots--;
//     }

//     if (overlapSlots.length > 0) {
//         setWorkerAvailabilities(prev => prev.map(worker => {
//             if (worker.workerId == w_id) {
//                 return updateWorkerSlots(worker, date, overlapSlots, setUpdatedJobs);
//             }
//             return worker;
//         }));
//     }

//     return availableSlots;
// };

// const updateWorkerSlots = (worker, date, overlapSlots, setUpdatedJobs) => {
//     const updatedSlots = worker.slots.map(slot => {
//         if (slot.date == date) {
//             const adjustedSlots = adjustSchedule(overlapSlots, slot.allSlots);
//             const shifts = adjustedSlots
//                 .filter(slot => slot.jobId != null)
//                 .map(slot => ({
//                     workerId: worker.workerId,
//                     workerName: worker.workerName,
//                     date: date,
//                     jobId: slot.jobId,
//                     time: { time: slot.time },
//                 }));

//             setUpdatedJobs(shifts.length > 0 ? convertShiftsFormat(shifts) : null);
//             return { ...slot, slots: adjustedSlots, allSlots: adjustedSlots };
//         }
//         return slot;
//     });

//     return { ...worker, slots: updatedSlots };
// };





// Function to convert time string to minutes
export const timeToMinutes = (time) => {
    const [hours, minutes, seconds] = time.split(":").map(Number);
    return hours * 60 + minutes;
};

export const findContinuousTimeSlots = (slots, requiredHours) => {
    const requiredMinutes = requiredHours * 60; // Convert required hours to minutes
    const slotsInMinutes = slots.map((slot) => ({
        ...slot,
        minutes: timeToMinutes(slot.time), // Assuming timeToMinutes is defined elsewhere
    }));

    let continuousCount = 0;
    let newSlots = [];

    for (let i = 0; i < slotsInMinutes.length; i++) {
        // Check if the slot is available
        if (
            !slotsInMinutes[i].isBooked &&
            !slotsInMinutes[i].isFreezed &&
            !slotsInMinutes[i].notAvailable
        ) {
            continuousCount++;
        } else {
            // If we hit a booked/freezed/not available slot, reset the count
            continuousCount = 0;
        }

        // If we have found enough continuous slots
        if (continuousCount * 15 >= requiredMinutes) {
            // Push the starting time of the continuous slots
            const startSlot = slotsInMinutes[i - continuousCount + 1];
            newSlots.push(startSlot);
        }
    }

    // Ensure unique slots based on their time
    const uniqueSlots = Array.from(new Set(newSlots.map(slot => slot.time)))
        .map(time => newSlots.find(slot => slot.time === time));

    return uniqueSlots;
};



export const getWorkerAvailabilities = (
    workers,
    isClient = false,
    jobHours = undefined
) => {
    const _today = moment().format("YYYY-MM-DD");
    const _currentTime = moment().format("HH:mm:ss");

    // const splitInto15MinuteSlots = (slot) => {
        
    //     const slotStart = moment(slot.time, "HH:mm:ss");
        
    //     const slotEnd = slotStart.clone().add(1, "hour");

    //     const slots = [];
    //     for (let time = slotStart.clone(); time.isBefore(slotEnd); time.add(15, "minutes")) {
    //         slots.push({
    //             time: time.format("HH:mm:ss"),
    //             endTime: time.clone().add(15, "minutes").format("HH:mm:ss"),
    //             isBooked: slot.isBooked,
    //             isFreezed: slot.isFreezed,
    //             notAvailable: slot.notAvailable,
    //             clientName: slot.clientName,
    //             jobId: slot.jobId,
    //         });
    //     }
    //     return slots;
    // };


    return workers?.map((worker) => {
        const freeze_dates = worker.freeze_dates ?? [];
        const booked_slots = worker.booked_slots ?? [];
        const notAvailableDates = worker.not_available_on;

        return {
            workerId: worker.id,
            workerName: `${worker.firstname} ${worker.lastname}`,
            slots: Object.entries(worker?.availabilities)?.map(([date, value]) => {
                const dailyBookedSlots = booked_slots[date] ?? [];
                const dailyFreezeDates = freeze_dates.filter(f => f.date === date);
                const notAvailableDate = notAvailableDates?.find(n => n.date === date);
                
                let slots = filterShiftOptions(value ?? [], dailyBookedSlots, dailyFreezeDates, notAvailableDate);

                if (date === _today) {
                    slots = slots.filter(slot => slot.time > _currentTime);
                    
                }
                
                // const allSlots = slots.flatMap(splitInto15MinuteSlots);
                const allSlots = slots;

                // Remove duplicates and ensure booked slots appear only once
                const uniqueSlots = allSlots.reduce((acc, curr) => {
                    const existing = acc.find(slot => slot.time === curr.time);
                    if (!existing) {
                        acc.push(curr);
                    } else if (curr.isBooked) {
                        existing.isBooked = true;
                        existing.clientName = curr.clientName;
                        existing.jobId = curr.jobId;
                    }
                    return acc;
                }, []);

                const filteredSlots = isClient 
                    ? findContinuousTimeSlots(uniqueSlots, jobHours) 
                    : uniqueSlots.filter(slot => !slot.isBooked && (!slot.isFreezed || !isClient) && !slot.notAvailable);

                return {
                    date,
                    allSlots: uniqueSlots, // All unique slots
                    slots: filteredSlots, // Filtered slots
                };
            }),
        };
    });
};



export const parseTimeSlots = (slots) => {

    const pairs = slots.split(",").map((slot) => slot.split("-"));
    let groupedSlots = [];
    let currentGroup = [pairs[0][0]];

    // Helper function to convert time "HH:MM" to total minutes
    const timeToMinutes = (time) => {
        const [hours, minutes] = time.split(":").map(Number);
        return hours * 60 + minutes;
    };

    // Iterate over pairs and group them based on the time difference
    pairs.forEach((pair, index) => {
        if (index > 0) {
            const previousEndTime = pairs[index - 1][1];
            const currentStartTime = pair[0];

            // Check if the break is 15 minutes or more
            const timeDifference = timeToMinutes(currentStartTime) - timeToMinutes(previousEndTime);

            if (timeDifference <= 15) {
                // Continue the current group
                currentGroup[1] = pair[1];
            } else {
                // Break found, finish the current group and start a new one
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
            // let shifts = parseTimeSlots(slots?.shifts ?? "");
            data.push({
                workerId: slots.worker_id,
                worker_name: slots?.worker_name ?? "",
                date: slots?.date ?? "",
                shifts: slots?.shifts,
            });
        });
    });
    return data;
};

export const filterShiftOptions = (
    availableTimeRanges,
    bookedTimeRanges,
    shiftFreezeDates = [],
    notAvailableDates = {},
    selectedHours = [],
    workerId = null,
    date = null
) => {
    const _availSlots = new Set();
    // Generate available slots
    availableTimeRanges.forEach((range) => {
        generate15MinutesTimeSlots(range.start_time, range.end_time, workerId).forEach(slot => _availSlots.add(slot));
    });

    // Add selected hours to available slots
    if (selectedHours.length > 0) {
        selectedHours.forEach((worker) => {
            if (worker.slots) {
                worker.slots.forEach((slot) => {
                    if (workerId === slot.workerId && date === slot.date) {
                        _availSlots.add(slot.time.time);
                    }
                });
            }
        });
    }
    
    // Generate booked slots and update available slots
    const _bookedSlots = bookedTimeRanges.map((range) => {
        const [start, end] = range.slot.split("-");
        const slots = generate15MinutesTimeSlots(`${start}:00`, `${end}:00`, "booked");
        
        
        slots.forEach((s) => _availSlots.add(s));
        return {
            client_name: range.client_name,
            job_id: range.job_id,
            slots,
        };
    });

    

    // Generate frozen slots
    const _freezeSlots = new Set();
    shiftFreezeDates.forEach((date) => {
        generate15MinutesTimeSlots(date.start_time, date.end_time).forEach(slot => _freezeSlots.add(slot));
    });

    // Generate not available slots
    let _notAvailableSlots = new Set();
    if (notAvailableDates.date) {
        if (notAvailableDates.start_time && notAvailableDates.end_time) {
            _notAvailableSlots = new Set(generate15MinutesTimeSlots(notAvailableDates.start_time, notAvailableDates.end_time));
        } else {
            _notAvailableSlots = new Set(_availSlots);
        }
    }

    // Map the available slots to include additional info
    return Array.from(_availSlots).map((slot, index, array) => {
        const bookedSlots = _bookedSlots?.find((bookedSlot) => bookedSlot?.slots?.includes(slot));
        const nextSlot = array[index + 1] || moment(slot, "HH:mm:ss").add(15, "minutes").format("HH:mm:ss");
    
        return {
            time: slot,
            endTime: nextSlot, // Set end time 15 minutes after start time
            isBooked: !!bookedSlots,
            clientName: bookedSlots?.client_name ?? null,
            jobId: bookedSlots?.job_id ?? null,
            isFreezed: _freezeSlots.has(slot),
            notAvailable: _notAvailableSlots.has(slot),
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
