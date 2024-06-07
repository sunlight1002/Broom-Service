import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import { NavLink, Link } from "react-router-dom";
import { useTranslation } from "react-i18next";

import logo from "../../Assets/image/sample.svg";

export default function ClientSidebar() {
    const alert = useAlert();
    const navigate = useNavigate();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const HandleLogout = async (e) => {
        await axios.post("/api/client/logout", {}, { headers }).then((res) => {
            if (res.status === 200) {
                localStorage.removeItem("client-token");
                localStorage.removeItem("client-name");
                localStorage.removeItem("client-id");
                navigate("/client/login");
                alert.success(t("global.Logout"));
            }
        });
    };

    return (
        <div id="column-left">
            <div className="sideLogo">
                <Link to="/client/dashboard">
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
                    <NavLink to="/client/dashboard">
                        <i className="fa-solid fa-gauge"></i>
                        {t("client.sidebar.dashboard")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/client/schedule">
                        <i className="fa-solid fa-video"></i>
                        {t("client.common.meetings")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/client/offered-price">
                        <i className="fa-solid fa-tags"></i>
                        {t("client.common.offers")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/client/contracts">
                        <i className="fa-solid fa-clipboard-list"></i>
                        {t("client.sidebar.contracts")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/client/jobs">
                        <i className="fa-solid fa-briefcase"></i>
                        {t("client.common.services")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/client/invoices">
                        <i className="fa-solid fa-file-invoice"></i>
                        {t("client.common.payments")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/client/settings">
                        <i className="fa-solid fa-gear"></i>
                        {t("client.sidebar.settings")}
                    </NavLink>
                </li>
            </ul>
            <div className="sideLogout">
                <div className="logoutBtn">
                    <button className="btn btn-white" onClick={HandleLogout}>
                        {t("client.logout")}
                    </button>
                </div>
            </div>
        </div>
    );
}
