import { useEffect, useMemo, useState } from "react";
import moment from "moment-timezone";
import "react-tooltip/dist/react-tooltip.css";
import { Tooltip } from "react-tooltip";
import { useTranslation } from "react-i18next";
import { MdArrowBackIosNew } from "react-icons/md";
import { MdArrowForwardIos } from "react-icons/md";
import { Modal, Button, Carousel } from 'react-bootstrap';
import { getWorkersData, parseTimeSlots } from "../../../Utils/job.utils";
import useWindowWidth from "../../../Hooks/useWindowWidth";
import WorkerAvailabilityTableMobile from "./WorkerAvailabilityTableMobile";
import { getShiftsDetails } from "../../../Utils/common.utils";

export default function WorkerAvailabilityTable({
    workerAvailabilities,
    week,
    AllWorkers,
    hasActive,
    changeShift,
    removeShift,
    searchKeyword = "",
    isClient = false,
    selectedHours,
    distance
}) {
    let hasStartActive;    

    const [filterSlots, setFilterSlots] = useState([])
    const [selectedWorker, setSelectedWorker] = useState(null);
    const [selectedDate, setSelectedDate] = useState(week[0]);
    // const [hasStartActive, setHasStartActive] = useState(false)
    const [currentDateIndex, setCurrentDateIndex] = useState(0);  // Track date index for pagination
    const [modifiedWorkers, setModifiedWorkers] = useState([])
    const [lastData, setLastData] = useState([])

    const [show, setShow] = useState(false);
    const [mobileView, setMobileView] = useState(false);

    const windowWidth = useWindowWidth();

    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])

    const [sortOrder, setSortOrder] = useState("asc");
    const { t } = useTranslation();
    const [bookedSlots, setBookedSlots] = useState([])

    const handleSorting = () => {
        if (sortOrder == "asc") {
            setSortOrder("desc");
        } else {
            setSortOrder("asc");
        }
    };

    useEffect(() => {
        setModifiedWorkers(AllWorkers);

    }, []);
    
    const workers = () => {
        console.log(modifiedWorkers, "worker");
        
        const today = moment().startOf('day');
        let futureBookedSlots = [];
        
        modifiedWorkers.forEach((worker) => {
            
            if (worker.booked_slots) {
                Object.keys(worker.booked_slots).forEach((date) => {
                    const slotDate = moment(date, "YYYY-MM-DD");
                    console.log(slotDate, "slotDate");
                    

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

            const data = modifiedWorkers.filter((i) => {
                const name = (
                    i.firstname +
                    " " +
                    i.lastname
                ).toLocaleLowerCase();

                return name.includes(_searchKeyword);
            });
            setModifiedWorkers(data)
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

        setLastData(_workers)
    }

    useEffect(() => {
        workers()
    }, [modifiedWorkers, sortOrder, searchKeyword, distance]);

    const getBookedSlotsForWorkerAndDate = (workerId, date) => {
        // console.log(bookedSlots, workerId, date);
        
        const bookedSlot = bookedSlots.find(
            (slot) => slot.worker_id === workerId && slot.date === date
        );
        return bookedSlot ? bookedSlot.slots : [];
    };


    const handleSlotFilter = (worker, date, index) => {
        
        const workerSlots = workerAvailabilities?.find((wa) => wa.workerId === worker.id) ?? {};
        const slots = workerSlots?.slots?.find((slot) => slot.date === date) ?? {};

        const filtered = isClient ? slots?.slots : slots?.allSlots;

        setFilterSlots(filtered);
        setSelectedDate(date);
        setCurrentDateIndex(index);  // Set the current date index for pagination
        setSelectedWorker(worker);
        setShow(true);;
    };


    const getGroupedSlots = (filteredSlots) => {

        if (!filteredSlots.length) return [];

        const timeString = filteredSlots.map(slot => `${slot.time.time}-${slot.time.endTime}`).join(",");
        const pairs = timeString.split(",").map(slot => slot.split("-"));
        const groupedSlots = [];
        let currentGroup = [pairs[0][0]];

        const timeToMinutes = (time) => {
            const [hours, minutes] = time.split(":").map(Number);
            return hours * 60 + minutes;
        };

        pairs.forEach((pair, index) => {
            if (index > 0) {
                const previousEndTime = pairs[index - 1][1];
                const currentStartTime = pair[0];
                const timeDifference = timeToMinutes(currentStartTime) - timeToMinutes(previousEndTime);

                if (timeDifference <= 15) {
                    currentGroup[1] = pair[1];
                } else {
                    groupedSlots.push(`${currentGroup[0].slice(0, 5)} - ${currentGroup[1].slice(0, 5)}`);
                    currentGroup = [pair[0], pair[1]];
                }
            }
        });

        groupedSlots.push(`${currentGroup[0].slice(0, 5)} - ${currentGroup[1]?.slice(0, 5) || 'N/A'}`);
        return groupedSlots;
    };


    const handleNext = () => {
        if (currentDateIndex < week.length - 1) {
            const nextIndex = currentDateIndex + 1;
            const nextDate = week[nextIndex];

            setCurrentDateIndex(nextIndex);
            handleSlotFilter(selectedWorker, nextDate, nextIndex);
        }
    };

    const handlePrev = () => {
        if (currentDateIndex > 0) {
            const prevIndex = currentDateIndex - 1;
            const prevDate = week[prevIndex];

            setCurrentDateIndex(prevIndex);
            handleSlotFilter(selectedWorker, prevDate, prevIndex);
        }
    };


    return (
        <>
            {
                mobileView ? (
                    <WorkerAvailabilityTableMobile
                        workerAvailabilities={workerAvailabilities}
                        week={week}
                        AllWorkers={AllWorkers}
                        hasActive={hasActive}
                        changeShift={changeShift}
                        removeShift={removeShift}
                        searchKeyword={searchKeyword}
                        isClient={isClient}
                        selectedHours={selectedHours}
                        distance={distance}
                    />
                ) : (
                    <>
                        <div className="table-container">
                            <table className="table table-bordered crt-jb-wrap worker-availability-table">
                                <thead>
                                    <tr>
                                        <th className="text-center worker-name" onClick={handleSorting} style={{ border: "1px solid #dee2e6" }}>
                                            {t("client.jobs.change.Worker")}
                                            <i
                                                className={
                                                    `ml-2 fa ` +
                                                    (sortOrder === "asc"
                                                        ? "fa-sort-up"
                                                        : "fa-sort-down")
                                                }
                                            ></i>
                                        </th>
                                        {week?.map((element, index) => (
                                            <th className="text-center" key={index} style={{ border: "1px solid #dee2e6" }}>
                                                {moment(element).format("MMM DD")}{" "}
                                                <span className="day-text">
                                                    {moment(element).format("ddd")}
                                                </span>
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {modifiedWorkers?.map((w, index) => (
                                        <tr key={index} >
                                            <td className="worker-name" style={{ border: "1px solid #dee2e6" }}>
                                                <span
                                                    id={`worker-${w.id}`}
                                                    className="align-items-center justify-content-center"
                                                >
                                                    {isClient ? (
                                                        <>
                                                            {w.firstname}
                                                            {w.gender === "male" && (
                                                                <i className="fa fa-person text-primary ml-2"></i>
                                                            )}
                                                            {w.gender === "female" && (
                                                                <i
                                                                    className="fa fa-person-dress ml-2"
                                                                    style={{ color: "pink" }}
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
                                            {week?.map((element, index) => {
                                                const alreadyBooked = getBookedSlotsForWorkerAndDate(w.id, element);

                                                let workerSlots =
                                                    workerAvailabilities?.find((_w) => _w.workerId === w.id) ?? [];
                                                let slots =
                                                    workerSlots?.slots?.find((_s) => _s.date === element) ?? [];

                                                hasStartActive = false;

                                                const filteredSlots = isClient
                                                    ? slots?.slots
                                                    : slots?.allSlots;
                                                return (
                                                    <td key={index} style={{ border: "1px solid #dee2e6" }}>
                                                        {filteredSlots?.length > 0 ? (
                                                            <div className="d-flex flex-column my-1">
                                                                <div className="d-flex justify-content-between mb-1">
                                                                    {bookedSlots && (
                                                                        <div className="busy-time-text mb-1">
                                                                            Busy
                                                                            Time
                                                                        </div>
                                                                    )}
                                                                    <div className="ml-2 d-flex justify-content-end">
                                                                        <button type="button"
                                                                            onClick={() =>
                                                                                handleSlotFilter(
                                                                                    w,
                                                                                    element,
                                                                                    index
                                                                                )
                                                                            }
                                                                        >
                                                                            <i className="fa-solid fa-calendar-days"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div className="d-flex flex-wrap">
                                                                    {alreadyBooked.map((slot, idx) => {
                                                                        return (
                                                                            <div key={idx} className="slot-info mr-1">
                                                                                <span className="badge badge-primary" >
                                                                                    {slot?.slot}
                                                                                </span>
                                                                            </div>
                                                                        );

                                                                    }
                                                                    )}
                                                                    {selectedHours?.filter(slot => slot?.slots?.some(s => s.workerId === w.id)).map((slot, idx) => {
                                                                        const filteredSlots = slot.slots.filter(s => s.date === element && s.workerId === w.id);
                                                                        // console.log(filteredSlots);
                                                                        
                                                                        const groupedSlots = getGroupedSlots(filteredSlots);
                                                                        // console.log(groupedSlots);
                                                                        

                                                                        return (
                                                                            <div key={idx} className="slot-info ml-1">
                                                                                {groupedSlots.map((timeRange, slotIdx) => (
                                                                                    <span key={slotIdx} className="badge badge-info text-white">
                                                                                        {timeRange}
                                                                                    </span>
                                                                                ))}
                                                                            </div>
                                                                        );
                                                                    })}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <div
                                                                className={`navyblueColor text-right pr-5 pr-md-0 text-md-center`}
                                                            >
                                                                {t(
                                                                    "client.jobs.change.notAvail"
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                );

                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Modal className="slotModal" show={show} onHide={() => setShow(false)} centered>
                            <Modal.Header className="slotsModal" style={{ border: "0" }}>
                                <Modal.Title>{selectedWorker && selectedWorker.firstname + " " + selectedWorker.lastname}</Modal.Title>
                                <div style={{ marginLeft: 'auto' }}>
                                    <button
                                        className="px-3 py-2"
                                        variant="outline-primary"
                                        style={{ marginRight: '10px', paddingTop: "7px", borderRadius: "5px" }}
                                        onClick={handlePrev}
                                    >
                                        <MdArrowBackIosNew className="d-flex" />
                                    </button>
                                    <span className="mr-2"><b>{moment(selectedDate).format("MMM DD")}</b> {moment(selectedDate).format("ddd")}</span>
                                    <button
                                        className="px-3 py-2"
                                        variant="outline-primary" onClick={handleNext}
                                        style={{ paddingTop: "7px", borderRadius: "5px" }}
                                    >
                                        <MdArrowForwardIos className="d-flex" />
                                    </button>
                                </div>
                            </Modal.Header>
                            <Modal.Body >
                                <div className="d-flex slots justify-content-center flex-wrap">
                                    {filterSlots?.length > 0 ? (
                                        filterSlots.map((shift, _sIdx) => {
                                            let isActive = hasActive(selectedWorker.id, selectedDate, shift);
                                            if (!hasStartActive) {
                                                hasStartActive = isActive;
                                            } else if (isClient) {
                                                isActive = false;
                                            }

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
                                                <div key={_sIdx} className={`mb-2`}>
                                                    <div
                                                        data-tooltip-hidden={
                                                            shift?.isBooked ||
                                                            (shift?.isFreezed && !isClient) ||
                                                            shift?.notAvailable
                                                        }
                                                        data-tooltip-id="slot-tooltip"
                                                        data-tooltip-content={ tooltip }
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
                                                        <span className="" style={{
                                                                marginLeft: "4px",
                                                                marginTop: "2px",
                                                            }}
                                                        >
                                                            {shift.time ? (
                                                                <>
                                                                    <div style={{ fontSize: "14px", }}>
                                                                        {moment(shift.time, "HH:mm").format("HH")}
                                                                    </div>
                                                                    <div>
                                                                        {moment( shift.time,"HH:mm").format("mm")}
                                                                    </div>
                                                                </>
                                                            ) : (
                                                                "-"
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                            );
                                        })
                                    ) : (
                                        <div className={`navyblueColor text-right pr-5 pr-md-0 text-md-center`}>
                                            {t("client.jobs.change.notAvail")}
                                        </div>
                                    )}
                                </div>
                            </Modal.Body>
                            <Modal.Footer style={{ border: "0" }}>
                                <button variant="secondary" className="navyblue px-4 py-2" style={{ borderRadius: "7px" }} onClick={() => setShow(false)}>
                                    Close
                                </button>
                                <button variant="secondary" className="navyblue px-4 py-2" style={{ borderRadius: "7px" }} onClick={() => setShow(false)}>
                                    Save
                                </button>
                            </Modal.Footer>
                        </Modal>
                        <Tooltip id="slot-tooltip" style={{ zIndex: "99999" }} />
                    </>
                )
            }
        </>
    );
}

