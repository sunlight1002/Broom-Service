import { useState, useEffect } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import DatePicker from "react-datepicker";
import moment from "moment";
import axios from "axios";
import { use } from "i18next";
import { Tooltip } from "react-tooltip";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { useTranslation } from "react-i18next";

// import "react-datepicker/dist/react-datepicker.css"; // import DatePicker styles

export default function ChangeStatusModal({
    handleChangeStatusModalClose,
    isOpen,
    clientId,
    getUpdatedData,
    statusArr,
}) {
    const alert = useAlert();
    const [allHolidays, setAllHolidays] = useState([])
    const [holidayNamesMap, setHolidayNamesMap] = useState({});
    const [pendingJobs, setPendingJobs] = useState([]);
    const [fee, setFee] = useState("0");
    const { t } = useTranslation();
    const [status, setStatus] = useState(null);
    const [formValues, setFormValues] = useState({
        reason: "",
        status: "",
        id: clientId,
        reschedule_date: null, // Add a field for the reschedule date
        reschedule_time: "", // Add a field for the reschedule time
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [loading, setLoading] = useState(false);
    const [totalAmount, setTotalAmount] = useState(0);

    const PastStatusMap = {
        "unhappy": t("admin.client.Unhappy"),
        "price issue": t("admin.client.Price_issue"),
        "moved": t("admin.client.Moved"),
        "one-time": t("admin.client.One_Time"),
    };

    const generateWeekendDates = (start, end) => {
        const weekends = [];
        const currentDate = new Date(start);

        while (currentDate <= end) {
            const dayOfWeek = currentDate.getDay();
            if (dayOfWeek === 5 || dayOfWeek === 6) { // 5 = Friday, 6 = Saturday
                weekends.push(new Date(currentDate));
            }
            currentDate.setDate(currentDate.getDate() + 1); // Move to the next day
        }

        return weekends;
    };

    const [isLoading, setIsLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const checkValidation = () => {
        if (!formValues.reason) {
            alert.error("The reason is missing");
            return false;
        }

        // Check if status is "reschedule call" and validate date and time
        if (formValues.status === "reschedule call" && !formValues.reschedule_date) {
            alert.error("Please select a date for rescheduling the call");
            return false;
        }
        if (formValues.status === "reschedule call" && !formValues.reschedule_time) {
            alert.error("Please select a time for rescheduling the call");
            return false;
        }

        return true;
    };

    const handleSubmit = () => {
        const valid = checkValidation();
        if (!valid) {
            return; // Exit early if validation fails
        }

        setIsLoading(true);

        let processedFormValues = null;

        if(status){
             processedFormValues = {
                ...formValues,
                status: status
            }
        } else {
             processedFormValues = {
                ...formValues,
                reschedule_date: formValues.reschedule_date
                    ? moment(formValues.reschedule_date).format("YYYY-MM-DD")
                    : null,
                reschedule_time: formValues.reschedule_time,
            };
        }

        // Use FormData for submission
        const formData = new FormData();
        Object.keys(processedFormValues).forEach((formKey) => {
            formData.append(formKey, processedFormValues[formKey]);
        });

        axios
            .post(`/api/admin/client-status-log`, formData, { headers })
            .then(async (response) => {
                Swal.fire("Added!", response?.data?.message, "success");
                setIsLoading(false);
                await getUpdatedData(); // Fetch updated data after successful submission
                handleChangeStatusModalClose(); // Close the modal
                if (formValues.status === "past") {
                    cencelJob();
                }
            })
            .catch((e) => {
                console.log(e);

                Swal.fire({
                    title: "Error!",
                    text: e.response?.data?.message || "Something went wrong!",
                    icon: "error",
                });
                setIsLoading(false);
            });
    };
    const handleAllHolidays = async () => {
        try {
            const res = await axios.get(`/api/admin/all-holidays`, { headers });
            const holidaysData = res.data;

            const holidayDates = [];
            const namesMap = {};

            // Define the date range for weekends
            const startDate = new Date(); // Today
            const endDate = new Date();
            endDate.setFullYear(startDate.getFullYear() + 1); // Next year

            // Add Fridays and Saturdays
            const weekendDates = generateWeekendDates(startDate, endDate);
            weekendDates.forEach((date) => {
                holidayDates.push(date);
                namesMap[date.toDateString()] = "Weekend"; // Add "Weekend" as holiday name
            });

            // Add holidays from the API
            holidaysData.forEach((holiday) => {
                holiday.all_dates.forEach((date) => {
                    const holidayDate = new Date(date);
                    holidayDates.push(holidayDate);
                    namesMap[holidayDate.toDateString()] = holiday.name; // Map holiday name
                });
            });

            setAllHolidays(holidayDates);
            setHolidayNamesMap(namesMap);
        } catch (error) {
            console.error("Error fetching holidays:", error);
        }
    };

    const getJobsOrder = async () => {
        const res = await axios.get(`/api/admin/get-pending-job-orders/${clientId}`, { headers });
        setPendingJobs(res.data);
    }


    const cencelJob = async () => {
        const data = {
            fee: fee
        }
        try {
            const res = await axios.put(`/api/admin/cancel-pending-job-orders/${clientId}`, data, { headers });
            console.log(res);
            getJobsOrder();
        } catch (error) {
            console.error("Error fetching holidays:", error);
        }
    }

    useEffect(() => {
        handleAllHolidays();
        getJobsOrder();
    }, []);

    const handleFeeChange = (_value) => {
        if (fee == _value) {
            setFee("0");
        } else {
            setFee(_value);
        }
    };

    const getHolidayName = (date) => holidayNamesMap[date.toDateString()] || "";

    return (
        <div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => handleChangeStatusModalClose()}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>{t("global.change_status")}</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.status")}</label>

                                <select
                                    name="status"
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            status: e.target.value,
                                        });
                                    }}
                                    value={formValues.status}
                                    className="form-control mb-3"
                                >
                                    <option value="">---select status---</option>
                                    {Object.keys(statusArr).map((s) => (
                                        <option key={s} value={s}>
                                            {statusArr[s]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        {(formValues.status === "reschedule call" || formValues.status === "voice bot") && (
                            <>
                                <div className="col-sm-12">
                                    <div className="form-group d-flex flex-column">
                                        <label className="control-label">{ formValues.status === "reschedule call" ? t("global.reschedule_date") : "Voice Call Date"}</label>
                                        <DatePicker
                                            selected={formValues.reschedule_date}
                                            onChange={(date) =>
                                                setFormValues({
                                                    ...formValues,
                                                    reschedule_date: date,
                                                })
                                            }
                                            className="form-control"
                                            dateFormat="yyyy-MM-dd"
                                            minDate={new Date()}
                                            highlightDates={allHolidays} // Highlight holiday dates dynamically
                                            dayClassName={(date) =>
                                                allHolidays.some(
                                                    (holiday) => holiday.toDateString() === date.toDateString()
                                                )
                                                    ? "holiday" // Add custom class to holiday dates
                                                    : undefined
                                            }
                                            renderDayContents={(day, date) => {
                                                const holidayName = getHolidayName(date);
                                                return (
                                                    <div>
                                                        <span><strong
                                                            data-tooltip-id="name"
                                                            data-tooltip-content={holidayName}
                                                        >{day}</strong></span>
                                                    </div>
                                                );
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">{ formValues.status === "reschedule call" ? t("global.reschedule_time") : "Voice Call Time"}</label>
                                        <input
                                            type="time"
                                            className="form-control"
                                            value={formValues.reschedule_time}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    reschedule_time: e.target.value,
                                                });
                                            }}
                                            required
                                        />
                                    </div>
                                </div>
                            </>
                        )}
                        {
                            formValues.status === "past" && (pendingJobs?.jobs?.length > 0 || pendingJobs?.orders?.length > 0) && (
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.schedule.jobs.CancelModal.CancellationFee"
                                            )}
                                        </label>
                                        <div className="form-check">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="fee"
                                                id="fee0"
                                                value={0}
                                                defaultChecked={true}
                                                onChange={(e) => {
                                                    handleFeeChange(e.target.value);
                                                }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="fee0"
                                            >
                                                0%
                                            </label>
                                        </div>
                                        <div className="form-check">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="fee"
                                                id="fee50"
                                                value={50}
                                                onChange={(e) => {
                                                    handleFeeChange(e.target.value);
                                                }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="fee50"
                                            >
                                                50%
                                            </label>
                                        </div>
                                        <div className="form-check">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="fee"
                                                id="fee100"
                                                value={100}
                                                onChange={(e) => {
                                                    handleFeeChange(e.target.value);
                                                }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="fee100"
                                            >
                                                100%
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            )
                        }
                        {
                            formValues.status === "past" && (
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">{t("global.sub_status")}</label>

                                        <select
                                            name="status"
                                            onChange={(e) => setStatus(e.target.value)}
                                            value={status}
                                            className="form-control mb-3"
                                        >
                                            <option value="">--- Select Sub Status ---</option>
                                            {Object.keys(PastStatusMap).map((s) => (
                                                <option key={s} value={s}>
                                                    {PastStatusMap[s]}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            )
                        }
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.reason")}</label>

                                <textarea
                                    name="reason"
                                    type="text"
                                    value={formValues.reason}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            reason: e.target.value,
                                        });
                                    }}
                                    className="form-control"
                                    required
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => handleChangeStatusModalClose()}
                    >
                        Close
                    </Button>
                    <Button
                        type="button"
                        disabled={isLoading}
                        onClick={handleSubmit}
                        className="btn btn-primary"
                    >
                        Save
                    </Button>
                </Modal.Footer>
            </Modal>
            {isLoading && <FullPageLoader visible={isLoading} />}
            <Tooltip id="name" place="top" type="dark" effect="solid" style={{ zIndex: "99999" }} />
        </div>
    );
}
