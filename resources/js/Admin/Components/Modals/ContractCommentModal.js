import { useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";

export default function ContractCommentModal({
    setIsOpen,
    isOpen,
    contract,
    onSuccess,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        comment: contract.comment ? contract.comment : "",
    });
    const [isLoading, setIsLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const checkValidation = () => {
        if (!formValues.comment) {
            alert.error("The comment is missing");
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

            axios
                .post(
                    `/api/admin/contracts/${contract.id}/comment`,
                    {
                        comment: formValues.comment,
                    },
                    {
                        headers,
                    }
                )
                .then(async (response) => {
                    Swal.fire("Saved!", "Comment saved", "success");
                    setIsLoading(false);
                    onSuccess();
                    setIsOpen(false);
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

    return (
        <Modal
            size="md"
            className="modal-container"
            show={isOpen}
            onHide={() => setIsOpen(false)}
            backdrop="static"
        >
            <Modal.Header closeButton>
                <Modal.Title>Contract Comment</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Comment</label>

                            <textarea
                                type="text"
                                value={formValues.comment}
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        comment: e.target.value,
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
                    onClick={() => setIsOpen(false)}
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
