import React, { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import { useNavigate } from "react-router-dom";
import logo from "../../Assets/image/sample.svg";
import FullPageLoader from "../../Components/common/FullPageLoader";
import { useAlert } from "react-alert";

const ChangePassword = () => {
    const [newPassword, setNewPassword] = useState("");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [errors, setErrors] = useState([]);
    const [dir, setDir] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState({
        newPassword: false,
        confirmPassword: false,
    });
    const navigate = useNavigate();
    const { t } = useTranslation();
    const alert = useAlert();

    useEffect(() => {
        const clientLogin = localStorage.getItem("client-token")
        // console.log(adminLogin);
        if (clientLogin) {
            navigate("/client/dashboard");
        }
    }, [navigate])

    const HandleLogin = (e) => {
        e.preventDefault();
        if (newPassword !== confirmPassword) {
            alert.error("Passwords do not match");
            return;
        }
        setLoading(true);
    
        const data = {
            id: localStorage.getItem("client-id"),
            new_password: newPassword,
            new_password_confirmation: confirmPassword,
        };
    
        axios.post(`/api/client/change-password`, data)
            .then((result) => {
                setLoading(false);
                if (result.data.errors) {
                    setErrors(result.data.errors);
                    return;
                }
                setConfirmPassword("");
                setNewPassword("");
                swal("Success", "Password updated successfully!", "success");
                navigate("/client/dashboard");
            })
            .catch((error) => {
                setLoading(false);
                swal("Error", error.response.data.message || "An error occurred", "error");
            });
    };
    

    useEffect(() => {
        let d = document.querySelector("html").getAttribute("dir");
        d === "rtl" ? setDir("heb") : setDir("en");
    }, []);

    const togglePasswordVisibility = (field) => {
        setShowPassword((prevState) => ({
            ...prevState,
            [field]: !prevState[field],
        }));
    };

    return (
        <div id="loginPage">
            <div className="container adminLogin">
                <div className="formSide">
                    <div className="hidden-xs ifRTL">
                        <svg
                            width="333"
                            height="135"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlnsXlink="http://www.w3.org/1999/xlink"
                        >
                            <image
                                xlinkHref={logo}
                                width="333"
                                height="135"
                            ></image>
                        </svg>
                    </div>
                    <h1 className="page-title">
                        {dir === "heb" ? "שנה סיסמה" : "Change Password"}
                    </h1>
                    <form onSubmit={HandleLogin}>
                        {/* New Password Input */}
                        <div className="form-group">
                            <div className="input-group mt-2 d-flex">
                                <div className="input-group-prepend">
                                    <span className="input-group-text">
                                        <i className="fa-solid fa-user"></i>
                                    </span>
                                </div>
                                <div className="input-wrapper" style={{ position: "relative", width: "92%" }}>
                                    <input
                                        type={showPassword.newPassword ? "text" : "password"}
                                        className="form-control"
                                        onChange={(e) => setNewPassword(e.target.value)}
                                        placeholder="Enter new password"
                                        name="new-password"
                                        aria-label="New Password"
                                        autoFocus
                                    />
                                    <span
                                        onClick={() => togglePasswordVisibility("newPassword")}
                                        style={{
                                            position: "absolute",
                                            right: "0.75rem",
                                            top: "50%",
                                            transform: "translateY(-50%)",
                                            cursor: "pointer",
                                        }}
                                    >
                                        {showPassword.newPassword ? <FaEyeSlash className="font-24" /> : <FaEye className="font-24" />}
                                    </span>
                                </div>
                            </div>
                            {errors.email && (
                                <small className="text-danger mb-1">
                                    {errors.email}
                                </small>
                            )}
                        </div>

                        {/* Confirm Password Input */}
                        <div className="form-group">
                            <div className="input-group d-flex">
                                <div className="input-group-prepend">
                                    <span className="input-group-text">
                                        <i className="fa-solid fa-key"></i>
                                    </span>
                                </div>
                                <div className="input-wrapper" style={{ position: "relative", width: "91.8%" }}>
                                    <input
                                        type={showPassword.confirmPassword ? "text" : "password"}
                                        className="form-control"
                                        onChange={(e) => setConfirmPassword(e.target.value)}
                                        placeholder="Enter confirm password"
                                        name="confirm-password"
                                        aria-label="Confirm Password"
                                        autoComplete="confirm-password"
                                    />
                                    <span
                                        onClick={() => togglePasswordVisibility("confirmPassword")}
                                        style={{
                                            position: "absolute",
                                            right: "0.75rem",
                                            top: "50%",
                                            transform: "translateY(-50%)",
                                            cursor: "pointer",
                                        }}
                                    >
                                        {showPassword.confirmPassword ? <FaEyeSlash className="font-24" /> : <FaEye className="font-24" />}
                                    </span>
                                </div>
                            </div>
                            {errors.password && (
                                <small className="text-danger mb-1">
                                    {errors.password}
                                </small>
                            )}
                        </div>

                        <div className="form-group mt-4">
                            <button type="submit" className="btn btn-danger btn-block">
                                {t("client.change_password")}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
};

export default ChangePassword;
