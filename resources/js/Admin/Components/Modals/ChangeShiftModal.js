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
    const [allClients, setAllClients] = useState([]);
    const [formValues, setFormValues] = useState({
        client_id: "",
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
        console.log(res.data.clients);
        
        setAllClients(res.data?.clients);
    }

    useEffect(() => {
      getClients();
    }, [selectedDate, selectedDateProp]);
    

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
                                                client_id: e.target.value,
                                            });
                                        }}
                                        value={formValues.client_id}
                                        className="form-control mb-3"
                                    >
                                        <option value="">--- Select Client ---</option>
                                        {allClients.map((client) => (
                                            <option key={client.id} value={client.id}>{client.client?.firstname + " " + client?.client.lastname}</option>
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
                    // onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>

            <FullPageLoader visible={isLoading} />
        </Modal>
    )
}
