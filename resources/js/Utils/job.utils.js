import * as moment from "moment";
import {
    monthNames,
    monthOccurrenceArr,
    weekOccurrenceArr,
} from "./common.utils";

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

export const filterShiftOptions = (
    availableTimeRanges,
    bookedTimeRanges,
    shiftFreezeTime = {}
) => {
    // Convert the bookedTimeRanges to an array of shift start and end times
    const _bookedSlots = bookedTimeRanges.map((shift) => {
        const [start, end] = shift.split("-");
        return { start, end };
    });

    let _availSlots = [];
    availableTimeRanges.forEach((range) => {
        const _timeArray = createHourlyTimeArray(
            range.start_time.slice(0, -3),
            range.end_time.slice(0, -3)
        );

        _availSlots = _availSlots.concat(
            _timeArray.slice(0, -1).map((i, _index) => {
                return { start: i, end: _timeArray[_index + 1] };
            })
        );
    });

    // Filter out the options that are not overlapped with any selected shifts
    const nonOverlappingOptions = _availSlots.filter((option, _index) => {
        const start_time = option.start;
        const end_time = option.end;

        let isOverlapping = !_bookedSlots.some((shift) => {
            return start_time === shift.start || end_time === shift.end;
        });

        if (!isOverlapping) {
            return isOverlapping;
        }
        const _startTime = moment(start_time, "HH:mm");
        const _endTime = moment(end_time, "HH:mm");

        if (shiftFreezeTime.start && shiftFreezeTime.end) {
            const _startTimeF = moment(shiftFreezeTime["start"], "ha");
            const _endTimeF = moment(shiftFreezeTime["end"], "ha");
            return !(
                _startTimeF.isSame(_startTime) ||
                _endTimeF.isSame(_endTime) ||
                _startTimeF.isBetween(_startTime, _endTime) ||
                _endTimeF.isBetween(_startTime, _endTime) ||
                _startTime.isBetween(_startTimeF, _endTimeF) ||
                _endTime.isBetween(_startTimeF, _endTimeF)
            );
        }
        return !_bookedSlots.some((shift) => {
            const _shiftStartTime = moment(shift.start, "ha");
            const _shiftEndTime = moment(shift.end, "ha");

            return (
                _shiftStartTime.isSame(_startTime) ||
                _shiftEndTime.isSame(_endTime) ||
                _shiftStartTime.isBetween(_startTime, _endTime) ||
                _shiftEndTime.isBetween(_startTime, _endTime) ||
                _startTime.isBetween(_shiftStartTime, _shiftEndTime) ||
                _endTime.isBetween(_shiftStartTime, _shiftEndTime)
            );
        });
    });

    return nonOverlappingOptions;
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
