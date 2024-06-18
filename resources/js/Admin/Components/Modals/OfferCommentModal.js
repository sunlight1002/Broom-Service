import { useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";

export default function OfferCommentModal({
    setIsOpen,
    isOpen,
    comment,
    onChange,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        comment: comment ? comment : "",
    });

    const checkValidation = () => {
        if (!formValues.comment) {
            alert.error("The comment is missing");
            return false;
        }

        return true;
    };

    const handleSubmit = () => {
        const valid = checkValidation();
        if (valid) {
            onChange(formValues.comment);
            setIsOpen(false);
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
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
