import React, { useState, useEffect } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import axios from "axios";
import PropertyAddress from "../../Components/Leads/PropertyAddress";
import { useTranslation } from "react-i18next";
import { IoSaveOutline } from "react-icons/io5";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
// import i18next from "i18next";


export default function AddLead() {
    const [status, setStatus] = useState(0);
    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const [loading, setLoading] = useState(false);
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const [addresses, setAddresses] = useState([]);
    const {t} = useTranslation()
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
        send_bot_message: true,
        payment_method: "cc",
        notification_type: "both",
    });

    const navigate = useNavigate();


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        // var phoneClc = "";
        // var phones = document.querySelectorAll(".pphone");
        // phones.forEach((p, i) => {
        //     phoneClc += p.value + ",";
        // });
        // phoneClc = phoneClc.replace(/,\s*$/, "");
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
                        phone: formValues.phone,
                        password: formValues.passcode,
                        payment_method: formValues.payment_method,
                        notification_type: formValues.notification_type,
                        vat_number: formValues.vat_number,
                        extra: JSON.stringify(extra),
                        status: !status ? 0 : parseInt(status),
                        meta: "",
                        send_bot_message: formValues.send_bot_message,
                    },
                    propertyAddress: addresses,
                },
                { headers }
            )
            .then((response) => {
                setLoading(false);
                if (response.data.errors) {
                    setLoading(false);
                    setErrors(response.data.errors);
                } else {
                    setLoading(false);
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

    const handleAlternatePhone = (i, value) => {
        let extraValues = [...extra];
        extraValues[i].phone = value;
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

    // console.log(formValues?.lng);
    
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                <form>
                    <div className="d-flex align-items-center justify-content-between">
                        <h1 className="page-title addEmployer"
                        style={{border: "none"}}
                        >
                            {t("admin.leads.AddLead.AddLead")}
                        </h1>
                        <div className="text-center">
                            <button
                                type="submit"
                                onClick={handleFormSubmit}
                                className="btn navyblue d-flex align-items-center saveBtn"
                                style={{paddingLeft: "20px", paddingRight: "20px"}}
                                // value="Save"
                            ><IoSaveOutline className="mr-2"/>{t("admin.leads.save")}</button>
                            {/* <input
                                type="submit"
                                onClick={handleFormSubmit}
                                className="btn navyblue saveBtn"
                                value="Clear"
                            /> */}
                        </div>
                    </div>
                        <div className="container-box d-flex justify-content-between">
                            <div className="card-item mr-3 resMarginRight" style={{background: "#FAFBFC"}}>
                                <div className="card-heading">
                                    <p style={{ margin: "20px 34px 9px", fontSize: "20px" }} className="navyblueColor">{t("admin.leads.AddLead.General_Information")}</p>
                                </div>
                                <div className="card-body d-flex">
                                    <div className="col">
                                        <div className="form-group d-flex align-items-center w-100">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
                                            {t("admin.leads.AddLead.Notification_Type")}
                                            </label>

                                            <select
                                                className="form-control"
                                                value={formValues.notification_type}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        notification_type: e.target.value,
                                                    });
                                                }}
                                            >
                                                <option value="both">{t("admin.leads.AddLead.both")}</option>
                                                <option value="email">
                                                {t("admin.leads.AddLead.email")}
                                                </option>
                                                <option value="whatsapp">
                                                {t("admin.leads.AddLead.whatsapp")}
                                                </option>
                                            </select>
                                        </div>
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "14.4rem"}}>
                                                {t(
                                                    "admin.leads.AddLead.FirstName"
                                                )}{" "}
                                                *
                                            </label>
                                            <div className="d-flex flex-column w-100">
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
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
                                                <option value="heb">{t("admin.leads.AddLead.hebrew")}</option>
                                                <option value="en">{t("admin.leads.AddLead.english")}</option>
                                            </select>
                                        </div>
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
                                                {t(
                                                    "admin.leads.AddLead.SendWPBotMessage"
                                                )}
                                            </label>
                                            <input
                                                type="checkbox"
                                                value={formValues.send_bot_message}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        send_bot_message:
                                                            e.target.checked,
                                                    });
                                                }}
                                            />
                                        </div>
                                    </div>
                                    <div className="col">
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
                                            {t(
                                                    "admin.leads.AddLead.PrimaryPhone"
                                            )}{" "}
                                            *
                                            </label>
                                            <div className="d-flex flex-column w-100">
                                                <PhoneInput
                                                    country={'il'}
                                                    value={formValues.phone}
                                                    onChange={(phone) => {
                                                        setFormValues({
                                                            ...formValues,
                                                            phone: phone,
                                                        });
                                                    }}
                                                    inputClass="form-control"
                                                    inputProps={{
                                                        name: 'phone',
                                                        required: true,
                                                        placeholder: t("admin.leads.AddLead.placeHolder.PrimaryPhone"),
                                                    }}
                                                />
                                                {errors.phone && (
                                                    <small className="text-danger mb-1">
                                                        {errors.phone}
                                                    </small>
                                                )}
                                            </div>
                                        </div>
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
                                                {t(
                                                    "admin.leads.AddLead.PrimaryEmail"
                                                )}{" "}
                                                
                                            </label>
                                            <div className="d-flex flex-column w-100">

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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{width: "15rem"}}>
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
                                </div>
                                <div className="form-group color d-flex align-items-center ml-5" >
                                    <div
                                        className="form-check form-check-inline1 pl-0"
                                        style={{ paddingLeft: "0" }}
                                    >
                                        <label
                                            className="form-check-label navyblueColor"
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
                                        <label htmlFor="swatch_7 ">
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
                            </div>
                            <div className="card-item additional_contract_box" style={{background: "#FAFBFC"}}>
                                <div className="card-heading">
                                <p style={{ margin: "20px 20px 9px", fontSize: "20px" }} className="navyblueColor">{t("admin.leads.AddLead.Additional_Contacts")}</p>
                                </div>
                                <div className="card-body d-flex flex-column">
                                    {extra &&
                                        extra.map((ex, i) => {
                                            return (
                                                <React.Fragment key={i}>
                                                <div className="d-flex flex-wrap">
                                                    <div className="">
                                                        <div className="form-group" style={{marginRight: "6px"}}>
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

                                                    <div className="">
                                                        <div className="form-group" style={{marginRight: "6px"}}>
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

                                                    <div className="">
                                                        <div className="form-group" style={{marginRight: "6px"}}>
                                                            <label className="control-label">
                                                                {t(
                                                                    "admin.leads.AddLead.AlternatePhone"
                                                                )}
                                                            </label>
                                                            <PhoneInput
                                                                country={'il'}
                                                                value={ex.phone || ""}
                                                                onChange={(value) => handleAlternatePhone(i, value)}
                                                                inputClass="form-control"
                                                                inputProps={{
                                                                    name: 'phone',
                                                                    required: true,
                                                                    placeholder: t("admin.leads.AddLead.placeHolder.AlternatePhone"),
                                                                }}
                                                            />
                                                        </div>
                                                    </div>
                                                    <div className="">
                                                        {i == 0 ? (
                                                            <>
                                                                <button
                                                                style={{fontSize: "24px", color: "#2F4054",  padding: "1px 9px", background: "#E5EBF1", borderRadius: "5px"}}
                                                                    className="mt-25 btn"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        addExtras(
                                                                            e
                                                                        );
                                                                    }}
                                                                >
                                                                    {" "}
                                                                    <i className="fa fa-plus" ></i>{" "}
                                                                </button>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <button
                                                                style={{fontSize: "24px", color: "#2F4054",  padding: "1px 9px", background: "#E5EBF1", borderRadius: "5px"}}
                                                                    className="mt-25 btn"
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
                                                    </div>
                                                </React.Fragment>
                                            );
                                        })}
                                </div>
                            </div>
                        </div>
                        <div className="property-container">
                            <PropertyAddress
                                heading={t(
                                    "admin.leads.AddLead.propertyAddress"
                                )}
                                errors={errors}
                                setErrors={setErrors}
                                addresses={addresses}
                                setAddresses={setAddresses}
                                language={formValues.lng}
                            />
                        </div>
                    </form>
                </div>
            </div>
            { loading && <FullPageLoader visible={loading}/>}
        </div>
    );
}
