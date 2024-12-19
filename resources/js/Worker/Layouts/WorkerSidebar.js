import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useNavigate, Link } from "react-router-dom";
import logo from "../../Assets/image/sample.svg";
import { NavLink } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { IoIosLogOut } from "react-icons/io";
import axios from "axios";


export default function WorkerSidebar() {
    const alert = useAlert();
    const navigate = useNavigate();
    const { t } = useTranslation();
    const [protocol, setProtocol] = useState(false)
    const [hearing, setHearing] = useState(false)

    const workerId = localStorage.getItem("worker-id");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const getProtocol = async () => {
        try {
            const res = await axios.get(`/api/protocol?worker_id=${workerId}`, { headers });
            // console.log(res.data, "res");
            if (res.status === 200) {
                setProtocol(true);
            }
        } catch (error) {
            if (error.response && error.response.status === 404) {
                setProtocol(false);
            } else {
                setProtocol(false);
            }
        }
    };

    const getHiring = async () => {
        const res2 = await axios.get(`/api/schedule`, { headers });
        if (res2.data?.data?.length > 0) {
            setHearing(true);
        } else {
            setHearing(false)
        }
    }


    useEffect(() => {
        getProtocol();
        getHiring();
    }, [workerId]);

    const HandleLogout = async (e) => {
        await axios.post("/api/logout", {}, { headers }).then((res) => {
            if (res.status === 200) {
                localStorage.removeItem("worker-token");
                localStorage.removeItem("worker-name");
                localStorage.removeItem("worker-id");
                navigate("/worker/login");
                alert.success(t("global.Logout"));
            }
        });
    };

    return (
        <div id="column-left">
            <div className="sideLogo">
                <Link to="/worker/dashboard">
                    <svg
                        width="190"
                        height="77"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlnsXlink="http://www.w3.org/1999/xlink"
                    >
                        <image xlinkHref={logo} width="190" height="77"></image>
                    </svg>
                </Link>
            </div>
            <ul className="list-group">
                <li className="list-group-item">
                    <NavLink to="/worker/dashboard">
                        <i className="fa-solid fa-gauge"></i>
                        {t("worker.sidebar.dashboard")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/worker/jobs">
                        <i className="fa-solid fa-briefcase"></i>
                        {t("worker.sidebar.jobs")}
                    </NavLink>
                </li>
                {
                    hearing && (
                        <li className="list-group-item">
                            <NavLink to="/worker/hearing">
                                <i className="fa-solid fa-calendar-check font-12"></i>
                                {t("worker.sidebar.hearing")}
                            </NavLink>
                        </li>
                    )
                }
                {
                    protocol && (
                        <li className="list-group-item">
                            <NavLink to="/worker/protocol">
                                <i className="fa-solid fa-file-alt"></i>
                                {t("worker.sidebar.protocol")}
                            </NavLink>
                        </li>
                    )
                }
                <li className="list-group-item">
                    <NavLink to="/worker/schedule">
                        <i className="fa-solid fa-calendar-days"></i>
                        {t("worker.sidebar.schedule")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/worker/leaves">
                        <i className="fa-solid fa-calendar-xmark"></i>
                        {t("worker.sidebar.leaves")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/worker/advance-loan">
                        <i className="fa-solid fa-hand-holding-usd"></i>
                        {t("worker.sidebar.advance_loan")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/worker/tasks">
                    <i className="fa-solid fa-list-check"></i>
                        {t("worker.sidebar.tasks")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/worker/refund-claim">
                        <i className="fa-solid fa-file-contract"></i>
                        {t("worker.sidebar.refund_claim")}
                    </NavLink>
                </li>
                {/* <li className="list-group-item">
                    <NavLink to="/worker/not-available">
                        <i className="fa-solid fa-calendar-xmark"></i>
                        {t("worker.sidebar.not_available")}
                    </NavLink>
                </li> */}
                <li className="list-group-item">
                    <NavLink to="/worker/my-account">
                        <i className="fa-solid fa-user"></i>
                        {t("worker.my_account")}
                    </NavLink>
                </li>
            </ul>
            <div className="sideLogout">
                <div className="logoutBtn">
                    <button className="btn btn-white d-flex justify-content-center align-items-center" onClick={HandleLogout}>
                        <IoIosLogOut className="mr-1 font-28" />
                        {t("worker.logout")}
                    </button>
                </div>
            </div>
        </div>
    );
}
