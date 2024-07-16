import React, { useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import { LuSave } from "react-icons/lu";
import { FaEye, FaEyeSlash } from "react-icons/fa";

export default function ChangePass() {
    const [currentPassword, setCurrentPassword] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmed, setPasswordConfirmed] = useState("");
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmed, setShowPasswordConfirmed] = useState(false);
    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const togglePasswordVisibility = (type) => {
        switch (type) {
            case "current":
                setShowCurrentPassword(!showCurrentPassword);
                break;
            case "new":
                setShowPassword(!showPassword);
                break;
            case "confirm":
                setShowPasswordConfirmed(!showPasswordConfirmed);
                break;
            default:
                break;
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("current_password", currentPassword);
        formData.append("password", password);
        formData.append("password_confirmation", passwordConfirmed);
        axios
            .post(`/api/client/change-password`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    setCurrentPassword("");
                    setPassword("");
                    setPasswordConfirmed("");
                    alert.success(t("client.settings.passwordUpdateSuccess"));
                }
            });
    };

    return (
        <div className="card"
            style={{
                background: "#FAFBFC",
                boxShadow: "none",
                border: "1px solid #E5EBF1",
            }}>
            <p className="ml-3 mt-4" style={{ fontWeight: "bolder" }}>
                {t("client.settings.change_pass")}
            </p>

            <div className="card-body">
                <form>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label " style={{ width: "55%" }}>
                            {t("client.settings.change_pass")} *
                        </label>
                        <div className="input-wrapper" style={{ position: "relative", width: "100%" }}>
                            <input
                                type={showCurrentPassword ? "text" : "password"}
                                value={currentPassword}
                                onChange={(e) => setCurrentPassword(e.target.value)}
                                className="form-control"
                                placeholder={t("client.settings.c_pass")}
                                autoComplete="new-password"
                                style={{ paddingRight: "2.5rem" }}
                            />
                            <span
                                onClick={() => togglePasswordVisibility("current")}
                                style={{
                                    position: "absolute",
                                    right: "0.75rem",
                                    top: "50%",
                                    transform: "translateY(-50%)",
                                    cursor: "pointer",
                                }}
                            >
                                {showCurrentPassword ? <FaEyeSlash /> : <FaEye />}
                            </span>
                        </div>
                        {errors.current_password && (
                            <small className="text-danger mb-1">
                                {errors.current_password}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "55%" }}>
                            {t("client.settings.u_pass")} *
                        </label>
                        <div className="input-wrapper" style={{ position: "relative", width: "100%" }}>
                            <input
                                type={showPassword ? "text" : "password"}
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className="form-control"
                                placeholder={t("client.settings.u_pass")}
                                autoComplete="new-password"
                                style={{ paddingRight: "2.5rem" }}
                            />
                            <span
                                onClick={() => togglePasswordVisibility("new")}
                                style={{
                                    position: "absolute",
                                    right: "0.75rem",
                                    top: "50%",
                                    transform: "translateY(-50%)",
                                    cursor: "pointer",
                                }}
                            >
                                {showPassword ? <FaEyeSlash /> : <FaEye />}
                            </span>
                        </div>
                        {errors.password && (
                            <small className="text-danger mb-1">
                                {errors.password}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "55%" }}>
                            {t("client.settings.cn_pass")}*
                        </label>
                        <div className="input-wrapper" style={{ position: "relative", width: "100%" }}>
                            <input
                                type={showPasswordConfirmed ? "text" : "password"}
                                value={passwordConfirmed}
                                onChange={(e) => setPasswordConfirmed(e.target.value)}
                                className="form-control"
                                placeholder={t("client.settings.cn_pass")}
                                autoComplete="new-password"
                                style={{ paddingRight: "2.5rem" }}
                            />
                            <span
                                onClick={() => togglePasswordVisibility("confirm")}
                                style={{
                                    position: "absolute",
                                    right: "0.75rem",
                                    top: "50%",
                                    transform: "translateY(-50%)",
                                    cursor: "pointer",
                                }}
                            >
                                {showPasswordConfirmed ? <FaEyeSlash /> : <FaEye />}
                            </span>
                        </div>
                    </div>
                    <div className="form-group text-right">
                        <button
                            type="submit"
                            onClick={handleSubmit}
                            className="btn navyblue saveBtn"
                        >
                            <span className="d-flex align-items-center">
                                <LuSave className="mr-1" />
                                {t("client.settings.save")}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
