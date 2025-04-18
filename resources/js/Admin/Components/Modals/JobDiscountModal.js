import { useEffect, useMemo, useState, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import axios from "axios";
import { useTranslation } from "react-i18next";

export default function JobDiscountModal({
    setIsOpen,
    isOpen,
    job,
    onSuccess,
}) {
    const { t } = useTranslation();
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        discount_type: job.discount_type ?? "fixed",
        discount_value: job.discount_value ?? 0,
        discount_comment: job.discount_comment ?? "",
    });
    const [loading, setLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSave = () => {
        if (!formValues.discount_type) {
            alert.error("The Discount type is missing");
            return false;
        }

        if (!formValues.discount_value) {
            alert.error("The Discount value is missing");
            return false;
        }

        setLoading(true);

        axios
            .post(`/api/admin/jobs/${job.id}/discount`, formValues, { headers })
            .then((response) => {
                setLoading(false);
                alert.success("Job discount saved successfully");
                onSuccess();
            })
            .catch((e) => {
                setLoading(false);
                alert.error(e.response.data.message);
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
        >
            <Modal.Header closeButton>
                <Modal.Title>{t("admin.global.jobDiscount")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                {t("admin.global.discountType")}
                            </label>

                            <select
                                name="discount_type"
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        discount_type: e.target.value,
                                    });
                                }}
                                value={formValues.discount_type}
                                className="form-control mb-3"
                            >
                                <option value="">--- Please Select ---</option>
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>

                        <div className="form-group">
                            <label className="control-label">
                                {t("admin.global.discountValue")}
                            </label>

                            <input
                                type="number"
                                name="discount_value"
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        discount_value: e.target.value,
                                    });
                                }}
                                value={formValues.discount_value}
                                className="form-control mb-3"
                            />
                        </div>

                        <div className="form-group">
                            <label className="control-label">
                                Comment
                            </label>
                            <textarea
                                type="text"
                                value={formValues.discount_comment}
                                name="discount_comment"
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        discount_comment: e.target.value,
                                    });
                                }}
                                className="form-control"
                                required
                                placeholder="Enter Note"
                            ></textarea>
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
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    onClick={handleSave}
                    className="btn btn-primary"
                    disabled={loading}
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
