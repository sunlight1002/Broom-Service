import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams } from "react-router-dom";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";

import Sidebar from "../../Layouts/Sidebar";
import PropertyAddress from "../../Components/Leads/PropertyAddress";
import JobMenu from "../../Components/Job/JobMenu";

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
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const alert = useAlert();
    const params = useParams();
    const navigate = useNavigate();

    const [cjob, setCjob] = useState("0");
    const [addresses, setAddresses] = useState([]);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        {
            /* Job Data*/
        }
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

        var phoneClc = "";
        var phones = document.querySelectorAll(".pphone");
        phones.forEach((p, i) => {
            phoneClc += p.value + ",";
        });
        phoneClc = phoneClc.replace(/,\s*$/, "");
        const data = {
            firstname: firstname,
            lastname: lastname,
            invoicename: invoiceName,
            lng: lng ? lng : "heb",
            dob: dob,
            passcode: passcode,
            color: !color ? "#fff" : color,
            email: email,
            phone: phoneClc,
            password: passcode,
            payment_method: paymentMethod,
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
                } else {
                    alert.success("Client has been updated successfully");
                    setTimeout(() => {
                        navigate("/admin/clients");
                    }, 1000);
                }
            })
            .catch((e) => {
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
                    <h1 className="page-title addEmployer">Edit Client</h1>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                First Name *
                                            </label>
                                            <input
                                                type="text"
                                                value={firstname}
                                                onChange={(e) =>
                                                    setFirstName(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter First Name"
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
                                                Last Name *
                                            </label>
                                            <input
                                                type="text"
                                                value={lastname}
                                                onChange={(e) =>
                                                    setLastName(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter Last Name"
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Invoice Name *
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
                                                placeholder="Enter Last Name"
                                            />
                                        </div>
                                    </div>

                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Primary Email *
                                            </label>
                                            <input
                                                type="email"
                                                value={email}
                                                onChange={(e) =>
                                                    setEmail(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Email"
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
                                                Password *
                                            </label>
                                            <input
                                                type="password"
                                                onChange={(e) =>
                                                    setPassCode(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Password"
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
                                                Primary Phone
                                            </label>
                                            <input
                                                type="tel"
                                                value={phone}
                                                name="phone"
                                                onChange={(e) =>
                                                    setPhone(e.target.value)
                                                }
                                                className="form-control pphone"
                                                placeholder="Phone"
                                            />
                                        </div>
                                    </div>

                                    {extra &&
                                        extra.map((ex, i) => {
                                            return (
                                                <React.Fragment key={i}>
                                                    <div className="col-sm-4">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                Alternate Email
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
                                                                placeholder="email"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="col-sm-4">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                Person Name
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
                                                                placeholder="person name"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="col-sm-3">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                Alternate phone
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
                                                                placeholder="Phone"
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
                                    heading={"Property Address"}
                                    errors={errors}
                                    setErrors={setErrors}
                                    addresses={addresses}
                                    setAddresses={setAddresses}
                                />

                                <div className="form-group">
                                    <label className="control-label">
                                        Date of Birth
                                    </label>
                                    <input
                                        type="date"
                                        value={dob}
                                        onChange={(e) => setDob(e.target.value)}
                                        className="form-control"
                                        placeholder="Enter dob"
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
                                        Payment Method
                                    </label>

                                    <select
                                        className="form-control"
                                        value={paymentMethod}
                                        onChange={(e) => {
                                            setPaymentMethod(e.target.value);
                                        }}
                                    >
                                        <option value="cc">Credit Card</option>
                                        <option value="mt">
                                            Money Transfer
                                        </option>
                                        <option value="cheque">
                                            By Cheque
                                        </option>
                                        <option value="cash">By Cash</option>
                                    </select>
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Language
                                    </label>

                                    <select
                                        className="form-control"
                                        value={lng}
                                        onChange={(e) => {
                                            setLng(e.target.value);
                                            handleServiceLng(e.target.value);
                                        }}
                                    >
                                        <option value="heb">Hebrew</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                                <div className="form-group lcs">
                                    <div
                                        className="form-check form-check-inline1 pl-0"
                                        style={{ paddingLeft: "0" }}
                                    >
                                        <label
                                            className="form-check-label"
                                            htmlFor="title"
                                        >
                                            Color
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
                                        <label htmlFor="swatch_7">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>white</span>
                                    </div>
                                    <div className="swatch green  mb-3">
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
                                        <label htmlFor="swatch_2">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Green</span>
                                    </div>
                                    <div className="swatch blue  mb-3">
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
                                        <span>Blue</span>
                                    </div>
                                    <div className="swatch purple  mb-3">
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
                                        <span>Voilet</span>
                                    </div>
                                    <div className="swatch red  mb-3">
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
                                        <span>Red</span>
                                    </div>
                                    <div className="swatch orange  mb-3">
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
                                        <span>Orange</span>
                                    </div>
                                    <div className="swatch yellow  mb-3">
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
                                        <span>Yellow</span>
                                    </div>

                                    {errors.color ? (
                                        <small className="text-danger mb-1">
                                            {errors.color}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group mt-35">
                                    <label className="control-label">
                                        Status
                                    </label>
                                    <select
                                        className="form-control"
                                        value={status}
                                        onChange={(e) =>
                                            setStatus(e.target.value)
                                        }
                                    >
                                        <option value="0">Lead</option>
                                        <option value="1">
                                            Potential Customer
                                        </option>
                                        <option value="2">Customer</option>
                                    </select>
                                    {errors.status ? (
                                        <small className="text-danger mb-1">
                                            {errors.status}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div
                                    className="form-group mt-35"
                                    style={{ display: "none" }}
                                >
                                    <label className="control-label">
                                        Create Job
                                    </label>
                                    <select
                                        className="form-control"
                                        value={cjob}
                                        onChange={(e) => {
                                            setCjob(e.target.value);
                                            e.target.value == "1"
                                                ? (document.querySelector(
                                                      ".ClientJobSection"
                                                  ).style.display = "block")
                                                : (document.querySelector(
                                                      ".ClientJobSection"
                                                  ).style.display = "none");
                                        }}
                                    >
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>

                                {/* Create Job */}
                                <div
                                    className="ClientJobSection"
                                    style={{ display: "none" }}
                                >
                                    {cjob === "1" && (
                                        <JobMenu
                                            addresses={addresses}
                                            AllServices={AllServices}
                                            AllFreq={AllFreq}
                                            formValues={formValues}
                                            handleSaveJobForm={handleSave}
                                            handleRemoveFormFields={
                                                removeFormFields
                                            }
                                        />
                                    )}
                                </div>

                                <div className="form-group text-center">
                                    <input
                                        type="submit"
                                        onClick={handleUpdate}
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
