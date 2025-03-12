import React, { useState } from "react";
import { useSearchParams, useNavigate } from 'react-router-dom';

import axios from "axios";
import logo from "../../../Assets/image/sample.svg";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import { useAlert } from "react-alert";

const AdminForgetPassword = () => {
    const [searchParams] = useSearchParams();
    const token = decodeURIComponent(searchParams.get("token"));
    const email = decodeURIComponent(searchParams.get("email"));
    const [dir, setDir] = useState([]);
    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const [password, setPassword] = useState("");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [message, setMessage] = useState("");
    const [loading, setLoading] = useState(false)
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (password !== confirmPassword) {
            setMessage("Passwords do not match.");
            return;
        }

        try {
            const response = await axios.post("/api/admin/reset-password", {
                token,
                email,
                password,
                password_confirmation: confirmPassword
            });
            alert.success(response?.data?.message);
            navigate("/admin/login");
            setMessage(response.data.message);
        } catch (error) {
            setMessage(error.response.data.message || "Something went wrong.");
        }
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
                    <div className="hidden-xl ifRTL">
                        <svg
                            width="250"
                            height="94"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlnsXlink="http://www.w3.org/1999/xlink"
                        >
                            <image
                                xlinkHref={logo}
                                width="250"
                                height="94"
                            ></image>
                        </svg>
                    </div>
                    <h1 className="page-title">
                        Admin Reset Password
                    </h1>
                    <form onSubmit={handleSubmit}>
                        <div className="form-group">
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span
                                        className="input-group-text"
                                        id="basic-addon1"
                                    >
                                        <i className="fa-solid fa-key"></i>
                                    </span>
                                </div>
                                <input
                                    type="password"
                                    className="form-control"
                                    onChange={(e) =>
                                        setPassword(e.target.value)
                                    }
                                    placeholder="Password"
                                    name="password"
                                    aria-label="Password"
                                    autoComplete="new-password"
                                />
                            </div>
                            {errors.password && (
                                <small className="text-danger mb-1">
                                    {errors.password}
                                </small>
                            )}
                        </div>
                        <div className="form-group">
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span
                                        className="input-group-text"
                                        id="basic-addon1"
                                    >
                                        <i className="fa-solid fa-key"></i>
                                    </span>
                                </div>
                                <input
                                    type="confirm-password"
                                    className="form-control"
                                    onChange={(e) =>
                                        setConfirmPassword(e.target.value)
                                    }
                                    placeholder="confirm password"
                                    name="password"
                                    aria-label="confirm-password"
                                    autoComplete="new-password"
                                />
                            </div>
                            {/* {errors.password && (
                                <small className="text-danger mb-1">
                                    {errors.password}
                                </small>
                            )} */}
                        </div>
                        <div className="form-group mt-1">
                            <button
                                type="submit"
                                className="btn btn-danger btn-block"
                            >
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
};

export default AdminForgetPassword;
