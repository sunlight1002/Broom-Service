import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import axios from "axios";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
import { useTranslation } from "react-i18next";

export default function EditTeam() {
    const [name, setName] = useState(null);
    const [hebname, setHebName] = useState(null);
    const [email, setEmail] = useState(null);
    const [phone, setPhone] = useState(" ");
    const [address, setAddress] = useState(null);
    const [password, setPassword] = useState(null);
    const [confirmPassword, setConfirmPassword] = useState(null);
    const [status, setStatus] = useState(null);
    const [color, setColor] = useState(null);
    const [role, setRole] = useState(null);
    const [payment, setPayment] = useState("")
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null)
    const [bankDetails, setBankDetails] = useState({
        payment_type: "",
        full_name: "",
        bank_name: "",
        bank_no: null,
        branch_no: null,
        account_no: null
    })
    const [showTimer, setShowTimer] = useState(false)
    const { t } = useTranslation();

    const alert = useAlert();
    const param = useParams();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setBankDetails((prevDetails) => ({
            ...prevDetails,
            [name]: value
        }));
    };

    const handleUpdate = (e) => {
        e.preventDefault()
        setLoading(true);

        const data = {
            name: name,
            heb_name: hebname,
            email: email,
            phone: phone,
            address,
            address,
            color: !color ? "#fff" : color,
            password: password,
            confirmation: confirmPassword,
            status: !status ? 1 : status,
            role: role,
            show_timer: showTimer,
            payment_type: payment,
            bank_name: bankDetails.bank_name,
            full_name: bankDetails.full_name,
            bank_number: bankDetails.bank_no,
            branch_number: bankDetails.branch_no,
            account_number: bankDetails.account_no,
        };

        axios
            .put(`/api/admin/teams/${param.id}`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    setLoading(false)
                    setErrors(res.data.errors)
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    setLoading(false)
                    alert.success(res.data.message);
                    setTimeout(() => {
                        navigate("/admin/manage-team");
                    }, 1000);
                }
            });
    };

    const getMember = () => {
        axios
            .get(`/api/admin/teams/${param.id}/edit`, { headers })
            .then((res) => {
                const d = res.data.data;
                setName(d.name);
                setHebName(d.heb_name);
                setEmail(d.email);
                setPhone(d.phone);
                setAddress(d.address);
                setStatus(d.status);
                setRole(d.role);
                setBankDetails({
                    bank_name: d.bank_name,
                    account_no: d.account_number,
                    bank_no: d.bank_number,
                    branch_no: d.branch_number,
                    full_name: d.full_name
                })
                setPayment(d.payment_type)
                setShowTimer(d.show_timer)
                if (d.color) {
                    let clr = document.querySelectorAll(
                        'input[name="swatch_demo"]'
                    );
                    clr.forEach((e, i) => {
                        e.getAttribute("color") == d.color
                            ? (e.checked = true)
                            : "";
                    });
                }
            });
    };
    useEffect(() => {
        getMember();
    }, []);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">Edit Team member</h1>
                <form>
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <div className="dashBox p-0 p-md-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Name - En
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        onChange={(e) =>
                                            setName(e.target.value)
                                        }
                                        value={name}
                                        placeholder="Enter name (english)"
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Name - Heb
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        onChange={(e) =>
                                            setHebName(e.target.value)
                                        }
                                        value={hebname}
                                        placeholder="Enter name (hebrew)"
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        className="form-control"
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
                                        value={email}
                                        placeholder="Enter email"
                                    />
                                    {errors?.email ? (
                                        <small className="text-danger mb-1">
                                            {errors?.email}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Phone
                                    </label>
                                    <PhoneInput
                                        country={'il'}
                                        value={phone}
                                        onChange={(phone, country) => {
                                            // Remove leading '0' after country code
                                            const dialCode = country.dialCode;
                                            let formattedPhone = phone;
                                            if (phone.startsWith(dialCode + '0')) {
                                                formattedPhone = dialCode + phone.slice(dialCode.length + 1);
                                            }
                                            setPhone(formattedPhone);
                                        }}
                                        inputClass="form-control"
                                        inputProps={{
                                            name: 'phone',
                                            required: true,
                                            placeholder: "Enter phone",
                                        }}
                                    />
                                    {errors?.phone ? (
                                        <small className="text-danger mb-1">
                                            {errors?.phone}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Address
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        onChange={(e) =>
                                            setAddress(e.target.value)
                                        }
                                        value={address}
                                        placeholder="Enter address"
                                    />
                                </div>
                                <div className="">
                                    <div className="d-flex justify-content-start align-items-center">
                                        <span className="rounded" style={{ "border": "1px solid #ebebeb", "overflow": "hidden" }}>
                                            <input id="timer" checked={showTimer == 1 ? true : false} onClick={(e) => setShowTimer(e.target.checked)} type="checkbox" className="form-control dt-if-completed-checkbox" style={{ "cursor": "pointer", "margin": "5px 5px" }} />
                                        </span>
                                        <label htmlFor="timer" className="form-check-label mx-1" style={{ "cursor": "pointer" }}>Show timer</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-lg-6 col-12">
                            <div className="dashBox pl-4 pt-4 pr-4">
                                <div className="form-group">
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
                                        <label htmlFor="swatch_2">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Green</span>
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
                                        <span>Blue</span>
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
                                        <span>Voilet</span>
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
                                        <span>Red</span>
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
                                        <span>Orange</span>
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
                                        <span>Yellow</span>
                                    </div>
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Password
                                    </label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        onChange={(e) =>
                                            setPassword(e.target.value)
                                        }
                                        value={password}
                                        placeholder="Enter password"
                                        autoComplete="new-password"
                                    />
                                    {errors?.password ? (
                                        <small className="text-danger mb-1">
                                            {errors?.password}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Confirm Password
                                    </label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        onChange={(e) =>
                                            setConfirmPassword(e.target.value)
                                        }
                                        value={confirmPassword}
                                        placeholder="Enter confirm password"
                                        autoComplete="new-password"
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Status
                                    </label>
                                    <select
                                        className="form-control"
                                        onChange={(e) =>
                                            setStatus(e.target.value)
                                        }
                                        value={status}
                                    >
                                        <option value={1}>Enable</option>
                                        <option value={0}>Disable</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Payment Method
                                    </label>

                                    <select
                                        className="form-control"
                                        value={payment}
                                        onChange={(e) =>
                                            setPayment(e.target.value)
                                        }
                                    >
                                        <option value="">--- please select ---</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="money_transfer">Money Transfer</option>
                                    </select>
                                    {errors?.payment_type ? (
                                        <small className="text-danger mb-1">
                                            {errors?.payment_type}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>
                            </div>
                        </div>
                        {
                            payment === "money_transfer" && (
                                <div className="col-sm-12 mt-2">
                                    <div className="dashBox p-0 p-md-4">
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Full Name</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.full_name}
                                                        name="full_name"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Full Name"
                                                    />
                                                    {errors?.full_name ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.full_name}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Bank Name</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_name}
                                                        name="bank_name"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Bank Name"
                                                    />
                                                    {errors?.bank_name ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.bank_name}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Bank Number</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_no}
                                                        name="bank_no"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Bank Number"
                                                    />
                                                    {errors?.bank_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.bank_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Branch Number</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.branch_no}
                                                        name="branch_no"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Branch Number"
                                                    />
                                                    {errors?.branch_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.branch_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Account Number</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.account_no}
                                                        name="account_no"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Account Number"
                                                    />
                                                    {errors?.account_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.account_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            )
                        }
                        <div className="col-sm-12">
                            <div className="dashBox p-0 p-md-4 mt-3">
                                <h4 className="mb-2">Preset permissions</h4>
                                <div className="form-group">
                                    <input
                                        type="radio"
                                        name="role"
                                        id="member"
                                        value="member"
                                        style={{ height: "unset" }}
                                        checked={role == "member"}
                                        onChange={(e) =>
                                            setRole(e.target.value)
                                        }
                                    />{" "}
                                    <label htmlFor="member">
                                        {t("worker.settings.makeMember")}
                                    </label>
                                    <input
                                        type="radio"
                                        name="role"
                                        id="admin"
                                        value="admin"
                                        style={{
                                            height: "unset",
                                            marginLeft: "10px",
                                        }}
                                        checked={role == "admin"}
                                        onChange={(e) =>
                                            setRole(e.target.value)
                                        }
                                    />{" "}
                                    <label htmlFor="admin">{t("worker.settings.makeAdministrator")}</label>
                                    <input
                                        type="radio"
                                        name="role"
                                        id="hr"
                                        value="hr"
                                        style={{
                                            height: "unset",
                                            marginLeft: "10px",
                                        }}
                                        checked={role == "hr"}
                                        onChange={(e) =>
                                            setRole(e.target.value)
                                        }
                                    />{" "}
                                    <label htmlFor="hr">Hr</label>
                                    <input
                                        type="radio"
                                        name="role"
                                        id="supervisor"
                                        value="supervisor"
                                        style={{
                                            height: "unset",
                                            marginLeft: "10px",
                                        }}
                                        checked={role == "supervisor"}
                                        onChange={(e) =>
                                            setRole(e.target.value)
                                        }
                                    />{" "}
                                    <label htmlFor="supervisor">{t("global.supervisor")}</label>
                                </div>
                                <div className="form-group">
                                    <input
                                        type="submit"
                                        onClick={handleUpdate}
                                        className="btn btn-pink saveBtn"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
