import { useEffect, useState } from "react";
import OfferServiceForm from "../../Pages/OfferPrice/OfferServiceForm";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";

const initialValue = {
    service: "",
    name: "",
    type: "fixed",
    freq_name: "",
    frequency: "",
    fixed_price: "",
    jobHours: "",
    rateperhour: "",
    other_title: "",
    totalamount: "",
    template: "",
    cycle: "",
    period: "",
    address: "",
    start_date: "",
    weekdays: [],
    weekday_occurrence: "1",
    weekday: "sunday",
    month_occurrence: 1,
    month_date: 1,
    monthday_selection_type: "weekday",
};

function OfferServiceModal({
    setIsOpen,
    isOpen,
    addresses,
    services,
    frequencies,
    tmpFormValues,
    handleTmpValue,
    handleSaveForm,
    isAdd,
    editIndex,
}) {
    const alert = useAlert();
    const [noOfWorkers, setNoOfWorkers] = useState(1);
    const [offerserviceTmp, setOfferServiceTmp] = useState([
        isAdd ? initialValue : tmpFormValues,
    ]);

    useEffect(() => {
        if (isAdd) {
            const length = noOfWorkers - offerserviceTmp.length;
            const tmp = Array.from({ length: noOfWorkers }, () => initialValue);
            setOfferServiceTmp(tmp);
        }
    }, [noOfWorkers]);

    const handleTmp = (index, tmpvalue) => {
        const tmp = [...offerserviceTmp];
        tmp[index] = tmpvalue;
        setOfferServiceTmp(tmp);
    };

    const handleChangeWorker = (e) => {
        const no = e.target.value;
        if (no > 0) {
            setNoOfWorkers(no);
        } else {
            setNoOfWorkers(1);
        }
    };

    const checkValidation = (tmpFormValues) => {
        if (tmpFormValues.address == "") {
            alert.error("The address is not selected");
            return false;
        }
        if (tmpFormValues.service == "" || tmpFormValues.service == 0) {
            alert.error("The service is not selected");
            return false;
        }

        let ot = document.querySelector("#other_title");

        if (tmpFormValues.service == "10" && ot != undefined) {
            if (tmpFormValues.other_title == "") {
                alert.error("Other title cannot be blank");
                return false;
            }
            tmpFormValues.other_title =
                document.querySelector("#other_title").value;
        } else {
            tmpFormValues.other_title = "";
        }

        if (tmpFormValues.jobHours == "") {
            alert.error("The job hours value is missing");
            return false;
        }
        !tmpFormValues.type ? (tmpFormValues.type = "fixed") : "";
        if (tmpFormValues.type == "hourly") {
            if (tmpFormValues.rateperhour == "") {
                alert.error("The rate per hour value is missing");
                return false;
            }
        } else {
            if (tmpFormValues.fixed_price == "") {
                alert.error("The job price is missing");
                return false;
            }
        }

        if (tmpFormValues.frequency == "" || tmpFormValues.frequency == 0) {
            alert.error("The frequency is not selected");
            return false;
        } else {
            if (tmpFormValues.start_date == "") {
                alert.error("The Start date is not selected");
                return false;
            }

            if (tmpFormValues.cycle == "1") {
                if (
                    ["w", "2w", "3w", "4w", "5w"].includes(
                        tmpFormValues.period
                    ) &&
                    tmpFormValues.weekday == ""
                ) {
                    alert.error("The weekday is not selected");
                    return false;
                } else if (
                    tmpFormValues.monthday_selection_type == "weekday" &&
                    ["m", "2m", "3m", "6m", "y"].includes(
                        tmpFormValues.period
                    ) &&
                    tmpFormValues.weekday == ""
                ) {
                    alert.error("The weekday is not selected");
                    return false;
                }

                if (
                    tmpFormValues.monthday_selection_type == "date" &&
                    ["m", "2m", "3m", "6m", "y"].includes(tmpFormValues.period)
                ) {
                    if (tmpFormValues.month_date == "") {
                        alert.error("The month date is not selected");
                        return false;
                    } else if (
                        new Date(tmpFormValues.start_date).getDate() >
                        tmpFormValues.month_date
                    ) {
                        alert.error(
                            "The start date should be less than or equal to selected month date"
                        );
                        return false;
                    }
                }
            }

            if (
                tmpFormValues.period == "w" &&
                tmpFormValues.cycle != "0" &&
                tmpFormValues.cycle != "1" &&
                tmpFormValues.weekdays.length != tmpFormValues.cycle
            ) {
                alert.error("The frequency week-days are invalid");
                return false;
            }
        }

        return true;
    };

    const handleSubmit = () => {
        let hasError = false;
        for (let i = 0; i < offerserviceTmp.length; i++) {
            const valid = checkValidation(offerserviceTmp[i]);
            if (!valid) {
                hasError = true;
                break;
            }
        }
        if (!hasError) {
            handleSaveForm(isAdd ? "" : editIndex, offerserviceTmp);
            setIsOpen(false);
        }
    };
    return (
        <Modal
            size="lg"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
        >
            <Modal.Header closeButton>
                <Modal.Title>
                    {isAdd ? "Add Service" : "Edit Service"}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                {isAdd && (
                    <div className="d-flex  align-items-center mb-3">
                        <label htmlFor="noOfWrkers pe-2">
                            No of Workers :{" "}
                        </label>
                        <input
                            type="number"
                            min={1}
                            className="form-control w-25"
                            id="noOfWrkers"
                            defaultValue={1}
                            onChange={handleChangeWorker}
                        />
                    </div>
                )}

                {offerserviceTmp &&
                    offerserviceTmp.length > 0 &&
                    offerserviceTmp.map((_, index) => (
                        <OfferServiceForm
                            key={index}
                            addresses={addresses}
                            services={services}
                            frequencies={frequencies}
                            tmpFormValues={offerserviceTmp[index]}
                            handleTmpValue={handleTmp}
                            index={index}
                        />
                    ))}
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
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
}

export default OfferServiceModal;
