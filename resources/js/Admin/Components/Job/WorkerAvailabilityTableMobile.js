import { useEffect, useMemo, useState } from "react";
import moment from "moment-timezone";
import "react-tooltip/dist/react-tooltip.css";
import { Tooltip } from "react-tooltip";
import { useTranslation } from "react-i18next";
import { MdArrowBackIosNew, MdArrowForwardIos } from "react-icons/md";
import { getWorkersData, parseTimeSlots } from "../../../Utils/job.utils";

export default function WorkerAvailabilityTableMobile({
    workerAvailabilities,
    week,
    AllWorkers,
    hasActive,
    changeShift,
    removeShift,
    searchKeyword = "",
    isClient = false,
    selectedHours,
}) {

    console.log(isClient);

    const [filterSlots, setFilterSlots] = useState([]);
    const [selectedWorker, setSelectedWorker] = useState(null);
    const [selectedDate, setSelectedDate] = useState(week[0]);
    const [currentDateIndex, setCurrentDateIndex] = useState(0); // Track date index for pagination
    const [sortOrder, setSortOrder] = useState("asc");
    const { t } = useTranslation();
    const [bookedSlots, setBookedSlots] = useState([]);

    const today = moment().startOf("day"); // Today's date

    const workers = useMemo(() => {
        let modifiedWorkers = AllWorkers;

        const today = moment().startOf('day');
        let futureBookedSlots = [];

        modifiedWorkers.forEach((worker) => {
            if (worker.booked_slots) {
                Object.keys(worker.booked_slots).forEach((date) => {
                    const slotDate = moment(date, "YYYY-MM-DD");

                    // Check if the slot date is today or in the future
                    if (slotDate.isSameOrAfter(today)) {
                        futureBookedSlots.push({
                            worker_id: worker.id, // Include worker ID
                            date: date,           // Include the date
                            slots: worker.booked_slots[date], // Include the booked slots
                        });
                    }
                });
            }
        });

        setBookedSlots(futureBookedSlots);

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

            if (sortOrder === "asc") {
                return name1 < name2 ? -1 : name1 > name2 ? 1 : 0;
            } else {
                return name1 > name2 ? -1 : name1 < name2 ? 1 : 0;
            }
        });

        return _workers;
    }, [AllWorkers, sortOrder, searchKeyword, distance]);

    const getBookedSlotsForWorkerAndDate = (workerId, date) => {
        const bookedSlot = bookedSlots.find(slot => slot.worker_id === workerId && slot.date === date);
        return bookedSlot ? bookedSlot.slots : [];
    };

    const handleSlotFilter = (worker, date) => {
        if (selectedWorker && selectedWorker.id === worker.id) {
            // Toggle visibility if the same worker is clicked
            setSelectedWorker(null);
        } else {
            const workerSlots = workerAvailabilities?.find((wa) => wa.workerId === worker.id) ?? {};
            const slots = workerSlots?.slots?.find((slot) => slot.date === date) ?? {};

            const filtered = isClient ? slots?.slots : slots?.allSlots;

            setFilterSlots(filtered);
            setSelectedDate(date);
            setSelectedWorker(worker);
            setCurrentDateIndex(0);  // Reset to first date index
        }
    };

    const handleNextM = () => {
        if (currentDateIndex < week.length - 1) {
            const nextDate = moment(week[currentDateIndex + 1]);
            setSelectedDate(nextDate.format("YYYY-MM-DD"));
            setCurrentDateIndex(currentDateIndex + 1);
        }
    };

    const handlePrevM = () => {
        if (currentDateIndex > 0) {
            const prevDate = moment(week[currentDateIndex - 1]);
            setSelectedDate(prevDate.format("YYYY-MM-DD"));
            setCurrentDateIndex(currentDateIndex - 1)
        }
    }

    return (
        <div className="table-container" style={{ maxHeight: "100%" }}>
            <div className="d-flex justify-content-between align-items-center mb-3">
                <button
                    type="button"
                    className="px-3 py-2"
                    onClick={handlePrevM}
                    style={{ paddingTop: "7px", borderRadius: "5px" }}
                >
                    <MdArrowBackIosNew className="d-flex" />
                </button>
                <span>
                    <b>{moment(selectedDate).format("MMM DD")}</b> {moment(selectedDate).format("ddd")}
                </span>
                <button
                    type="button"
                    className="px-3 py-2"
                    onClick={handleNextM}
                    style={{ paddingTop: "7px", borderRadius: "5px" }}
                >
                    <MdArrowForwardIos className="d-flex" />
                </button>
            </div>

            <div className="worker-container">
                {workers.map((worker) => {
                    let workerSlots =
                        workerAvailabilities?.find((_w) => _w.workerId === worker.id) ?? [];
                    let slots =
                        workerSlots?.slots?.find((_s) => _s.date === selectedDate) ?? [];


                    const filteredSlots = isClient
                        ? slots?.slots
                        : slots?.allSlots;
                    // console.log(filteredSlots.length);

                    return (
                        <div key={worker.id} className="worker-info d-flex flex-column" style={{ borderTop: "1px solid #E5EBF1", borderBottom: "1px solid #E5EBF1" }}>
                            <div className="d-flex justify-content-between">
                                <div className="d-flex flex-column">
                                    <div className="mt-2">
                                        <span className="font-18">{worker.firstname} {worker.lastname}</span>
                                    </div>
                                    {filteredSlots?.length > 0 ? (
                                        <div className="time d-flex mt-2">
                                            <div className="busy-time-text mb-1">Busy Time</div>
                                            <div className="d-flex ml-3">
                                                <div className="slot-info mr-1">
                                                    {getBookedSlotsForWorkerAndDate(worker.id, selectedDate).map((slot, idx) => (
                                                        <div key={idx} className="slot-info mr-1">
                                                            {parseTimeSlots(slot.slot).map((time, timeIdx) => (
                                                                <span key={timeIdx} className="badge badge-primary">
                                                                    {time}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    ))}
                                                </div>
                                                {
                                                    selectedHours?.map((slot, idx) => {
                                                        const filteredSlots = slot?.slots?.filter(
                                                            (s) => s.date === selectedDate && s.workerId === worker.id 
                                                        );

                                                        if (filteredSlots?.length > 0) {
                                                            return (
                                                                <div key={idx} className="slot-info ml-1">
                                                                    {getWorkersData(selectedHours).map((d, workerDataIdx) => (
                                                                        <span key={workerDataIdx} className="badge badge-info text-white">
                                                                            {d.shifts}
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            );
                                                        }
                                                        return null;
                                                    })
                                                }
                                            </div>
                                        </div>
                                    ) : (
                                        <div className={`navyblueColor pr-5 pr-md-0 `}>
                                            {t("client.jobs.change.notAvail")}
                                        </div>
                                    )}
                                </div>
                                {filteredSlots?.length > 0 ? (
                                    <div className="ml-2 d-flex justify-content-end" style={{ margin: "20px 10px" }}>
                                        <button
                                            type="button"
                                            onClick={() => handleSlotFilter(worker, selectedDate)}
                                        >
                                            <i className="fa-solid fa-calendar-days"></i>
                                        </button>
                                    </div>
                                ) : (
                                    ""
                                )}

                            </div>

                            {selectedWorker && selectedWorker.id === worker.id && (
                                <div className="worker-slots text-center" style={{ borderTop: "1px solid #E5EBF1" }}>
                                    <div className="d-flex mt-2">
                                        <div className="d-flex slots flex-wrap justify-content-center">
                                            {filterSlots.length > 0 ? (
                                                filterSlots.map((shift, idx) => {
                                                    const isActive = hasActive(worker.id, selectedDate, shift);
                                                    let tooltip = "";
                                                    if (!isClient) {
                                                        if (shift?.isBooked) {
                                                            tooltip = shift?.clientName + shift?.jobId;
                                                        } else if (shift?.isFreezed && isClient) {
                                                            tooltip = t("client.jobs.change.shiftFreezedByAdmin");
                                                        } else if (shift?.isFreezed && !isClient) {
                                                            tooltip = t("client.jobs.change.shiftFreezed");
                                                        } else if (shift?.notAvailable) {
                                                            tooltip = t("client.jobs.change.workNotAvail");
                                                        }
                                                    }

                                                    return (
                                                        <div key={idx} className="mb-2">
                                                            <div
                                                                 data-tooltip-hidden={
                                                                    shift?.isBooked ||
                                                                    (shift?.isFreezed && !isClient) ||
                                                                    shift?.notAvailable
                                                                }
                                                                data-tooltip-id="slot-tooltip"
                                                                data-tooltip-content={tooltip}
                                                                className={`d-flex slot justify-content-between ${shift?.isBooked || (shift?.isFreezed && isClient) || shift?.notAvailable ? "slot-disabled" : ""} ${isActive ? "none bg-primary-selected" : ""}`}
                                                                onClick={() => {
                                                                    if (
                                                                        !shift?.isBooked &&
                                                                        (!shift?.isFreezed || !isClient) &&
                                                                        !shift?.notAvailable
                                                                    ) {
                                                                        isActive
                                                                            ? removeShift(selectedWorker.id, selectedDate, shift)
                                                                            : changeShift(selectedWorker.id, selectedDate, shift);
                                                                    }
                                                                }}
                                                            >
                                                                <span className="" style={{ marginLeft: "4px", marginTop: "2px" }}>
                                                                    {shift.time ? (
                                                                        <>
                                                                            <div style={{ fontSize: "14px" }}>
                                                                                {moment(shift.time, "HH:mm").format("HH")}
                                                                            </div>
                                                                            <div>
                                                                                {moment(shift.time, "HH:mm").format("mm")}
                                                                            </div>
                                                                        </>
                                                                    ) : "-"}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    );
                                                })
                                            ) : (
                                                <div className={`navyblueColor text-right pr-5 pr-md-0 text-md-center w-100`}>
                                                    {t("client.jobs.change.notAvail")}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )
                })}
            </div>
        </div>
    );
}
