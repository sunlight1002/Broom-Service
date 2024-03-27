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

export const convertMinsToDecimalHrs = (minutes) => {
    return parseFloat(minutes / 60).toFixed(2);
};
