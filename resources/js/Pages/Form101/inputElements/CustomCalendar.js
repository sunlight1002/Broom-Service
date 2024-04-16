import React from "react";
import DatePicker from "react-datepicker";

import "react-datepicker/dist/react-datepicker.css";
import "./customCalendar.css";

const CustomCalendar = ({
    value,
    handleChangeDate,
    handleSelectTimeSlot,
    timeSlots = [],
}) => {
    const handleDateChange = (date) => {
        handleChangeDate(date);
    };

    const handleChangeTimeSlot = (time) => {
        handleSelectTimeSlot(time);
    };
    const dayName = new Date(value)?.toLocaleDateString("en-US", {
        month: "long",
    });
    const monthName = new Date(value).toLocaleDateString("en-US", {
        weekday: "long",
    });
    const date = new Date(value)?.getDate();
    return (
        <div className="w-75 mx-auto mt-5 row  custom-calendar">
            <div className="col-8 border">
                <h5 className="mt-3">Select a Date & Time</h5>
                <div className="d-flex gap-3 p-3">
                    <div>
                        <DatePicker
                            selected={value}
                            onChange={(date) => handleDateChange(date)}
                            autoFocus
                            shouldCloseOnSelect={false}
                            inline
                        />
                    </div>
                    <div className="mt-1 ">
                        <h6 className="time-slot-date">
                            {dayName ?? ""}, {monthName ?? ""}, {date ?? ""}
                        </h6>
                        <ul className="list-unstyled mt-4 timeslot">
                            {timeSlots && timeSlots?.length > 0 ? (
                                timeSlots.map((t, index) => {
                                    return (
                                        <li
                                            className={`py-2 px-3 border  mb-2  text-center border-primary  ${
                                                value === t
                                                    ? "bg-primary text-white"
                                                    : "text-primary"
                                            }`}
                                            key={index}
                                            onClick={() =>
                                                handleChangeTimeSlot(t)
                                            }
                                        >
                                            {t}
                                        </li>
                                    );
                                })
                            ) : (
                                <li className="py-2 px-3 border  mb-2  text-center border-secondary text-secondary bg-light ">
                                    No time slot avaiable
                                </li>
                            )}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default CustomCalendar;
