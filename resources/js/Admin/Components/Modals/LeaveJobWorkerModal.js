import { useEffect, useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import DatePicker from "react-datepicker";
import * as moment from "moment";

export default function LeaveJobWorkerModal({ setIsOpen, isOpen, workerId }) {
    const alert = useAlert();
 
    const [isLoading, setIsLoading] = useState(false);
    const [selectedDate, setSelectedDate] = useState();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getWorker = () => {
        axios
            .get(`/api/admin/workers/${workerId}/edit`, {
                headers,
            })
            .then((response) => {
                if (response.data.worker) {
                    const _worker = response.data.worker;

                    if (_worker.last_work_date) {
                        setSelectedDate(new Date(_worker.last_work_date));
                    }
                }
            });
    };

    const handleSubmit = () => {
        setIsLoading(true);
        axios
            .post(
                `/api/admin/workers/${workerId}/leave-job`,
                {
                    date: selectedDate ? moment(selectedDate).format("YYYY-MM-DD") : null,
                },
                {
                    headers,
                }
            )
            .then((response) => {
                Swal.fire(
                    "Updated!",
                    "Leave job date has been updated.",
                    "success"
                );
                setIsOpen(false);
                setIsLoading(false);
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
                setIsLoading(false);
            });
    };

    const handleDateChange = (_date) => {
        setSelectedDate(_date);
    };

    useEffect(() => {
        getWorker();
    }, [workerId]);

    return (
        <Modal
            size="md"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
            backdrop="static"
        >
            <Modal.Header closeButton>
                <Modal.Title>Leave Job</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-6">
                        <div className="form-group">
                            <label className="control-label">
                                Last working date
                            </label>
                            <DatePicker
                                selected={selectedDate}
                                onChange={(date) => handleDateChange(date)}
                                className="form-control"
                                minDate={new Date()}
                                shouldCloseOnSelect={false}
                            />
                        </div>
                    </div>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <Button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                        setIsOpen(false);
                    }}
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
    );
}
