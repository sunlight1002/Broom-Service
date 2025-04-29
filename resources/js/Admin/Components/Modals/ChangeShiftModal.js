import "flatpickr/dist/flatpickr.css";
import moment from "moment";
import { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { Button, Modal } from "react-bootstrap";
import Flatpickr from "react-flatpickr";
import { useTranslation } from "react-i18next";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import axios from "axios";

export const ChangeShiftModal = ({
    isOpen,
    setIsOpen,
    job,
    selectedDate : selectedDateProp
}) => {
    const { t } = useTranslation();
    const alert = useAlert();
    const [isLoading, setIsLoading] = useState(false);
    const [allJobs, setAllJobs] = useState([]);
    const [formValues, setFormValues] = useState({
        job_id: "",
    });

    const parseDateString = (dateStr) => {
        if (!dateStr) return new Date();
        
        // Parse DD/MM/YY format with moment and convert to Date object
        return moment(dateStr, "DD/MM/YY").toDate();
      };
    
    const [selectedDate, setSelectedDate] = useState(parseDateString(selectedDateProp));


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleDateChange = async(_date) => {
        setSelectedDate(_date);      
    };

    const getClients = async() => {
        const res = await axios.get(`/api/admin/jobs/get-shift-client/${job}/${moment(selectedDate).format("YYYY-MM-DD")}`, { headers });
        setAllJobs(res.data?.jobs);
    }

    useEffect(() => {
      getClients();
    }, [selectedDate, selectedDateProp]);

    const handleSubmit = () => {
        setIsLoading(true);
        axios
            .post(
                `/api/admin/jobs/${job}/change-client-shift/${formValues.job_id}`, 
                null,
                {
                    headers,
                }
            )
            .then((response) => {
                Swal.fire(
                    "Updated!",
                    "Shift has been updated.",
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
                <Modal.Title>{t("global.change_shift")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group d-flex flex-column">
                            <label className="control-label">{t("global.select_date_to_change")}</label>
                            <DatePicker
                                selected={selectedDate}
                                onChange={(date) => handleDateChange(date)}
                                className="form-control"
                                minDate={new Date()}
                                shouldCloseOnSelect={false}
                                dateFormat="dd-MM-yyyy" // Set display format here

                            />
                        </div>
                    </div>

                    {
                        selectedDate && (
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">{t("global.select_client")}</label>

                                    <select
                                        name="repeatancy"
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                job_id: e.target.value,
                                            });
                                        }}
                                        value={formValues.client_id}
                                        className="form-control mb-3"
                                    >
                                        <option value="">{t("admin.global.select_client")}</option>
                                        {allJobs.map((job) => (
                                            <option key={job.id} value={job.id}>{job.client?.firstname + " " + job?.client.lastname + " (" + job?.offer_service?.address?.address_name + ")"} </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        )
                    }
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
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    disabled={isLoading}
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>

            <FullPageLoader visible={isLoading} />
        </Modal>
    )
}
