import { useEffect, useMemo, useState } from "react";
import moment from "moment-timezone";
import "react-tooltip/dist/react-tooltip.css";
import { Tooltip } from "react-tooltip";
import { useTranslation } from "react-i18next";

export default function WorkerAvailabilityTable({
    workerAvailabilities,
    week,
    AllWorkers,
    hasActive,
    changeShift,
    removeShift,
    searchKeyword = "",
    isClient = false,
}) {
    // const [workers, setWorkers] = useState(AllWorkers);
    const [sortOrder, setSortOrder] = useState("asc");
    const { t } = useTranslation();

    const handleSorting = () => {
        if (sortOrder == "asc") {
            setSortOrder("desc");
        } else {
            setSortOrder("asc");
        }
    };

    const workers = useMemo(() => {
        let modifiedWorkers = AllWorkers;

        if (searchKeyword) {
            const _searchKeyword = searchKeyword.toLocaleLowerCase();

            modifiedWorkers = modifiedWorkers.filter((i) => {
                const name = (
                    i.firstname +
                    " " +
                    i.lastname
                ).toLocaleLowerCase();

                return name.includes(_searchKeyword);
            });
        }

        const _workers = [...modifiedWorkers].sort((a, b) => {
            const name1 = (a.firstname + " " + a.lastname).toLocaleLowerCase();
            const name2 = (b.firstname + " " + b.lastname).toLocaleLowerCase();

            if (sortOrder == "asc") {
                return name1 < name2 ? -1 : name1 > name2 ? 1 : 0;
            } else {
                return name1 > name2 ? -1 : name1 < name2 ? 1 : 0;
            }
        });

        return _workers;
    }, [AllWorkers, sortOrder, searchKeyword]);

    return (
        <>
            <div className="table-container">
                <table className="table table-bordered crt-jb-wrap worker-availability-table">
                    <thead>
                        <tr>
                            <th
                                className="text-center worker-name"
                                onClick={handleSorting}
                            >
                                {t("client.jobs.change.Worker")}
                                <i
                                    className={
                                        `ml-2 fa ` +
                                        (sortOrder == "asc"
                                            ? "fa-sort-up"
                                            : "fa-sort-down")
                                    }
                                ></i>
                            </th>
                            {week.map((element, index) => (
                                <th className="text-center" key={index}>
                                    {moment(element)
                                        .format("MMM DD")
                                        .toString()}{" "}
                                    <span className="day-text">
                                        {moment(element)
                                            .format("ddd")
                                            .toString()}
                                    </span>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {workers.map((w, index) => {
                            return (
                                <tr key={index}>
                                    <td className="worker-name">
                                        <span
                                            id={`worker-${w.id}`}
                                            className="align-items-center justify-content-center"
                                        >
                                            {isClient ? (
                                                <>
                                                    {w.firstname}

                                                    {w.gender == "male" && (
                                                        <i className="fa fa-person text-primary ml-2"></i>
                                                    )}

                                                    {w.gender == "female" && (
                                                        <i
                                                            className="fa fa-person-dress ml-2"
                                                            style={{
                                                                color: "pink",
                                                            }}
                                                        ></i>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {w.firstname} {w.lastname}
                                                </>
                                            )}
                                        </span>
                                    </td>
                                    {week.map((element, index) => {
                                        let workerSlots =
                                            workerAvailabilities.find((_w) => {
                                                return _w.workerId == w.id;
                                            }) ?? [];
                                        let slots =
                                            workerSlots?.slots?.find((_s) => {
                                                return _s.date == element;
                                            }) ?? [];
                                        let hasStartActive = false;

                                        const filteredSlots = isClient
                                            ? slots?.slots
                                            : slots?.allSlots;
                                        return (
                                            <td key={index}>
                                                <div className="d-flex">
                                                    <div className="d-flex slots">
                                                        {filteredSlots?.length >
                                                        0 ? (
                                                            filteredSlots.map(
                                                                (
                                                                    shift,
                                                                    _sIdx
                                                                ) => {
                                                                    let isActive =
                                                                        hasActive(
                                                                            w.id,
                                                                            element,
                                                                            shift
                                                                        );

                                                                    if (
                                                                        !hasStartActive
                                                                    ) {
                                                                        hasStartActive =
                                                                            isActive;
                                                                    } else if (
                                                                        isClient
                                                                    ) {
                                                                        isActive = false;
                                                                    }
                                                                    let tooltip =
                                                                        "";
                                                                    if (
                                                                        !isClient
                                                                    ) {
                                                                        if (
                                                                            shift?.isBooked
                                                                        ) {
                                                                            tooltip =
                                                                                shift?.clientName +
                                                                                shift?.jobId;
                                                                        } else if (
                                                                            shift?.isFreezed &&
                                                                            isClient
                                                                        ) {
                                                                            tooltip =
                                                                                t(
                                                                                    "client.jobs.change.shiftFreezedByAdmin"
                                                                                );
                                                                        } else if (
                                                                            shift?.isFreezed &&
                                                                            !isClient
                                                                        ) {
                                                                            tooltip =
                                                                                t(
                                                                                    "client.jobs.change.shiftFreezed"
                                                                                );
                                                                        } else if (
                                                                            shift?.notAvailable
                                                                        ) {
                                                                            tooltip =
                                                                                t(
                                                                                    "client.jobs.change.workNotAvail"
                                                                                );
                                                                        }
                                                                    }
                                                                    return (
                                                                        <div
                                                                            data-tooltip-hidden={
                                                                                shift?.isBooked ||
                                                                                (shift?.isFreezed &&
                                                                                    !isClient) ||
                                                                                shift?.notAvailable
                                                                            }
                                                                            data-tooltip-id="slot-tooltip"
                                                                            data-tooltip-content={
                                                                                tooltip
                                                                            }
                                                                            className={`d-flex slot justify-content-between ${
                                                                                isActive
                                                                                    ? "bg-primary-selected"
                                                                                    : ""
                                                                            } ${
                                                                                shift?.isBooked ||
                                                                                (shift?.isFreezed &&
                                                                                    isClient) ||
                                                                                shift?.notAvailable
                                                                                    ? "slot-disabled"
                                                                                    : ""
                                                                            }`}
                                                                            onClick={() => {
                                                                                if (
                                                                                    !shift?.isBooked &&
                                                                                    (!shift?.isFreezed ||
                                                                                        !isClient) &&
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
                                                                {t(
                                                                    "client.jobs.change.notAvail"
                                                                )}
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
            </div>

            <Tooltip id="slot-tooltip" />
        </>
    );
}
