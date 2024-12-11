import "flatpickr/dist/flatpickr.css";
import moment from "moment";
import { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { Button, Modal } from "react-bootstrap";
import Flatpickr from "react-flatpickr";
import { useTranslation } from "react-i18next";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function SwitchWorkerModal({
    setIsOpen,
    isOpen,
    job,
    onSuccess,
}) {
    const { t } = useTranslation();
    const alert = useAlert();
    const [workers, setWorkers] = useState([]);
    const [singleJob, setSingleJob] = useState({});
    const [formValues, setFormValues] = useState({
        worker_id: "",
        repeatancy: "one_time",
        until_date: null,
        fee: "0",
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [isLoading, setIsLoading] = useState(false);

    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const checkValidation = () => {
        if (!formValues.worker_id) {
            alert.error("The worker is missing");
            return false;
        }

        if (!formValues.repeatancy) {
            alert.error("The Repeatancy is missing");
            return false;
        }

        if (formValues.repeatancy == "until_date" && !formValues.until_date) {
            alert.error("The Until Date is missing");
            return false;
        }
        return true;
    };

    const handleInputChange = (e) => {
        let newFormValues = { ...formValues };

        newFormValues[e.target.name] = e.target.value;

        setFormValues({ ...newFormValues });
    };

    const getAllWorkers = () => {
        axios
            .get(`/api/admin/all-workers`, {
                headers,
            })
            .then((response) => {
                setWorkers(response.data?.workers);
            });
    };


    const getJob = async() => {
       const res = await axios.get(`/api/admin/jobs/${job.id}`, { headers })
       console.log(res.data);
       
  
    };

    const handleSubmit = () => {
        let hasError = false;
        const valid = checkValidation();
        if (!valid) {
            hasError = true;
        }
        if (!hasError) {
            setIsLoading(true);
            axios
                .post(`/api/admin/jobs/${job.id}/switch-worker`, formValues, {
                    headers,
                })
                .then((response) => {
                    Swal.fire(
                        "Updated!",
                        "Worker switched successfully.",
                        "success"
                    );
                    setIsOpen(false);
                    setIsLoading(false);
                    onSuccess();
                })
                .catch((e) => {
                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                    setIsLoading(false);
                });
        }
    };

    useEffect(() => {
        getAllWorkers();
        getJob();
    }, []);


    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

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
                <Modal.Title>{t("global.switchWorker")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">{t("global.worker")}</label>
                            <select
                                name="worker_id"
                                className="form-control"
                                value={formValues.worker_id}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                <option value="">{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
                                {workers.map((w, i) => (
                                    <option value={w.id} key={i}>
                                        {w.firstname} {w.lastname}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">{t("global.repeatancy")}</label>

                            <select
                                name="repeatancy"
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        repeatancy: e.target.value,
                                    });
                                }}
                                value={formValues.repeatancy}
                                className="form-control mb-3"
                            >
                                <option value="one_time">
                                    {t("client.jobs.change.oneTime")}
                                </option>
                                <option value="until_date">{t("client.jobs.change.UntilDate")}</option>
                                <option value="forever">{t("client.jobs.change.Forever")}</option>
                            </select>
                        </div>
                    </div>

                    {formValues.repeatancy == "until_date" && (
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">
                                {t("client.jobs.change.UntilDate")}
                                </label>
                                <Flatpickr
                                    name="date"
                                    className="form-control"
                                    onChange={(
                                        selectedDates,
                                        dateStr,
                                        instance
                                    ) => {
                                        setFormValues({
                                            ...formValues,
                                            until_date: dateStr,
                                        });
                                    }}
                                    options={{
                                        disableMobile: true,
                                        minDate: minUntilDate,
                                    }}
                                    value={formValues.until_date}
                                    ref={flatpickrRef}
                                />
                            </div>
                        </div>
                    )}
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
    );
}
