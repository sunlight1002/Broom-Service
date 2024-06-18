import React, { useState, useEffect } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import axios from "axios";
import PropertyAddress from "../../Components/Leads/PropertyAddress";
import { useTranslation } from "react-i18next";

export default function AddLead() {
    const [status, setStatus] = useState(0);
    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const [addresses, setAddresses] = useState([]);
    const [formValues, setFormValues] = useState({
        firstname: "",
        lastname: "",
        email: "",
        invoicename: "",
        phone: "",
        dob: "",
        passcode: "",
        lng: "",
        color: "",
        vat_number: "",
        payment_method: "cc",
    });

    const navigate = useNavigate();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        var phoneClc = "";
        var phones = document.querySelectorAll(".pphone");
        phones.forEach((p, i) => {
            phoneClc += p.value + ",";
        });
        phoneClc = phoneClc.replace(/,\s*$/, "");
        axios
            .post(
                `/api/admin/leads`,
                {
                    data: {
                        firstname: formValues.firstname,
                        lastname:
                            formValues.lastname == null
                                ? ""
                                : formValues.lastname,
                        invoicename: formValues.invoicename
                            ? formValues.invoicename
                            : "",
                        dob: formValues.dob,
                        passcode: formValues.passcode,
                        lng: formValues.lng ? formValues.lng : "heb",
                        color: !formValues.color ? "#fff" : formValues.color,
                        email: formValues.email,
                        phone: phoneClc,
                        password: formValues.passcode,
                        payment_method: formValues.payment_method,
                        vat_number: formValues.vat_number,
                        extra: JSON.stringify(extra),
                        status: !status ? 0 : parseInt(status),
                        meta: "",
                    },
                    propertyAddress: addresses,
                },
                { headers }
            )
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success("Lead has been created successfully");
                    setTimeout(() => {
                        navigate("/admin/leads");
                    }, 1000);
                }
            });
    };

    const handleAlternate = (i, e) => {
        let extraValues = [...extra];
        extraValues[i][e.target.name] = e.target.value;
        setExtra(extraValues);
    };

    let addExtras = (e) => {
        e.preventDefault();
        setExtra([
            ...extra,
            {
                email: "",
                name: "",
                phone: "",
            },
        ]);
    };

    let removeExtras = (e, i) => {
        e.preventDefault();
        let extraValues = [...extra];
        extraValues.splice(i, 1);
        setExtra(extraValues);
    };
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">
                        {t("admin.leads.AddLead.AddLead")}
                    </h1>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "admin.leads.AddLead.FirstName"
                                                )}{" "}
                                                *
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.firstname}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        firstname:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                required
                                                placeholder={t(
                                                    "admin.leads.AddLead.placeHolder.FirstName"
                                                )}
                                            />
                                            {errors.firstname ? (
                                                <small className="text-danger mb-1">
                                                    {errors.firstname}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "admin.leads.AddLead.LastName"
                                                )}
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.lastname}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        lastname:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                required
                                                placeholder={t(
                                                    "admin.leads.AddLead.placeHolder.LastName"
                                                )}
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "admin.leads.AddLead.InvoiceName"
                                                )}
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.invoicename}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        invoicename:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                required
                                                placeholder={t(
                                                    "admin.leads.AddLead.placeHolder.InvoiceName"
                                                )}
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "admin.leads.AddLead.PrimaryEmail"
                                                )}{" "}
                                                *
                                            </label>
                                            <input
                                                type="email"
                                                value={formValues.email}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        email: e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                required
                                                placeholder={t(
                                                    "admin.leads.AddLead.placeHolder.PrimaryEmail"
                                                )}
                                            />
                                            {errors.email ? (
                                                <small className="text-danger mb-1">
                                                    {errors.email}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "admin.leads.AddLead.Password"
                                                )}
                                            </label>
                                            <input
                                                type="password"
                                                value={formValues.passcode}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        passcode:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                required
                                                placeholder={t(
                                                    "admin.leads.AddLead.placeHolder.Password"
                                                )}
                                                autoComplete="new-password"
                                            />
                                            {errors.passcode ? (
                                                <small className="text-danger mb-1">
                                                    {errors.passcode}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-sm-6 phone">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "admin.leads.AddLead.PrimaryPhone"
                                                )}
                                            </label>
                                            <input
                                                type="tel"
                                                value={formValues.phone}
                                                name={"phone"}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        phone: e.target.value,
                                                    });
                                                }}
                                                className="form-control pphone"
                                                placeholder={t(
                                                    "admin.leads.AddLead.placeHolder.PrimaryPhone"
                                                )}
                                            />
                                            {errors.phone ? (
                                                <small className="text-danger mb-1">
                                                    {errors.phone}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>

                                    {extra &&
                                        extra.map((ex, i) => {
                                            return (
                                                <React.Fragment key={i}>
                                                    <div className="col-sm-4">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                {t(
                                                                    "admin.leads.AddLead.AlternateEmail"
                                                                )}
                                                            </label>
                                                            <input
                                                                type="tel"
                                                                value={
                                                                    ex.email ||
                                                                    ""
                                                                }
                                                                name="email"
                                                                onChange={(e) =>
                                                                    handleAlternate(
                                                                        i,
                                                                        e
                                                                    )
                                                                }
                                                                className="form-control"
                                                                placeholder={t(
                                                                    "admin.leads.AddLead.placeHolder.AlternateEmail"
                                                                )}
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="col-sm-4">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                {t(
                                                                    "admin.leads.AddLead.PersonName"
                                                                )}
                                                            </label>
                                                            <input
                                                                type="tel"
                                                                value={
                                                                    ex.name ||
                                                                    ""
                                                                }
                                                                name="name"
                                                                onChange={(e) =>
                                                                    handleAlternate(
                                                                        i,
                                                                        e
                                                                    )
                                                                }
                                                                className="form-control"
                                                                placeholder={t(
                                                                    "admin.leads.AddLead.placeHolder.PersonName"
                                                                )}
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="col-sm-3">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                {t(
                                                                    "admin.leads.AddLead.AlternatePhone"
                                                                )}
                                                            </label>
                                                            <input
                                                                type="tel"
                                                                value={
                                                                    ex.phone ||
                                                                    ""
                                                                }
                                                                name="phone"
                                                                onChange={(e) =>
                                                                    handleAlternate(
                                                                        i,
                                                                        e
                                                                    )
                                                                }
                                                                className="form-control"
                                                                placeholder={t(
                                                                    "admin.leads.AddLead.placeHolder.AlternatePhone"
                                                                )}
                                                            />
                                                        </div>
                                                    </div>
                                                    <div className="col-sm-1">
                                                        {i == 0 ? (
                                                            <>
                                                                <button
                                                                    className="mt-25 btn btn-success"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        addExtras(
                                                                            e
                                                                        );
                                                                    }}
                                                                >
                                                                    {" "}
                                                                    +{" "}
                                                                </button>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <button
                                                                    className="mt-25 btn bg-red"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        removeExtras(
                                                                            e,
                                                                            i
                                                                        );
                                                                    }}
                                                                >
                                                                    {" "}
                                                                    <i className="fa fa-minus"></i>{" "}
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </React.Fragment>
                                            );
                                        })}
                                </div>

                                <PropertyAddress
                                    heading={t(
                                        "admin.leads.AddLead.propertyAddress"
                                    )}
                                    errors={errors}
                                    setErrors={setErrors}
                                    addresses={addresses}
                                    setAddresses={setAddresses}
                                />

                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.leads.AddLead.DOB")}
                                    </label>
                                    <input
                                        type="date"
                                        value={formValues.dob}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                dob: e.target.value,
                                            });
                                        }}
                                        className="form-control"
                                    />
                                    {errors.dob ? (
                                        <small className="text-danger mb-1">
                                            {errors.dob}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        ID/VAT Number
                                    </label>

                                    <input
                                        type="text"
                                        value={formValues.vat_number}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                vat_number: e.target.value,
                                            });
                                        }}
                                        className="form-control"
                                        required
                                        placeholder="Enter ID/VAT Number"
                                    />
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.leads.AddLead.PaymentMethod")}
                                    </label>

                                    <select
                                        className="form-control"
                                        value={formValues.payment_method}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                payment_method: e.target.value,
                                            });
                                        }}
                                    >
                                        <option value="cc">
                                            {t(
                                                "admin.leads.AddLead.Options.PaymentMethod.CreditCard"
                                            )}
                                        </option>
                                        <option value="mt">
                                            {t(
                                                "admin.leads.AddLead.Options.PaymentMethod.MoneyTransfer"
                                            )}
                                        </option>
                                        <option value="cheque">
                                            {t(
                                                "admin.leads.AddLead.Options.PaymentMethod.ByCheque"
                                            )}
                                        </option>
                                        <option value="cash">
                                            {t(
                                                "admin.leads.AddLead.Options.PaymentMethod.ByCash"
                                            )}
                                        </option>
                                    </select>
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.leads.AddLead.Language")}
                                    </label>

                                    <select
                                        className="form-control"
                                        value={formValues.lng}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                lng: e.target.value,
                                            });
                                        }}
                                    >
                                        <option value="heb">Hebrew</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <div
                                        className="form-check form-check-inline1 pl-0"
                                        style={{ paddingLeft: "0" }}
                                    >
                                        <label
                                            className="form-check-label"
                                            htmlFor="title"
                                        >
                                            {t("admin.leads.AddLead.Color")}
                                        </label>
                                    </div>
                                    <div className="swatch white mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_2"
                                            value="0"
                                            color="#fff"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#fff",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_2">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.white")}
                                        </span>
                                    </div>
                                    <div className="swatch green mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_7"
                                            value="2"
                                            color="#28a745"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#28a745",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_7">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Green")}
                                        </span>
                                    </div>
                                    <div className="swatch blue mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_3"
                                            value="3"
                                            color="#007bff"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#007bff",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_3">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Blue")}
                                        </span>
                                    </div>
                                    <div className="swatch purple mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_1"
                                            value="1"
                                            color="#6f42c1"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#6f42c1",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_1">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Voilet")}
                                        </span>
                                    </div>
                                    <div className="swatch red mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_5"
                                            value="5"
                                            color="#dc3545"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#dc3545",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_5">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Red")}
                                        </span>
                                    </div>
                                    <div className="swatch orange mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_4"
                                            value="4"
                                            color="#fd7e14"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#fd7e14",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_4">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Orange")}
                                        </span>
                                    </div>
                                    <div className="swatch yellow mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_6"
                                            value="6"
                                            color="#ffc107"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    color: "#ffc107",
                                                });
                                            }}
                                        />
                                        <label htmlFor="swatch_6">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Yellow")}
                                        </span>
                                    </div>

                                    {errors.color ? (
                                        <small className="text-danger mb-1">
                                            {errors.color}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group text-center">
                                    <input
                                        type="submit"
                                        onClick={handleFormSubmit}
                                        className="btn btn-pink saveBtn"
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
