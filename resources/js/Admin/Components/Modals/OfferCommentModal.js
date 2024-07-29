import { useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";

export default function OfferCommentModal({
    setIsOpen,
    isOpen,
    comment,
    onChange,
}) {
    const { t } = useTranslation();
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
                <Modal.Title>{t("client.sidebar.contracts")} {t("worker.jobs.view.cmt")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">{t("worker.jobs.view.cmt")}</label>

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
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
