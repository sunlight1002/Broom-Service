import { useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import DatePicker from "react-datepicker";
import moment from "moment";
// import "react-datepicker/dist/react-datepicker.css"; // import DatePicker styles

export default function ChangeStatusModal({
    handleChangeStatusModalClose,
    isOpen,
    clientId,
    getUpdatedData,
    statusArr,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        reason: "",
        status: "irrelevant",
        id: clientId,
        reschedule_date: null, // Add a field for the reschedule date
        reschedule_time: "", // Add a field for the reschedule time
    });


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

        const processedFormValues = {
            ...formValues,
            reschedule_date: formValues.reschedule_date
                ? moment(formValues.reschedule_date).format("YYYY-MM-DD")
                : null,
            reschedule_time: formValues.reschedule_time, 
        };

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
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response?.data?.message || "Something went wrong!",
                    icon: "error",
                });
                setIsLoading(false);
            });
    };

    // const holidays = [
    //     new Date("2024-12-07"), // Example holiday date
    //     new Date("2024-12-08"), // Another holiday
    //     // Add more holiday dates as needed
    // ];


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
                    <Modal.Title>Change status</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Status</label>

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
                                    {Object.keys(statusArr).map((s) => (
                                        <option key={s} value={s}>
                                            {statusArr[s]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        {formValues.status === "reschedule call" && (
                            <>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">Reschedule Date</label>
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
                                            // highlightDates={holidays} // Highlight holiday dates
                                            // dayClassName={(date) =>
                                            //     holidays.some((holiday) => holiday.toDateString() === date.toDateString())
                                            //         ? "holiday" // Optional: apply a custom class to holidays
                                            //         : undefined
                                            // }
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">Reschedule Time</label>
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
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Reason</label>

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
        </div>
    );
}
