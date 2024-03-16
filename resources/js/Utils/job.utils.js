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

export const filterShiftOptions = (options, selectedShifts) => {
    const shifts = selectedShifts;

    // Convert the selectedShifts to an array of shift start and end times
    const shiftTimes = shifts.map((shift) => {
        const [_, start, end] = shift.match(/(\d{1,2}[ap]m)-(\d{1,2}[ap]m)/);
        return { start, end };
    });

    const isFullDay = shiftTimes.some((shift) => {
        return shift.start === "8am" && shift.end === "16pm";
    });

    if (isFullDay) {
        return [];
    }

    // Filter out the options that are not overlapped with any selected shifts
    const nonOverlappingOptions = options.filter((option) => {
        const [_, start, end] = option.label.match(
            /(\d{1,2}[ap]m)-(\d{1,2}[ap]m)/
        );

        let isOverlapping = !shiftTimes.some((shift) => {
            return start === shift.start || end === shift.end;
        });

        if (!isOverlapping) {
            return isOverlapping;
        }

        const _startTime = moment(start, "ha");
        const _endTime = moment(end, "ha");

        return !shiftTimes.some((shift) => {
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
