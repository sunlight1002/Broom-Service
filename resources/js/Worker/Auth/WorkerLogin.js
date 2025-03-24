import React, { useEffect, useState } from "react";
import logo from "../../Assets/image/sample.svg";
import i18next from "i18next";
import { useNavigate } from "react-router-dom";
import FullLoader from "../../../../public/js/FullLoader";
import { getCookie } from "../../Admin/Components/common/Cookies";
import FullPageLoader from "../../Components/common/FullPageLoader";
import { useAlert } from "react-alert";


export default function WorkerLogin() {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [errors, setErrors] = useState([]);
    const [loading, setLoading] = useState(false)
    const [isRemembered, setIsRemembered] = useState(false)
    const alert = useAlert();
    const navigate = useNavigate()


    useEffect(() => {
        const workerLogin = localStorage.getItem("worker-token")
        // console.log(adminLogin);
        if (workerLogin) {
            navigate("/worker/dashboard");
        }
    }, [navigate])

    useEffect(() => {
        const token = getCookie('remember_device_token');
        if (token) {
            setIsRemembered(true);
        }
    }, []);

    const forgotPassword = async () => {
        if (!email) {
            alert.error('Please enter your email');
            return;
        }
    
        try {
            const response = await axios.post('/api/password/email', { email });
    
            alert.success(response?.data?.message || 'Reset link sent! Check your email.');
        } catch (err) {
            alert.error(err.response?.data?.message || 'Failed to send reset link.');
            console.error(err);
        }
    };
    


    const HandleLogin = (e) => {
        e.preventDefault();
        setLoading(true)
        const data = {
            email: email,
            password: password,
        };
        axios.post(`/api/login`, data).then((result) => {
            if (result.data.errors) {
                console.log(result.data.errors);
                
                setLoading(false)
                setErrors(result.data.errors);
            } else {
                if (isRemembered) {
                    localStorage.setItem("worker-token", result.data.token);
                    i18next.changeLanguage(result.data.lng);
                    if (result?.data?.lng == 'en') {
                        document.querySelector("html").removeAttribute("dir");
                        const rtlLink = document.querySelector('link[href*="rtl.css"]');
                        if (rtlLink) {
                            rtlLink.remove();
                        }
                    }
                    localStorage.setItem(
                        "worker-name",
                        result.data.firstname + " " + result.data.lastname
                    );
                    localStorage.setItem("worker-id", result.data.id);

                    window.location = "/worker/dashboard";
                } else {
                    if (result.data.two_factor_enabled === 1 || result.data[0] === 1) {
                        localStorage.setItem("worker-email", result.data.email);
                        localStorage.setItem("worker-lng", result.data.lng);
                        window.location = "/worker/login-otp";
                        setLoading(false)
                    } else {
                        localStorage.setItem("worker-token", result.data.token);
                        i18next.changeLanguage(result.data.lng);
                        if (result?.data?.lng == 'en') {
                            document.querySelector("html").removeAttribute("dir");
                            const rtlLink = document.querySelector('link[href*="rtl.css"]');
                            if (rtlLink) {
                                rtlLink.remove();
                            }
                        }
                        localStorage.setItem(
                            "worker-name",
                            result.data.firstname + " " + result.data.lastname
                        );
                        localStorage.setItem("worker-id", result.data.id);

                        window.location = "/worker/dashboard";
                    }
                }
            }
        });
    };

    return (
        <div>
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
                        <h1 className="page-title">Worker Login</h1>
                        <form onSubmit={HandleLogin}>
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
                                        type="text"
                                        name="email"
                                        className="form-control"
                                        placeholder="Enter Worker id or Email"
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
                                        autoFocus
                                    />
                                </div>
                                {errors.worker && (
                                    <small className="text-danger mb-1">
                                        {errors.worker}
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
                                        name="password"
                                        className="form-control"
                                        placeholder="Enter password"
                                        onChange={(e) =>
                                            setPassword(e.target.value)
                                        }
                                        autoComplete="new-password"
                                    />
                                </div>
                                {errors.password && (
                                    <small className="text-danger mb-1">
                                        {errors.password}
                                    </small>
                                )}
                            </div>
                            <div className='d-flex justify-content-start align-items-center'>
                                <button type="button" className="btn btn-link p-0" onClick={() => forgotPassword()}>forgot password</button>
                            </div>
                            <div className="form-group mt-1">
                                <button
                                    as="input"
                                    type="submit"
                                    className="btn btn-danger btn-block"
                                >
                                    {" "}
                                    Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
