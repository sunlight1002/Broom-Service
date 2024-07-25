import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import { NavLink, Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { IoIosLogOut } from "react-icons/io";
import { HiOutlineSquares2X2 } from "react-icons/hi2";
import { RiVideoChatLine } from "react-icons/ri";
import { FaRegBookmark } from "react-icons/fa";
import { LiaFileContractSolid } from "react-icons/lia";
import { MdHomeRepairService } from "react-icons/md";
import { MdOutlinePayments } from "react-icons/md";
import { IoMdSettings } from "react-icons/io";

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
// console.log(t("client.sidebar.dashboard"));
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
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/dashboard"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <HiOutlineSquares2X2 className="font-28" />
                        </i>
                        {t("client.sidebar.dashboard")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/schedule"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <RiVideoChatLine className="font-28" />
                        </i>
                        {t("client.common.meetings")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/offered-price"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <FaRegBookmark className="font-28" />
                        </i>
                        {t("client.common.offers")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/contracts"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <LiaFileContractSolid className="font-28" />
                        </i>
                        {t("client.sidebar.contracts")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/jobs"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <MdHomeRepairService className="font-28" />
                        </i>
                        {t("client.common.services")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/invoices"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <MdOutlinePayments className="font-28" />
                        </i>
                        {t("client.common.payments")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink
                        className="d-flex align-items-center"
                        to="/client/settings"
                        activeClassName="active"
                    >
                        <i className="d-flex align-items-center">
                            <IoMdSettings className="font-28" />
                        </i>
                        {t("client.sidebar.settings")}
                    </NavLink>
                </li>
            </ul>
            <div className="sideLogout">
            <div className="logoutBtn">
                    <button className="btn d-flex justify-content-center align-items-center" onClick={HandleLogout}
                    >
                        <IoIosLogOut className="mr-1 font-28" />
                        {t("client.logout")}
                    </button>
            </div>
            </div>
        </div>
    );
}
