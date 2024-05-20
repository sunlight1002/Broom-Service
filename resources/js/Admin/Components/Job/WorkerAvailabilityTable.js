import moment from "moment-timezone";
import "react-tooltip/dist/react-tooltip.css";
import { Tooltip } from "react-tooltip";

export default function WorkerAvailabilityTable({
    workerAvailabilities,
    week,
    AllWorkers,
    hasActive,
    changeShift,
    removeShift,
    isClient = false,
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
                                    let workerSlots = workerAvailabilities.find(_w => {
                                        return _w.workerId == w.id
                                    }) ?? [];
                                    let slots = workerSlots?.slots?.find(_s => {
                                        return _s.date == element;
                                    }) ?? [];
                                    let hasStartActive = false;
                                    return (
                                        <td key={index}>
                                            <div className="d-flex">
                                                <div className="d-flex slots">
                                                    {slots?.allSlots?.length > 0 ? (
                                                        slots?.allSlots.map(
                                                            (shift, _sIdx) => {
                                                                let isActive =
                                                                    hasActive(
                                                                        w.id,
                                                                        element,
                                                                        shift
                                                                    );

                                                                if(!hasStartActive) {
                                                                    hasStartActive = isActive;
                                                                } else if(isClient) {
                                                                    isActive = false;
                                                                }
                                                                let tooltip = '';
                                                                if(!isClient) {
                                                                    if(shift?.isBooked) {
                                                                        tooltip = shift?.clientName + shift?.jobId;
                                                                    } else if(shift?.isFreezed && isClient) {
                                                                        tooltip = 'Shift is freezed by Administrator';
                                                                    } else if(shift?.isFreezed && !isClient) {
                                                                        tooltip = 'Shift is freezed';
                                                                    } else if(shift?.notAvailable) {
                                                                        tooltip = 'Worker is not available';
                                                                    }
                                                                }
                                                                return (
                                                                    <div
                                                                        data-tooltip-hidden={
                                                                            shift?.isBooked ||
                                                                            (shift?.isFreezed && !isClient) ||
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
                                                                            (shift?.isFreezed && isClient) ||
                                                                            shift?.notAvailable
                                                                                ? "slot-disabled"
                                                                                : ""
                                                                        }`}
                                                                        onClick={() => {
                                                                            if (
                                                                                !shift?.isBooked &&
                                                                                (!shift?.isFreezed || !isClient) &&
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
