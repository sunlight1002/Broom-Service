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

export const shiftOptions = {
    "8am-16pm": [
        { value: 1, label: "8am-9am" },
        { value: 2, label: "9am-10am" },
        { value: 3, label: "10am-11am" },
        { value: 4, label: "11am-12pm" },
        { value: 6, label: "12pm-13pm" },
        { value: 7, label: "13pm-14pm" },
        { value: 8, label: "14pm-15pm" },
        { value: 9, label: "15pm-16pm" },
    ],
    "8am-12pm": [
        { value: 1, label: "8am-9am" },
        { value: 2, label: "9am-10am" },
        { value: 3, label: "10am-11am" },
        { value: 4, label: "11am-12pm" },
    ],
    "12pm-16pm": [
        { value: 0, label: "12pm-13pm" },
        { value: 1, label: "13pm-14pm" },
        { value: 2, label: "14pm-15pm" },
        { value: 3, label: "15pm-16pm" },
    ],
    "16pm-20pm": [
        { value: 0, label: "16pm-17pm" },
        { value: 1, label: "17pm-18pm" },
        { value: 2, label: "18pm-19pm" },
        { value: 3, label: "19pm-20pm" },
    ],
    "20pm-24am": [
        { value: 0, label: "20pm-21pm" },
        { value: 1, label: "21pm-22pm" },
        { value: 2, label: "22pm-23pm" },
        { value: 3, label: "23pm-24am" },
    ],
};

export const convertMinsToDecimalHrs = (minutes) => {
    return parseFloat(minutes / 60).toFixed(2);
};
