import { useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";

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
        status: "pending",
        id: clientId,
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

        return true;
    };

    const handleSubmit = () => {
        let hasError = false;
        const valid = checkValidation();
        if (!valid) {
            hasError = true;
        }
        if (!hasError) {
            setIsLoading(true);

            const formData = new FormData();
            Object.keys(formValues).forEach((formKey) => {
                formData.append(formKey, formValues[formKey]);
            });
            axios
                .post(`/api/admin/client-status-log`, formData, {
                    headers,
                })
                .then(async (response) => {
                    Swal.fire("Added!", response?.data?.message, "success");
                    setIsLoading(false);
                    await getUpdatedData();
                    handleChangeStatusModalClose();
                })
                .catch((e) => {
                    Swal.fire({
                        title: "Error!",
                        text: e.response?.data?.message,
                        icon: "error",
                    });
                    setIsLoading(false);
                });
        }
    };

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
            { isLoading && <FullPageLoader visible={isLoading}/>}
        </div>
    );
}
