import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";

export default function ManpowerCompanyModal({
    setIsOpen,
    isOpen,
    company,
    onSuccess,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        name: company ? company.name : "",
    });
    const [isLoading, setIsLoading] = useState(false);

    let fileRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const checkValidation = () => {
        if (!formValues.name) {
            alert.error("The name is missing");
            return false;
        }

        return true;
    };

    const handleSubmit = async () => {
        let hasError = false;
        const valid = checkValidation();
        if (!valid) {
            hasError = true;
        }
        if (!hasError) {
            setIsLoading(true);

            const formData = new FormData();
            formData.append("name", formValues.name);

            if (fileRef.current && fileRef.current.files.length > 0) {
                const element = fileRef.current.files[0];
                formData.append("file", element);
            }

            if (company) {
                await axios
                    .put(
                        `/api/admin/manpower-companies/${company.id}`,
                        formData,
                        {
                            headers,
                        }
                    )
                    .then((response) => {
                        if (response.data.errors) {
                            for (let e in response.data.errors) {
                                alert.error(response.data.errors[e]);
                            }
                        } else {
                            setIsOpen(false);
                            onSuccess();
                            Swal.fire(
                                "Updated!",
                                "Comment updated successfully.",
                                "success"
                            );
                        }
                        setIsLoading(false);
                    })
                    .catch((e) => {
                        console.log(e);
                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                        setIsLoading(false);
                    });
            } else {
                await axios
                    .post(`/api/admin/manpower-companies`, formData, {
                        headers,
                    })
                    .then((response) => {
                        if (response.data.errors) {
                            for (let e in response.data.errors) {
                                alert.error(response.data.errors[e]);
                            }
                        } else {
                            setIsOpen(false);
                            onSuccess();
                            Swal.fire(
                                "Added!",
                                "Comment added successfully.",
                                "success"
                            );
                        }
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
            }
        }
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
                <Modal.Title>
                    {company ? "Edit Manpower Company" : "Add Manpower Company"}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Name</label>

                            <input
                                type="text"
                                value={formValues.name}
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        name: e.target.value,
                                    });
                                }}
                                className="form-control"
                                required
                            />
                        </div>
                    </div>

                    <div className="col-sm-12">
                        <div className="form-group">
                            <label htmlFor="file" className="form-label">
                                Contract
                            </label>
                            <input
                                ref={fileRef}
                                className="form-control-file"
                                type="file"
                                id="file"
                                accept="application/pdf"
                            />
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
                    Close
                </Button>
                <Button
                    type="button"
                    disabled={isLoading}
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {company ? "Update" : "Add"}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
