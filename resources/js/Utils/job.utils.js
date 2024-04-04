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
