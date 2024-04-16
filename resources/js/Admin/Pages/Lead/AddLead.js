import React, { useState, useEffect } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import axios from "axios";
import { MultiSelect } from "react-multi-select-component";
import Select from "react-select";
import { create } from "lodash";
import PropertyAddress from "../../Components/Leads/PropertyAddress";
import { useTranslation } from "react-i18next";

export default function AddLead() {
    const [firstname, setFirstName] = useState("");
    const [lastname, setLastName] = useState("");
    const [email, setEmail] = useState("");
    const [invoiceName, setInvoiceName] = useState("");
    const [phone, setPhone] = useState("");
    const [dob, setDob] = useState("");
    const [passcode, setPassCode] = useState("");
    const [lng, setLng] = useState("");
    const [color, setColor] = useState("");
    const [status, setStatus] = useState(0);
    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const [paymentMethod, setPaymentMethod] = useState("cc");
    const navigate = useNavigate();
    const { t } = useTranslation();
    const [addresses, setAddresses] = useState([]);

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
                        firstname: firstname,
                        lastname: lastname == null ? "" : lastname,
                        invoicename: invoiceName ? invoiceName : "",
                        dob: dob,
                        passcode: passcode,
                        lng: lng ? lng : "heb",
                        color: !color ? "#fff" : color,
                        email: email,
                        phone: phoneClc,
                        password: passcode,
                        payment_method: paymentMethod,
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
                                                value={firstname}
                                                onChange={(e) =>
                                                    setFirstName(e.target.value)
                                                }
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
                                                value={lastname}
                                                onChange={(e) =>
                                                    setLastName(e.target.value)
                                                }
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
                                                value={invoiceName}
                                                onChange={(e) =>
                                                    setInvoiceName(
                                                        e.target.value
                                                    )
                                                }
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
                                                value={email}
                                                onChange={(e) =>
                                                    setEmail(e.target.value)
                                                }
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
                                                value={passcode}
                                                onChange={(e) =>
                                                    setPassCode(e.target.value)
                                                }
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
                                                value={phone}
                                                name={"phone"}
                                                onChange={(e) =>
                                                    setPhone(e.target.value)
                                                }
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
                                        value={dob}
                                        onChange={(e) => setDob(e.target.value)}
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
                                        {t("admin.leads.AddLead.PaymentMethod")}
                                    </label>

                                    <select
                                        className="form-control"
                                        value={paymentMethod}
                                        onChange={(e) => {
                                            setPaymentMethod(e.target.value);
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
                                        value={lng}
                                        onChange={(e) => {
                                            setLng(e.target.value);
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
                                    <div className="swatch white">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_2"
                                            value="0"
                                            color="#fff"
                                            onChange={(e) => setColor("#fff")}
                                        />
                                        <label htmlFor="swatch_2">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.white")}
                                        </span>
                                    </div>
                                    <div className="swatch green">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_7"
                                            value="2"
                                            color="#28a745"
                                            onChange={(e) =>
                                                setColor("#28a745")
                                            }
                                        />
                                        <label htmlFor="swatch_7">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Green")}
                                        </span>
                                    </div>
                                    <div className="swatch blue">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_3"
                                            value="3"
                                            color="#007bff"
                                            onChange={(e) =>
                                                setColor("#007bff")
                                            }
                                        />
                                        <label htmlFor="swatch_3">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Blue")}
                                        </span>
                                    </div>
                                    <div className="swatch purple">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_1"
                                            value="1"
                                            color="#6f42c1"
                                            onChange={(e) =>
                                                setColor("#6f42c1")
                                            }
                                        />
                                        <label htmlFor="swatch_1">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Voilet")}
                                        </span>
                                    </div>
                                    <div className="swatch red">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_5"
                                            value="5"
                                            color="#dc3545"
                                            onChange={(e) =>
                                                setColor("#dc3545")
                                            }
                                        />
                                        <label htmlFor="swatch_5">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Red")}
                                        </span>
                                    </div>
                                    <div className="swatch orange">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_4"
                                            value="4"
                                            color="#fd7e14"
                                            onChange={(e) =>
                                                setColor("#fd7e14")
                                            }
                                        />
                                        <label htmlFor="swatch_4">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>
                                            {t("admin.leads.AddLead.Orange")}
                                        </span>
                                    </div>
                                    <div className="swatch yellow">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_6"
                                            value="6"
                                            color="#ffc107"
                                            onChange={(e) =>
                                                setColor("#ffc107")
                                            }
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
