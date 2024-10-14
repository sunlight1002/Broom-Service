import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams } from "react-router-dom";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";

import Sidebar from "../../Layouts/Sidebar";
import PropertyAddress from "../../Components/Leads/PropertyAddress";
import JobMenu from "../../Components/Job/JobMenu";
import { useTranslation } from "react-i18next";
import { IoSaveOutline } from "react-icons/io5";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

export default function EditClient() {
    const [firstname, setFirstName] = useState("");
    const [lastname, setLastName] = useState("");
    const [email, setEmail] = useState("");
    const [invoiceName, setInvoiceName] = useState("");
    const [phone, setPhone] = useState("");
    const [dob, setDob] = useState("");
    const [passcode, setPassCode] = useState("");
    const [lng, setLng] = useState("");
    const [color, setColor] = useState("");
    const [status, setStatus] = useState("");
    const [errors, setErrors] = useState([]);
    const [paymentMethod, setPaymentMethod] = useState("");
    const [notificationType, setNotificationType] = useState("both");
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const [vatNumber, setVatNumber] = useState("");
    const [loading, setLoading] = useState(false);

    const alert = useAlert();
    const params = useParams();
    const navigate = useNavigate();
    const { t } = useTranslation();


    const [cjob, setCjob] = useState("0");
    const [addresses, setAddresses] = useState([]);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        setLoading(true);
        let to = 0;
        let taxper = 17;
        if (cjob == 1) {
            for (let t in formValues) {
                !formValues[t].type ? (formValues[t].type = "fixed") : "";
                if (formValues[t].type == "hourly") {
                    formValues[t].totalamount = parseInt(
                        formValues[t].jobHours * formValues[t].rateperhour
                    );
                    to += parseInt(formValues[t].totalamount);
                } else if (formValues[t].type == "squaremeter") {
                    formValues[t].totalamount = parseInt(
                        formValues[t].ratepersquaremeter * formValues[t].totalsquaremeter
                    );
                    to += parseInt(formValues[t].totalamount);
                } else {
                    formValues[t].totalamount = parseInt(
                        formValues[t].fixed_price
                    );
                    to += parseInt(formValues[t].fixed_price);
                }
            }
        }

        let tax = (taxper / 100) * to;
        const jobdata = {
            status: "sent",
            subtotal: to,
            total: to + tax,
            services: JSON.stringify(formValues),
        };

        // var phoneClc = "";
        // var phones = document.querySelectorAll(".pphone");
        // phones.forEach((p, i) => {
        //     phoneClc += p.value + ",";
        // });
        // phoneClc = phoneClc.replace(/,\s*$/, "");
        const data = {
            firstname: firstname,
            lastname: lastname,
            invoicename: invoiceName,
            lng: lng ? lng : "heb",
            dob: dob,
            passcode: passcode,
            color: !color ? "#fff" : color,
            email: email,
            phone: phone,
            password: passcode,
            vat_number: vatNumber,
            payment_method: paymentMethod,
            notification_type: notificationType,
            extra: JSON.stringify(extra),
            status: !status ? 0 : parseInt(status),
        };

        axios
            .put(
                `/api/admin/clients/${params.id}`,
                { data: data, jobdata: cjob == 1 ? jobdata : {} },
                { headers }
            )
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                    setLoading(false);
                } else {
                    alert.success("Client has been updated successfully");
                    setLoading(false);
                    setTimeout(() => {
                        navigate("/admin/clients");
                    }, 1000);
                }
            })
            .catch((e) => {
                setLoading(false);
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const getClient = () => {
        axios
            .get(`/api/admin/clients/${params.id}/edit`, { headers })
            .then((response) => {
                setFirstName(response.data.client.firstname);
                setLastName(response.data.client.lastname);
                setEmail(response.data.client.email);
                setPhone(response.data.client.phone);
                setPassCode(response.data.client.passcode);
                setDob(response.data.client.dob);
                setLng(response.data.client.lng);
                handleServiceLng(response.data.client.lng);
                setColor(response.data.client.color);
                setInvoiceName(response.data.client.invoicename);
                setStatus(response.data.client.status);
                setPaymentMethod(response.data.client.payment_method);
                setNotificationType(response.data.client.notification_type);
                setVatNumber(response.data.client.vat_number);
                setAddresses(response.data.client.property_addresses);
                response.data.client.extra != null
                    ? setExtra(JSON.parse(response.data.client.extra))
                    : setExtra([{ email: "", name: "", phone: "" }]);
                if (response.data.client.color) {
                    let clr = document.querySelectorAll(
                        'input[name="swatch_demo"]'
                    );
                    clr.forEach((e, i) => {
                        e.getAttribute("color") == response.data.client.color
                            ? (e.checked = true)
                            : "";
                    });
                }
            });
    };
    useEffect(() => {
        getClient();
    }, []);

    /*  Job Add */
    const [formValues, setFormValues] = useState([]);
    const [AllServices, setAllServices] = useState([]);
    const [AllFreq, setAllFreq] = useState([]);

    const handleSave = (indexKey, tmpJobData) => {
        let newFormValues = [...formValues];
        if (indexKey > -1 && indexKey !== "" && indexKey !== undefined) {
            newFormValues[indexKey] = tmpJobData;
        } else {
            newFormValues = [...formValues, tmpJobData];
        }
        setFormValues(newFormValues);
    };

    let removeFormFields = (i) => {
        let newFormValues = [...formValues];
        newFormValues.splice(i, 1);
        setFormValues(newFormValues);
    };

    const getServices = (lng) => {
        axios
            .post("/api/admin/all-services", { lng }, { headers })
            .then((res) => {
                setAllServices(res.data.services);
            });
    };
    const getFrequency = (lng) => {
        axios
            .post("/api/admin/all-service-schedule", { lng }, { headers })
            .then((res) => {
                setAllFreq(res.data.schedules);
            });
    };

    const handleServiceLng = (lng) => {
        getServices(lng);
        getFrequency(lng);
    };

    useEffect(() => {
        handleServiceLng("heb");
    }, []);

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

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">

                    <form>
                        <div className="d-flex align-items-center justify-content-between">
                            <h1 className="page-title addEmployer"
                                style={{ border: "none" }}
                            >
                                Edit Client
                            </h1>
                            <div className="text-center">
                                <button
                                    type="submit"
                                    onClick={handleUpdate}
                                    className="btn navyblue d-flex align-items-center saveBtn"
                                    style={{ paddingLeft: "20px", paddingRight: "20px" }}
                                // value="Save"
                                ><IoSaveOutline className="mr-2" /> {t("admin.leads.save")}</button>
                                {/* <input
                                type="submit"
                                onClick={handleFormSubmit}
                                className="btn navyblue saveBtn"
                                value="Clear"
                            /> */}
                            </div>
                        </div>
                        <div className="container-box d-flex justify-content-between">
                            <div className="card-item mr-4 resMarginRight" style={{ background: "#FAFBFC" }}>
                                <div className="card-heading">
                                    <p style={{ margin: "20px 34px 9px", fontSize: "20px" }} className="navyblueColor">{t("admin.leads.AddLead.General_Information")} </p>
                                </div>
                                <div className="card-body d-flex">
                                    <div className="col">
                                        <div className="form-group d-flex align-items-center w-100">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
                                                {t("admin.leads.AddLead.Notification_Type")}
                                            </label>

                                            <select
                                                className="form-control"
                                                value={notificationType}
                                                onChange={(e) => {
                                                    setNotificationType(e.target.value);
                                                }}
                                            >
                                                <option value="both">Both</option>
                                                <option value="email">
                                                    Email
                                                </option>
                                                <option value="whatsapp">
                                                    WhatsApp
                                                </option>
                                            </select>
                                        </div>
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "14.4rem" }}>
                                                {t(
                                                    "admin.leads.AddLead.FirstName"
                                                )}{" "}
                                                *
                                            </label>
                                            <div className="d-flex flex-column w-100">
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
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
                                    </div>
                                    <div className="col">
                                    <div className="form-group d-flex">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
                                                {t("admin.leads.AddLead.PrimaryPhone")} *
                                            </label>
                                            <div className="d-flex flex-column w-100">
                                                <PhoneInput
                                                    country={'il'}
                                                    value={phone}
                                                    onChange={(phone) => {
                                                        setPhone(phone);
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
                                                        {errors.phone[0]}
                                                    </small>
                                                )}
                                            </div>
                                        </div>
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
                                                {t(
                                                    "admin.leads.AddLead.PrimaryEmail"
                                                )}{" "}
                                                *
                                            </label>
                                            <div className="d-flex flex-column w-100">

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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
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
                                        <div className="form-group d-flex align-items-center">
                                            <label className="control-label navyblueColor" style={{ width: "15rem" }}>
                                                {t(
                                                    "admin.leads.AddLead.Password"
                                                )}
                                            </label>
                                            <input
                                                type="password"
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
                                            id="swatch_7"
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
                                    <div className="swatch green mb-3">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_2"
                                            value="2"
                                            color="#28a745"
                                            onChange={(e) =>
                                                setColor("#28a745")
                                            }
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
                                    <div className="swatch purple mb-3">
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
                                    <div className="swatch red mb-3">
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
                                    <div className="swatch orange mb-3">
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
                                    <div className="swatch yellow mb-3">
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
                            </div>
                            <div className="card-item additional_contract_box" style={{ background: "#FAFBFC" }}>
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
                                                            <div className="form-group" style={{ marginRight: "6px" }}>
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
                                                            <div className="form-group" style={{ marginRight: "6px" }}>
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
                                                            <div className="form-group" style={{ marginRight: "6px" }}>
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
                                                                        style={{ fontSize: "24px", color: "#2F4054", padding: "1px 9px", background: "#E5EBF1", borderRadius: "5px" }}
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
                                                                    style={{ fontSize: "24px", color: "#2F4054", padding: "1px 9px", background: "#E5EBF1", borderRadius: "5px" }}
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
                            />
                        </div>
                    </form>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
