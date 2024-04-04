import moment from "moment-timezone";
import TimeSlot from "./TimeSlot";

const WeekCard = ({ tabName, week, slots, setTimeSlots, timeSlots }) => {
    return (
        <div
            className="tab-pane active show"
            role="tab-panel"
            aria-labelledby={tabName}
        >
            <div className="table-responsive">
                <table className="timeslots table">
                    <thead>
                        <tr>
                            {week.map((element, index) => (
                                <th key={index}>
                                    {moment(element).toString().slice(0, 15)}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            {week.map((w, _wIndex) => (
                                <td key={_wIndex}>
                                    <TimeSlot
                                        clsName={w}
                                        keyName={_wIndex}
                                        slots={slots}
                                        setTimeSlots={setTimeSlots}
                                        timeSlots={timeSlots}
                                    />
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default WeekCard;
