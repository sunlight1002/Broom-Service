import axios from "axios";
import React, { useState } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function AddTeam() {

    const { t } = useTranslation();
    const [name, setName] = useState(null);
    const [hebname, setHebName] = useState(null);
    const [email, setEmail] = useState(null);
    const [phone, setPhone] = useState(null);
    const [address, setAddress] = useState(null);
    const [password, setPassword] = useState(null);
    const [confirmPassword, setConfirmPassword] = useState(null);
    const [status, setStatus] = useState(null);
    const [color, setColor] = useState(null);
    const [role, setRole] = useState("member");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null);

    const [payment, setPayment] = useState("")
    const [bankDetails, setBankDetails] = useState({
        full_name: "",
        bank_name: "",
        bank_no: null,
        branch_no: null,
        account_no: null
    })

    const alert = useAlert();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const handleBankDetails = (e) => {
        const { name, value } = e.target;
        setBankDetails(prev => ({ ...prev, [name]: value }));
    }

    const handleSubmit = (e) => {
        e.preventDefault();
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
            payment_type: payment,
            bank_name: bankDetails.bank_name,
            full_name: bankDetails.full_name,
            bank_number: bankDetails.bank_no,
            branch_number: bankDetails.branch_no,
            account_number: bankDetails.account_no,
        };

        axios.post(`/api/admin/teams`, data, { headers }).then((res) => {

            if (res.data.errors) {
                setLoading(false);
                setErrors(res.data.errors)
                for (let e in res.data.errors) {
                    alert.error(res.data.errors[e]);
                }
            } else {
                setLoading(false);
                alert.success(res.data.message);
                setTimeout(() => {
                    navigate("/admin/manage-team");
                }, 1000);
            }
        });
    };

    console.log(errors);


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">{t("admin.global.addTeamMember")}</h1>
                <form>
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <div className="dashBox p-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.global.NameEn")}
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        onChange={(e) =>
                                            setName(e.target.value)
                                        }
                                        placeholder="Enter name (english)"
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.global.NameHev")}
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        onChange={(e) =>
                                            setHebName(e.target.value)
                                        }
                                        placeholder="Enter name (hebrew)"
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.global.Email")}
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        className="form-control"
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
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
                                        {t("admin.global.Phone")}
                                    </label>
                                    <input
                                        type="tel"
                                        className="form-control"
                                        onChange={(e) =>
                                            setPhone(e.target.value)
                                        }
                                        placeholder="Enter phone"
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
                                        {t("client.meeting.address_txt")}
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        onChange={(e) =>
                                            setAddress(e.target.value)
                                        }
                                        placeholder="Enter address"
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="col-lg-6 col-12">
                            <div className="dashBox pt-4 pl-4 pr-4">
                                <div className="form-group">
                                    <div
                                        className="form-check form-check-inline1 pl-0"
                                        style={{ paddingLeft: "0" }}
                                    >
                                        <label
                                            className="form-check-label"
                                            htmlFor="title"
                                        >
                                            {t("client.settings.color")}
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
                                        <label htmlFor="swatch_7" style={{ background: "white" }}>
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>{t("admin.leads.AddLead.white")}</span>
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
                                        <span>{t("admin.leads.AddLead.Green")}</span>
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
                                        <span>{t("admin.leads.AddLead.Blue")}</span>
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
                                        <span>{t("admin.leads.AddLead.Voilet")}</span>
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
                                        <span>{t("admin.leads.AddLead.Red")}</span>
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
                                        <span>{t("admin.leads.AddLead.Orange")}</span>
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
                                        <span>{t("admin.leads.AddLead.Yellow")}</span>
                                    </div>
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.pass")}
                                    </label>
                                    <input
                                        type="password"
                                        name="password"
                                        className="form-control"
                                        onChange={(e) =>
                                            setPassword(e.target.value)
                                        }
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
                                        {t("client.settings.confirmPass")}
                                    </label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        onChange={(e) =>
                                            setConfirmPassword(e.target.value)
                                        }
                                        placeholder="Enter confirm password"
                                        autoComplete="new-password"
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("global.status")}
                                    </label>
                                    <select
                                        className="form-control"
                                        onChange={(e) =>
                                            setStatus(e.target.value)
                                        }
                                    >
                                        <option value={1}>{t("worker.settings.Enable")}</option>
                                        <option value={0}>{t("worker.settings.Disable")}</option>
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
                                    <div className="dashBox p-4">
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Full Name</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.full_name}
                                                        name="full_name"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder="Enter Full Name"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Bank Name</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_name}
                                                        name="bank_name"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder="Enter Bank Name"
                                                    />
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
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder="Enter Bank Number"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group">
                                                    <label className="control-label">Branch Number</label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.branch_no}
                                                        name="branch_no"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder="Enter Branch Number"
                                                    />
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
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder="Enter Account Number"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            )
                        }

                        <div className="col-sm-12">
                            <div className="dashBox p-4 mt-3">
                                <h4 className="mb-2">{t("worker.settings.Presetpermissions")}</h4>
                                <div className="form-group">
                                    <input
                                        type="radio"
                                        name="role"
                                        value="member"
                                        style={{ height: "unset" }}
                                        checked={role == "member"}
                                        onChange={(e) =>
                                            setRole(e.target.value)
                                        }
                                    />{" "}
                                    {t("worker.settings.makeMember")}
                                    <input
                                        type="radio"
                                        name="role"
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
                                    {t("worker.settings.makeAdministrator")}
                                </div>
                                <div className="form-group">
                                    <input
                                        type="submit"
                                        onClick={handleSubmit}
                                        className="btn navyblue no-hover saveBtn"
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
