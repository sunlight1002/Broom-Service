import React, { useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function ChangePass() {

    const { t } = useTranslation();
    const [currentPassword, setCurrentPassword] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmed, setPasswordConfirmed] = useState("");
    const [errors, setErrors] = useState([]);
    const [loading, setLoading] = useState(false);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        const formData = new FormData();
        formData.append("current_password", currentPassword);
        formData.append("password", password);
        formData.append("password_confirmation", passwordConfirmed);
        axios
            .post(`/api/admin/change-password`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setLoading(false)
                    setErrors(response.data.errors);
                } else {
                    setLoading(false)
                    setCurrentPassword("");
                    setPassword("");
                    setPasswordConfirmed("");
                    alert.success("Password has been updated successfully");
                }
            });
    };

    return (
        <div className="card" style={{boxShadow: "none"}}>
            <div className="card-body">
                <form>
                    <div className="form-group">
                        <label className="control-label">
                            {t("client.settings.c_pass")} *
                        </label>
                        <input
                            type="password"
                            value={currentPassword}
                            onChange={(e) => setCurrentPassword(e.target.value)}
                            className="form-control"
                            placeholder={t("client.settings.c_pass")}
                            autoComplete="new-password"
                        />
                        {errors.current_password ? (
                            <small className="text-danger mb-1">
                                {errors.current_password}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className="form-group">
                        <label className="control-label">
                        {t("client.settings.u_pass")}*
                        </label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="form-control"
                            placeholder={t("client.settings.u_pass")}
                            autoComplete="new-password"
                        />
                        {errors.password ? (
                            <small className="text-danger mb-1">
                                {errors.password}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className="form-group">
                        <label className="control-label">
                        {t("client.settings.cn_pass")} *
                        </label>
                        <input
                            type="password"
                            value={passwordConfirmed}
                            onChange={(e) =>
                                setPasswordConfirmed(e.target.value)
                            }
                            className="form-control"
                            placeholder={t("client.settings.cn_pass")}
                            autoComplete="new-password"
                        />
                    </div>
                    <div className="form-group text-center">
                        <input
                            type="submit"
                            onClick={handleSubmit}
                            value={t("client.jobs.review.Submit")}
                            className="btn navyblue saveBtn"
                        />
                    </div>
                </form>
            </div>
            { loading && <FullPageLoader visible={loading}/>}
        </div>
    );
}
