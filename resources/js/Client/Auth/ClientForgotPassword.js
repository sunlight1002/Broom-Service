import i18next from "i18next";
import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from 'react-router-dom';
import { getCookie } from "../../Admin/Components/common/Cookies";
import logo from "../../Assets/image/sample.svg";
import FullPageLoader from "../../Components/common/FullPageLoader";
import axios from 'axios';


export default function ClientForgotPassword() {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [errors, setErrors] = useState([]);
    const [dir, setDir] = useState([]);
    const [loading, setLoading] = useState(false)
    const alert = useAlert();

    const { token } = useParams(); // Get the token from the URL
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const navigate = useNavigate();

    useEffect(() => {
        // Fetch data for the reset form
        axios
            .get(`/client/reset-password/${token}`)
            .then((response) => {
                console.log(response.data);
                // Optionally set email or other data from the response
            })
            .catch((error) => {
                console.error('Error fetching reset form data:', error);
            });
    }, [token]);

    const handleSubmit = (e) => {
        e.preventDefault();

        axios
            .post('/client/reset-password', {
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            })
            .then((response) => {
                console.log('Password reset successful:', response.data);
                navigate('/login'); // Redirect to login page on success
            })
            .catch((error) => {
                console.error('Error resetting password:', error.response.data);
            });
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
                        {dir == "heb" ? "כניסה ללקוח" : "Client Login"}
                    </h1>
                    <form onSubmit={handleSubmit}>
                        <div className="form-group">
                            <div className="input-group mt-2">
                                <div className="input-group-prepend">
                                    <span
                                        className="input-group-text"
                                        id="basic-addon1"
                                    >
                                        <i className="fa-solid fa-user"></i>
                                    </span>
                                </div>
                                <input
                                    type="email"
                                    className="form-control"
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="Email"
                                    name="username"
                                    aria-label="Username"
                                    autoFocus
                                />
                            </div>
                            {errors.email && (
                                <small className="text-danger mb-1">
                                    {errors.email}
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
                                        setPasswordConfirmation(e.target.value)
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

                        <div className='d-flex justify-content-start align-items-center'>
                           <button type="button" className="btn btn-link p-0" onClick={() => resetPassword()}>forgot password</button>
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
}
