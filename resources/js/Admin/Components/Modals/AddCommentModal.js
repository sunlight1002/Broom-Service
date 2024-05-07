import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import moment from "moment";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";

export default function AddCommentModal({
    setIsOpen,
    isOpen,
    relationID,
    routeType,
    onSuccess,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        comment: "",
        repeatancy: "until_date",
        valid_till: "",
    });
    const [isLoading, setIsLoading] = useState(false);
    const [minUntilDate, setMinUntilDate] = useState(null);

    let fileRef = useRef(null);
    const flatpickrRef = useRef(null);

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

            const formData = new FormData();
            formData.append("comment", formValues.comment);
            formData.append("valid_till", formValues.valid_till);

            if (fileRef.current && fileRef.current.files.length > 0) {
                for (
                    let index = 0;
                    index < fileRef.current.files.length;
                    index++
                ) {
                    const element = fileRef.current.files[index];
                    formData.append("files[]", element);
                }
            }

            axios
                .post(
                    `/api/admin/${routeType}/${relationID}/comments`,
                    formData,
                    {
                        headers,
                    }
                )
                .then((response) => {
                    Swal.fire(
                        "Added!",
                        "Comment Added successfully.",
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
                <Modal.Title>Add Comment</Modal.Title>
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

                    <div className="col-sm-12">
                        <div className="form-group">
                            <label htmlFor="files" className="form-label">
                                Upload files
                            </label>
                            <input
                                ref={fileRef}
                                className="form-control"
                                type="file"
                                id="files"
                                multiple
                            />
                        </div>
                    </div>

                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Repeatancy</label>

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
                                <option value="until_date">Until Date</option>
                                <option value="forever">Forever</option>
                            </select>
                        </div>
                    </div>

                    {formValues.repeatancy == "until_date" && (
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">
                                    Until Date
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
                                            valid_till: dateStr,
                                        });
                                    }}
                                    options={{
                                        disableMobile: true,
                                        minDate: minUntilDate,
                                    }}
                                    value={formValues.valid_till}
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
