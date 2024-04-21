import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import moment from "moment";
import Swal from "sweetalert2";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";

export default function AddPaymentModal({
    setIsOpen,
    isOpen,
    jobId,
    clientId,
    onSuccess,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        amount: "",
        txn: "",
        method: "cc",
        date: "",
        account: "",
        bank: "",
        branch: "",
        number: "",
    });

    const [isLoading, setIsLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = () => {
        const pm = {
            cc: "Credit Card",
            mt: "Bank Transfer",
            cash: "Cash",
            cheque: "Cheque",
        };

        const mdata = {
            paid_amount: formValues.amount,
            pay_method: pm[formValues.method],
            txn_id: formValues.txn,
            status: "Paid",
        };
        let data = {};

        if (formValues.method == "mt") {
            if (formValues.date == "") {
                alert.error("Please select bank transfer date");
                return false;
            }

            if (formValues.account == "") {
                alert.error("please enter bank account");
                return false;
            }

            data = {
                ...mdata,
                date: formValues.date,
                account: formValues.account,
            };
        } else if (formValues.method == "cheque") {
            if (formValues.date == "") {
                alert.error("please select cheque date");
                return false;
            }
            if (formValues.bank == "") {
                alert.error("please enter cheque bank");
                return false;
            }
            if (formValues.branch == "") {
                alert.error("please enter cheque branch");
                return false;
            }
            if (formValues.account == "") {
                alert.error("please enter cheque account");
                return false;
            }
            if (formValues.number == "") {
                alert.error("please enter cheque number");
                return false;
            }

            data = {
                ...mdata,
                date: formValues.date,
                bank: formValues.bank,
                branch: formValues.branch,
                account: formValues.account,
                number: formValues.number,
            };
        } else {
            data = { ...mdata };
        }

        setIsLoading(true);
        axios
            .post(`/api/admin/client/${clientId}/update-invoice`, data, {
                headers,
            })
            .then((response) => {
                setIsOpen(false);
                onSuccess();
                setIsLoading(false);
            })
            .catch((e) => {
                setIsLoading(false);

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const getClientUnpaidInvoice = (_clientID) => {
        axios
            .get(`/api/admin/client/${_clientID}/unpaid-invoice`, {
                headers,
            })
            .then((response) => {
                setFormValues({
                    ...formValues,
                    amount: response.data.total_unpaid_amount,
                });
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    useEffect(() => {
        getClientUnpaidInvoice(clientId);
    }, [clientId]);

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
                <Modal.Title>Add Payment</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Amount</label>
                            <input
                                type="number"
                                value={formValues.amount}
                                className="form-control"
                                readOnly
                            ></input>
                        </div>
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                Transaction / Reference ID
                                <small> (Optional in credit card mode)</small>
                            </label>
                            <input
                                type="text"
                                value={formValues.txn}
                                onChange={(e) =>
                                    setFormValues({
                                        ...formValues,
                                        txn: e.target.value,
                                    })
                                }
                                className="form-control"
                                required
                                placeholder="Enter Transaction / Reference ID"
                            ></input>
                        </div>
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                Payment Mode
                            </label>
                            <select
                                name="mode"
                                className="form-control"
                                value={formValues.method}
                                onChange={(e) =>
                                    setFormValues({
                                        ...formValues,
                                        method: e.target.value,
                                    })
                                }
                            >
                                <option value="mt">Bank Transfer</option>
                                <option value="cash">By Cash</option>
                                <option value="cc">Credit Card</option>
                                <option value="cheque">By Cheque</option>
                            </select>
                        </div>
                    </div>
                </div>
                {formValues.method == "mt" && (
                    <div>
                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Bank Transfer Date
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={formValues.date}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                date: e.target.value,
                                            })
                                        }
                                        required
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Account
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control ba"
                                        value={formValues.account}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                account: e.target.value,
                                            })
                                        }
                                        placeholder="Bank account ID where BankTransfer was deposited"
                                        required
                                    ></input>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {formValues.method == "cheque" && (
                    <div>
                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Cheque Date
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={formValues.date}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                date: e.target.value,
                                            })
                                        }
                                        required
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Cheque Bank
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={formValues.bank}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                bank: e.target.value,
                                            })
                                        }
                                        required
                                        placeholder="Cheque Bank"
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Cheque Branch
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={formValues.branch}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                branch: e.target.value,
                                            })
                                        }
                                        required
                                        placeholder="Cheque Branch"
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Cheque account
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={formValues.account}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                account: e.target.value,
                                            })
                                        }
                                        required
                                        placeholder="Cheque account"
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Cheque number
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={formValues.number}
                                        onChange={(e) =>
                                            setFormValues({
                                                ...formValues,
                                                number: e.target.value,
                                            })
                                        }
                                        required
                                        placeholder="Cheque number"
                                    ></input>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
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
