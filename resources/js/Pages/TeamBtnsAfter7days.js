import React, { useState } from 'react'
import ChangeStatusModal from '../Admin/Components/Modals/ChangeStatusModal';
import logo from "../Assets/image/sample.svg";
import { useParams } from 'react-router-dom';
import { useTranslation } from "react-i18next";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";
import FullPageLoader from '../Components/common/FullPageLoader';



function TeamBtnsAfter7days() {
    const [isOpen, setIsOpen] = useState(false)
    const params = useParams();
    const [isLoading, setIsLoading] = useState(false);

    const { t } = useTranslation()

    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        reason: "",
        status: "uninterested",
        id: Base64.decode(params.id),
    });

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

    const handleDelete = async () => {
        try {
            setIsLoading(false);
            const response = await axios.delete(`/api/admin/client-meta/${Base64.decode(params.id)}`, {headers});
            Swal.fire("Reset!", response?.data?.message, "success");
        } catch (err) {
            Swal.fire({
                title: "Error!",
                text: e.response?.data?.message,
                icon: "error",
            });
            setIsLoading(false);
        }
    };
    return (

        <div className="container meeting" style={{ display: "block" }}>
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <div className="cta">
                    <div id="content">
                        <div className="titleBox customer-title">
                            <div className="row">
                                <div className="col-sm-6">
                                    <h1 className="page-title">
                                        Buttons:
                                    </h1>
                                </div>

                            </div>
                        </div>
                        {/* {job && (
                            <div className="comment-details mb-3">
                                <p>Details</p>
                                <p>Client: {job?.client?.firstname} {job?.client?.lastname}</p>
                                <p>Worker: {job?.worker?.firstname} {job?.client?.lastname}</p>
                                <p>Property Address: {job?.property_address?.geo_address}</p>
                            </div>
                        )} */}
                        <div className="card">
                            <div className="card-body d-flex justify-content-around align-items-center flex-wrap">

                                <div className="ml-2">
                                    <button
                                        className="btn btn-pink addButton mt-2"
                                        style={{ textTransform: "none", width: "13rem" }}
                                        type="button"
                                        onClick={() => setIsOpen(true)}
                                    >
                                        Mark as Uninterested
                                    </button>
                                </div>

                                <div className="ml-2">
                                    <button
                                        className="btn btn-pink addButton mt-2"
                                        style={{ textTransform: "none", width: "13rem" }}
                                        type="button"
                                        onClick={handleDelete}
                                    >
                                        Reset Reminder
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Change status to Uninterested</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        {/* <div className="col-sm-12">
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
                                </div> */}
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
            {isLoading && <FullPageLoader visible={isLoading}/>}
        </div>
    )
}

export default TeamBtnsAfter7days