import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import moment from "moment";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import { useTranslation } from "react-i18next";

export default function AddPaymentModal({
    setIsOpen,
    isOpen,
    clientId,
    onSuccess,
    handleAddNewCard,
}) {
    const { t } = useTranslation();
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
    const [maxAmount, setMaxAmount] = useState(0);

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
        // const pm = {
        //     cc: t("admin.leads.AddLead.Options.PaymentMethod.CreditCard"),
        //     mt: t("admin.leads.AddLead.Options.PaymentMethod.ByBanktransfer"),
        //     cash: t("admin.leads.AddLead.Options.PaymentMethod.ByCash"),
        //     cheque: t("admin.leads.AddLead.Options.PaymentMethod.ByCheque"),
        // };

        const mdata = {
            paid_amount: formValues.amount,
            pay_method: pm[formValues.method],
            txn_id: formValues.txn,
            status: "Paid",
        };
        let data = {};

        if (formValues.amount == "" || formValues.amount <= 0) {
            alert.error("Please enter amount");
            return false;
        }

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
            .post(`/api/admin/client/${clientId}/ucpdate-invoice`, data, {
                headers,
            })
            .then((response) => {
                setIsLoading(false);
                setIsOpen(false);
                onSuccess();
            })
            .catch((e) => {
                setIsLoading(false);

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                    showCancelButton: true,
                    confirmButtonText: "Add New Credit Card",
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleAddNewCard(clientId);
                    }
                });
            });
    };

    const getClientUnpaidInvoice = (_clientID) => {
        axios
            .get(`/api/admin/client/${_clientID}/unpaid-invoice`, {
                headers,
            })
            .then((response) => {
                setMaxAmount(response.data.total_unpaid_amount);
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
                <Modal.Title>{t("admin.leads.AddLead.Options.addPayment")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                            {t("admin.leads.AddLead.Options.amount")}{" "}
                                <small className="text-danger">
                                    (max - {maxAmount})
                                </small>
                            </label>
                            <input
                                type="number"
                                value={formValues.amount}
                                onChange={(e) =>
                                    setFormValues({
                                        ...formValues,
                                        amount: e.target.value,
                                    })
                                }
                                className="form-control"
                            ></input>
                        </div>
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                            {t("admin.leads.AddLead.Options.tandb")}
                                <small> ({t("admin.leads.AddLead.Options.optionalCCmode")})</small>
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
                                placeholder={t("admin.leads.AddLead.Options.tandb")}
                            ></input>
                        </div>
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                            {t("admin.leads.AddLead.Options.paymentMode")}
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
                                    {t("admin.leads.AddLead.Options.transferDate")}
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
                                    {t("admin.leads.AddLead.Options.account")}
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
                                        placeholder={t("admin.leads.AddLead.Options.accountIdPlaceholder")}
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
                                    {t("admin.leads.AddLead.Options.chequeDate")}
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
                                    {t("admin.leads.AddLead.Options.chequeBank")}
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
                                        placeholder={t("admin.leads.AddLead.Options.chequeBank")}
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                    {t("admin.leads.AddLead.Options.chequeBranch")}
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
                                        placeholder={t("admin.leads.AddLead.Options.chequeBranch")}
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                    {t("admin.leads.AddLead.Options.chequeAccount")}
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
                                        placeholder={t("admin.leads.AddLead.Options.chequeAccount")}
                                    ></input>
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                    {t("admin.leads.AddLead.Options.chequeNumber")}
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
                                        placeholder={t("admin.leads.AddLead.Options.chequeNumber")}
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
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    disabled={isLoading}
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>

            <FullPageLoader visible={isLoading} />
        </Modal>
    );
}
