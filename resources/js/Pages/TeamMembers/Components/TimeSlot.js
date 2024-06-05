import { useRef } from "react";
import * as moment from "moment";

const TimeSlot = ({ clsName, slots, setTimeSlots, timeSlots }) => {
    const startTimeRef = useRef();
    const handleTimeSlotAdd = () => {
        let flag = true;
        const time = [];

        if (startTimeRef.current.value) {
            const _endTime = moment(startTimeRef.current.value, "HH:mm")
                .add(30, "minute")
                .format("HH:mm");

            time.push(startTimeRef.current.value);
            time.push(_endTime);

            startTimeRef.current.value = "";
        } else {
            flag = false;
        }

        if (!flag) {
            alert("Please select time!");
            return false;
        }

        const _time = {
            start_time: time[0],
            end_time: time[1],
        };

        const isSelected = timeSlots[clsName]?.find(
            (t) =>
                t.start_time == _time.start_time && t.end_time == _time.end_time
        );

        if (!isSelected) {
            setTimeSlots((state) => ({
                ...state,
                [clsName]: state[clsName]
                    ? [...state[clsName], _time]
                    : [_time],
            }));
        } else {
            alert("Time slot is already selected");
        }
    };

    const handleTimeSlotRemove = (w, indexId) => {
        const removedSlots = [...timeSlots[w]].filter((t, i) => i !== indexId);
        setTimeSlots((state) => ({
            ...state,
            [w]: removedSlots,
        }));
    };

    return (
        <div className={clsName}>
            <div className="d-flex flex-row bd-highlight align-content-center align-items-center justify-content-center mb-3">
                <div className="d-flex flex-column bd-highlight align-content-center flex-wrap align-items-center">
                    <div className="p-1 bd-highlight">
                        <select
                            name="start_time"
                            className="form-control"
                            ref={startTimeRef}
                        >
                            <option value="">Time</option>
                            {slots.map((t, i) => {
                                return (
                                    <option value={t} key={i}>
                                        {" "}
                                        {t}{" "}
                                    </option>
                                );
                            })}
                        </select>
                    </div>
                </div>
                <div className="p-1 bd-highlight">
                    <button
                        type="button"
                        className="btn-sm btn btn-secondary"
                        onClick={() => handleTimeSlotAdd()}
                    >
                        <i className="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div className="d-flex flex-column">
                {timeSlots[clsName] &&
                    timeSlots[clsName].map((t, i) => {
                        return (
                            <div className="p-1" key={i}>
                                <label>
                                    <input
                                        type="checkbox"
                                        className="btn-check"
                                    />
                                    <span className={"forcustom"}>
                                        <label>
                                            {t.start_time + "-" + t.end_time}
                                        </label>
                                    </span>
                                </label>
                                <button
                                    type="button"
                                    className="btn-sm btn btn-danger ml-1"
                                    onClick={() =>
                                        handleTimeSlotRemove(clsName, i)
                                    }
                                >
                                    <i className="fa fa-trash"></i>
                                </button>
                            </div>
                        );
                    })}
            </div>
        </div>
    );
};

export default TimeSlot;
