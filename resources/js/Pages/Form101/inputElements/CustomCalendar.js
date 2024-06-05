import React, { useEffect, useState, useMemo } from "react";
import DatePicker from "react-datepicker";
import moment from "moment";
import Swal from "sweetalert2";
import { useAlert } from "react-alert";

import "react-datepicker/dist/react-datepicker.css";
import "./customCalendar.css";
import { createHalfHourlyTimeArray } from "../../../Utils/job.utils";

const CustomCalendar = ({ meeting }) => {
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [selectedTime, setSelectedTime] = useState(null);
    const [availableSlots, setAvailableSlots] = useState([]);
    const [bookedSlots, setBookedSlots] = useState([]);
    const [isLoading, setIsLoading] = useState(false);

    const alert = useAlert();

    const getTeamAvailibality = (date) => {
        const _date = moment(date).format("Y-MM-DD");

        axios
            .get(`/api/teams/availability/${meeting.team_id}/date/${_date}`)
            .then((response) => {
                setAvailableSlots(
                    response.data.available_slots.map((i) => {
                        return {
                            start_time: i.start_time.slice(0, -3),
                            end_time: i.end_time.slice(0, -3),
                        };
                    })
                );
                setBookedSlots(response.data.booked_slots);
            })
            .catch((e) => {
                setAvailableSlots([]);
                setBookedSlots([]);

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const timeOptions = useMemo(() => {
        return createHalfHourlyTimeArray("08:00", "24:00");
    }, []);

    const startTimeOptions = useMemo(() => {
        const _timeOptions = timeOptions.filter((_option) => {
            if (_option == "24:00") {
                return false;
            }

            if (meeting && meeting.start_time) {
                const _st = moment(meeting.start_time, "hh:mm A").format(
                    "kk:mm"
                );
                if (_st == _option) {
                    return true;
                }
            }

            const _startTime = moment(_option, "kk:mm");
            const isSlotAvailable = availableSlots.some((slot) => {
                const _slotStartTime = moment(slot.start_time, "kk:mm");
                const _slotEndTime = moment(slot.end_time, "kk:mm");

                return (
                    _slotStartTime.isSame(_startTime) ||
                    _startTime.isBetween(_slotStartTime, _slotEndTime)
                );
            });

            if (!isSlotAvailable) {
                return false;
            }

            return !bookedSlots.some((slot) => {
                const _slotStartTime = moment(slot.start_time, "kk:mm");
                const _slotEndTime = moment(slot.end_time, "kk:mm");

                return (
                    _startTime.isBetween(_slotStartTime, _slotEndTime) ||
                    _startTime.isSame(_slotStartTime)
                );
            });
        });

        return _timeOptions;
    }, [timeOptions, availableSlots, bookedSlots]);

    useEffect(() => {
        getTeamAvailibality(selectedDate);
    }, [selectedDate]);

    const handleSubmit = () => {
        if (!selectedDate) {
            alert.error("Date not selected");
            return false;
        }

        if (!selectedTime) {
            alert.error("Time not selected");
            return false;
        }

        setIsLoading(true);
        axios
            .post(`/api/client/meeting/${meeting.id}/reschedule`, {
                start_date: selectedDate
                    ? moment(selectedDate).format("YYYY-MM-DD")
                    : null,
                start_time: selectedTime,
            })
            .then((response) => {
                setIsLoading(false);
                alert.error(response.data.message);
            })
            .catch((e) => {
                setIsLoading(false);

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const formattedSelectedDate = useMemo(() => {
        if (selectedDate) {
            const _date = new Date(selectedDate);
            const dayName = _date.toLocaleDateString("en-US", {
                month: "long",
            });

            const monthName = _date.toLocaleDateString("en-US", {
                weekday: "long",
            });

            const date = _date.getDate();

            return `${dayName}, ${monthName}, ${date}`;
        }
        return "";
    }, [selectedDate]);

    const timeSlots = useMemo(() => {
        return startTimeOptions.map((i) =>
            moment(i, "kk:mm").format("hh:mm A")
        );
    }, [startTimeOptions]);

    return (
        <>
            <div className="mx-auto mt-5 custom-calendar">
                <div className="border">
                    <h5 className="mt-3">Select a Date & Time</h5>
                    <div
                        className="d-flex gap-3 p-3"
                        style={{ overflowX: "auto" }}
                    >
                        <div>
                            <DatePicker
                                selected={selectedDate}
                                onChange={(date) => setSelectedDate(date)}
                                autoFocus
                                shouldCloseOnSelect={false}
                                inline
                                minDate={new Date()}
                            />
                        </div>
                        <div className="mt-1 ">
                            <h6 className="time-slot-date">
                                {formattedSelectedDate}
                            </h6>
                            <ul className="list-unstyled mt-4 timeslot">
                                {timeSlots.length > 0 ? (
                                    timeSlots.map((t, index) => {
                                        return (
                                            <li
                                                className={`py-2 px-3 border  mb-2  text-center border-primary  ${
                                                    selectedTime === t
                                                        ? "bg-primary text-white"
                                                        : "text-primary"
                                                }`}
                                                key={index}
                                                onClick={() =>
                                                    setSelectedTime(t)
                                                }
                                            >
                                                {t}
                                            </li>
                                        );
                                    })
                                ) : (
                                    <li className="py-2 px-3 border mb-2 text-center border-secondary text-secondary bg-light">
                                        No time slot avaiable
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <button
                type="button"
                className="btn btn-primary mt-2"
                onClick={handleSubmit}
                disabled={isLoading}
            >
                Submit
            </button>
        </>
    );
};

export default CustomCalendar;
