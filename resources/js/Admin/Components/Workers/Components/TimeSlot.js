import { Fragment, createRef, useRef } from "react";

const slotTimeArr = [
    {
        name: "Start Time",
        key: "start_time",
    },
    {
        name: "End Time",
        key: "end_time",
    },
];
const TimeSlot = ({ clsName, keyName, slots, setTimeSlots, timeSlots }) => {
    const elementsRef = useRef(slotTimeArr.map(() => createRef()));
    const handleTimeSlotAdd = () => {
        let flag = true;
        const time = [];
        elementsRef.current.map((ref) => {
            if (ref.current.value) {
                time.push(ref.current.value);
                ref.current.value = "";
            } else {
                flag = false;
            }
        });
        if (!flag) {
            alert("Please add start & end time!");
            return false;
        }

        const _time = {
            start_time: time[0],
            end_time: time[1],
        };

        setTimeSlots((state) => ({
            ...state,
            [clsName]: state[clsName] ? [...state[clsName], _time] : [_time],
        }));
    };
    const handleTimeSlotRemove = (w, indexId) => {
        const removedSlots = [...timeSlots[w]].filter((t, i) => i !== indexId);
        setTimeSlots((state) => ({
            ...state,
            [w]: removedSlots,
        }));
    };
    return (
        <div className={clsName} key={keyName}>
            <div className="d-flex flex-row bd-highlight align-content-center align-items-center justify-content-center">
                <div className="d-flex flex-column bd-highlight mb-3 align-content-center flex-wrap align-items-center">
                    {slotTimeArr.map((s, index) => (
                        <div className="p-1 bd-highlight" key={s.key}>
                            <select
                                name={s.key}
                                className="form-control"
                                ref={elementsRef.current[index]}
                            >
                                <option value="">{s.name}</option>
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
                    ))}
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
