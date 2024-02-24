import React, { useState } from "react";
import { useAlert } from "react-alert";

export default function ChangePass() {
    const [currentPassword, setCurrentPassword] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmed, setPasswordConfirmed] = useState("");
    const [errors, setErrors] = useState([]);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("current_password", currentPassword);
        formData.append("password", password);
        formData.append("password_confirmation", passwordConfirmed);
        axios
            .post(`/api/admin/change-password`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    setCurrentPassword("");
                    setPassword("");
                    setPasswordConfirmed("");
                    alert.success("Password has been updated successfully");
                }
            });
    };

    return (
        <div className="card">
            <div className="card-body">
                <form>
                    <div className="form-group">
                        <label className="control-label">
                            Current password *
                        </label>
                        <input
                            type="password"
                            value={currentPassword}
                            onChange={(e) => setCurrentPassword(e.target.value)}
                            className="form-control"
                            placeholder="Current password"
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
                            New password *
                        </label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="form-control"
                            placeholder="New password"
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
                            Confirm new password *
                        </label>
                        <input
                            type="password"
                            value={passwordConfirmed}
                            onChange={(e) =>
                                setPasswordConfirmed(e.target.value)
                            }
                            className="form-control"
                            placeholder="Confirm new password"
                            autoComplete="new-password"
                        />
                    </div>
                    <div className="form-group text-center">
                        <input
                            type="submit"
                            onClick={handleSubmit}
                            className="btn btn-danger saveBtn"
                        />
                    </div>
                </form>
            </div>
        </div>
    );
}
