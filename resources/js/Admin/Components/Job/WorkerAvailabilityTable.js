import moment from "moment-timezone";
import { filterShiftOptions } from "../../../Utils/job.utils";
import "react-tooltip/dist/react-tooltip.css";
import { Tooltip } from "react-tooltip";

export default function WorkerAvailabilityTable({
    week,
    AllWorkers,
    hasActive,
    changeShift,
    removeShift,
    selectedHours
}) {
    return (
        <>
            <table className="table table-bordered crt-jb-wrap worker-availability-table">
                <thead>
                    <tr>
                        <th className="text-center worker-name">Worker</th>
                        {week.map((element, index) => (
                            <th className="text-center" key={index}>
                                {moment(element).format("MMM DD").toString()}{" "}
                                <span className="day-text">
                                    {moment(element).format("ddd").toString()}
                                </span>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {AllWorkers.map((w, index) => {
                        let availabilities = w.availabilities ?? [];
                        let booked_slots = w.booked_slots ?? [];

                        const shiftFreezeTime = {
                            start: w.freeze_shift_start_time,
                            end: w.freeze_shift_end_time,
                        };

                        const notAvailableDates = w.not_available_on;

                        return (
                            <tr key={index}>
                                <td className="worker-name">
                                    <span
                                        id={`worker-${w.id}`}
                                        className="align-items-center justify-content-center"
                                    >
                                        {w.firstname} {w.lastname}
                                    </span>
                                </td>
                                {week.map((element, index) => {
                                    let shifts = booked_slots[element] ?? [];
                                    let slots = filterShiftOptions(
                                        availabilities[element] ?? [],
                                        shifts,
                                        shiftFreezeTime,
                                        notAvailableDates?.find(
                                            (n) => n.date == element
                                        ),
                                        selectedHours,
                                        w.id,
                                        element,
                                    );
                                    return (
                                        <td key={index}>
                                            <div className="d-flex">
                                                <div className="d-flex slots">
                                                    {slots.length > 0 ? (
                                                        slots.map(
                                                            (shift, _sIdx) => {
                                                                const isActive =
                                                                    hasActive(
                                                                        w.id,
                                                                        element,
                                                                        shift
                                                                    );
                                                                let tooltip = '';
                                                                if(shift?.isBooked) {
                                                                    tooltip = shift?.clientName;
                                                                } else if(shift?.isFreezed) {
                                                                    tooltip = 'Shift is freezed by Administrator';
                                                                } else if(shift?.notAvailable) {
                                                                    tooltip = 'Worker is not available';
                                                                }
                                                                return (
                                                                    <div
                                                                        data-tooltip-hidden={
                                                                            shift?.isBooked ||
                                                                            shift?.isFreezed ||
                                                                            shift?.notAvailable
                                                                        }
                                                                        data-tooltip-id="slot-tooltip"
                                                                        data-tooltip-content={tooltip}
                                                                        className={`d-flex slot justify-content-between ${
                                                                            isActive
                                                                                ? "bg-primary-selected"
                                                                                : ""
                                                                        } ${
                                                                            shift?.isBooked ||
                                                                            shift?.isFreezed ||
                                                                            shift?.notAvailable
                                                                                ? "slot-disabled"
                                                                                : ""
                                                                        }`}
                                                                        onClick={() => {
                                                                            if (
                                                                                !shift?.isBooked &&
                                                                                !shift?.isFreezed &&
                                                                                !shift?.notAvailable
                                                                            ) {
                                                                                isActive
                                                                                    ? removeShift(
                                                                                          w.id,
                                                                                          element,
                                                                                          shift
                                                                                      )
                                                                                    : changeShift(
                                                                                          w.id,
                                                                                          element,
                                                                                          shift
                                                                                      );
                                                                            }
                                                                        }}
                                                                        key={
                                                                            _sIdx
                                                                        }
                                                                    >
                                                                        <>
                                                                            {shift.time
                                                                                ? moment(
                                                                                      shift.time,
                                                                                      "HH:mm"
                                                                                  ).format(
                                                                                      "hh A"
                                                                                  )
                                                                                : "-"}
                                                                        </>
                                                                    </div>
                                                                );
                                                            }
                                                        )
                                                    ) : (
                                                        <div
                                                            className={`text-danger text-right pr-5 pr-md-0 text-md-center`}
                                                        >
                                                            Not Available
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                    );
                                })}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
            <Tooltip id="slot-tooltip" />
        </>
    );
}
